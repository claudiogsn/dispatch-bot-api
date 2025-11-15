<?php
date_default_timezone_set('America/Rio_Branco');
header('Content-Type: application/json; charset=utf-8');
ini_set('memory_limit', '-1');


require_once __DIR__ . '/../database/db.php';

class BIController {

    public static function getContasBi($requestData)
    {
        global $pdo;

        // Verificar obrigatórios
        if (!isset($requestData['data_inicial']) || !isset($requestData['data_final'])) {
            return [
                "success" => false,
                "message" => "Parâmetros obrigatórios ausentes: data_inicial e data_final."
            ];
        }

        $data_inicial = $requestData['data_inicial'];
        $data_final   = $requestData['data_final'];

        // WHERE base fixo
        $where = [
            "dt_mov BETWEEN :data_inicial AND :data_final"
        ];

        $bind = [
            ":data_inicial" => $data_inicial,
            ":data_final"   => $data_final,
        ];

        // Campos válidos da tabela bi_conta
        $validFields = [
            'nome_estabelecimento','cnpj_estabelecimento','num_controle','nome_entregador',
            'desconto','origem','nome','intg_tipo','npessoas','fone','delivered_by',
            'nome_caixa','bairrox','hora_saida','dt_abertura','hr_abertura','dt_fecha','hr_despacho',
            'troco_para','valor','status','bx_entrega','forma','max_ope','aviso','valor_total_pago',
            'valor_entrega_propria','valor_taxa_servico','hora_pronto_kds','comanda','mesa'
        ];

        // Processar filtros adicionais
        foreach ($requestData as $field => $value) {

            if ($value === null || $value === '' || $field === 'data_inicial' || $field === 'data_final') {
                continue;
            }

            if (!in_array($field, $validFields)) {
                continue;
            }

            // Se array → IN
            if (is_array($value)) {
                $keys = [];
                foreach ($value as $i => $v) {
                    $key = ":{$field}_{$i}";
                    $keys[] = $key;
                    $bind[$key] = $v;
                }
                $where[] = "$field IN (" . implode(',', $keys) . ")";
            }

            // Igualdade sempre
            else {
                $where[] = "$field = :$field";
                $bind[":$field"] = $value;
            }
        }

        // SQL final
        $sql = "SELECT * FROM bi_conta WHERE " . implode(" AND ", $where);

        $stmt = $pdo->prepare($sql);

        foreach ($bind as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public static function getItensBi($requestData)
    {
        global $pdo;

        // Verifica obrigatórios
        if (!isset($requestData['data_inicial']) || !isset($requestData['data_final'])) {
            return [
                "success" => false,
                "message" => "Parâmetros obrigatórios ausentes: data_inicial e data_final."
            ];
        }

        $data_inicial = $requestData['data_inicial'];
        $data_final   = $requestData['data_final'];

        // WHERE base
        $where = [
            "dt_mov BETWEEN :data_inicial AND :data_final"
        ];

        $bind = [
            ":data_inicial" => $data_inicial,
            ":data_final"   => $data_final,
        ];

        // Campos válidos (schema da tabela)
        $validFields = [
            'nome_estabelecimento','cnpj_estabelecimento','num_controle','sequencial','codigo','operacao',
            'nquant','npreco','caixinha','garcon','hora','hora_at','mesa','comanda','fone','nome','fantasia',
            'intg_tipo','npessoas','origem_conta','delivered_by','hr_fechamento_conta','hora_lancamento',
            'hora_at_despachado','valor_total_prod','valor_total_prodserv','nome_produto','impressora_produto',
            'nome_categoria','nome_tipo','nome_garcom',
            'parte1_descricao','parte2_descricao','parte3_descricao','parte4_descricao','parte5_descricao',
            'parte6_descricao','parte7_descricao','parte8_descricao','parte9_descricao','parte10_descricao',
            'parte11_descricao','parte12_descricao'
        ];

        // Processa filtros enviados
        foreach ($requestData as $field => $value) {

            if ($value === null || $value === '' || $field === 'data_inicial' || $field === 'data_final') {
                continue;
            }

            if (!in_array($field, $validFields)) {
                continue;
            }

            // Caso array => IN
            if (is_array($value)) {
                $keys = [];
                foreach ($value as $i => $v) {
                    $key = ":{$field}_{$i}";
                    $keys[] = $key;
                    $bind[$key] = $v;
                }
                $where[] = "$field IN (" . implode(',', $keys) . ")";
            }

            // Igualdade simples
            else {
                $where[] = "$field = :$field";
                $bind[":$field"] = $value;
            }
        }

        // SQL final
        $sql = "SELECT * FROM bi_itens WHERE " . implode(" AND ", $where);

        $stmt = $pdo->prepare($sql);

        foreach ($bind as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public static function getContaDetalhada($cnpj, $num_controle) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM bi_conta WHERE cnpj_estabelecimento = :cnpj AND num_controle = :num_controle');
        $stmt->bindParam(':cnpj', $cnpj);
        $stmt->bindParam(':num_controle', $num_controle);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getItemDetalhado($cnpj, $num_controle) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM bi_itens WHERE cnpj_estabelecimento = :cnpj AND num_controle = :num_controle');
        $stmt->bindParam(':cnpj', $cnpj);
        $stmt->bindParam(':num_controle', $num_controle);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function createOrUpdateContas($dados) {
        global $pdo;

        if (!is_array($dados) || count($dados) === 0) {
            return ['success' => false, 'message' => 'Nenhum dado fornecido.'];
        }

        // Adiciona created_at e updated_at aos dados
        $agora = date('Y-m-d H:i:s');
        foreach ($dados as &$registro) {
            $registro['created_at'] = $agora;
            $registro['updated_at'] = $agora;
        }

        // Lista de colunas (incluindo created_at e updated_at)
        $columns = array_keys($dados[0]);

        $placeholders = [];
        $params = [];

        foreach ($dados as $i => $row) {
            $rowPlaceholders = [];
            foreach ($columns as $col) {
                $key = ":{$col}_{$i}";
                $rowPlaceholders[] = $key;
                $params[$key] = $row[$col] ?? null;
            }
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }

        $sql = "INSERT INTO bi_conta (" . implode(', ', $columns) . ") VALUES "
            . implode(', ', $placeholders)
            . " ON DUPLICATE KEY UPDATE "
            . implode(', ', array_map(fn($col) => "$col = VALUES($col)", $columns));

        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return ['success' => true, 'message' => 'Contas BI inseridas/atualizadas com sucesso.'];
    }


    public static function createOrUpdateItens($dados)
    {
        global $pdo;


        if (!is_array($dados) || count($dados) === 0) {
            echo "Nenhum dado fornecido.\n";
            return ['success' => false, 'message' => 'Nenhum dado fornecido.'];
        }

        // Normaliza
        $dados = array_map(function($registro) {
            $registro = array_change_key_case($registro, CASE_LOWER);
            foreach ($registro as $k => $v) {
                if (is_string($v)) {
                    $registro[$k] = preg_replace('/\s+/', ' ', trim($v));
                }
            }
            return $registro;
        }, $dados);


        // Força sequencial como inteiro
        foreach ($dados as &$registro) {
            $registro['sequencial'] = (int) $registro['sequencial'];
        }
        unset($registro);

        // Timestamps
        $agora = date('Y-m-d H:i:s');
        foreach ($dados as &$registro) {
            $registro['created_at'] = $agora;
            $registro['updated_at'] = $agora;
        }
        unset($registro);


        // 1️⃣ Descobre quem já existe no banco
        $chavesExistentes = [];
        $placeholdersBusca = [];
        $paramsBusca = [];

        foreach ($dados as $i => $row) {
            $placeholdersBusca[] = "(cnpj_estabelecimento = :cnpj{$i} 
                                 AND num_controle = :num{$i} 
                                 AND nome_estabelecimento = :nome{$i} 
                                 AND sequencial = :seq{$i})";
            $paramsBusca[":cnpj{$i}"] = $row['cnpj_estabelecimento'];
            $paramsBusca[":num{$i}"]  = $row['num_controle'];
            $paramsBusca[":nome{$i}"] = $row['nome_estabelecimento'];
            $paramsBusca[":seq{$i}"]  = $row['sequencial'];
        }



        if (!empty($placeholdersBusca)) {
            $sqlBusca = "SELECT cnpj_estabelecimento, num_controle, nome_estabelecimento, sequencial
                     FROM bi_itens 
                     WHERE " . implode(" OR ", $placeholdersBusca);

            $stmtBusca = $pdo->prepare($sqlBusca);
            $stmtBusca->execute($paramsBusca);

            while ($row = $stmtBusca->fetch(PDO::FETCH_ASSOC)) {
                $chave = $row['cnpj_estabelecimento'] . '|' . $row['num_controle'] . '|' . $row['nome_estabelecimento'] . '|' . $row['sequencial'];
                $chavesExistentes[$chave] = true;
            }
        }


        // 2️⃣ Monta listas de inseridos e atualizados
        $chavesVistas = [];
        $listaInseridos = [];
        $listaAtualizados = [];

        foreach ($dados as $registro) {
            $chave = $registro['cnpj_estabelecimento'] . '|' .
                $registro['num_controle'] . '|' .
                $registro['nome_estabelecimento'] . '|' .
                $registro['sequencial'];

            $info = [
                'num_controle' => $registro['num_controle'],
                'sequencial'   => $registro['sequencial'],
                'dt_mov'       => $registro['dt_mov'] ?? null
            ];

            if (isset($chavesExistentes[$chave])) {
                $listaAtualizados[] = $info;
            } elseif (isset($chavesVistas[$chave])) {
                $listaAtualizados[] = $info;
            } else {
                $listaInseridos[] = $info;
                $chavesVistas[$chave] = true;
            }
        }

        // 3️⃣ Insere / atualiza
        $columns = array_keys($dados[0]);
        $placeholders = [];
        $params = [];

        foreach ($dados as $i => $row) {
            $rowPlaceholders = [];
            foreach ($columns as $col) {
                $key = ":{$col}_{$i}";
                $rowPlaceholders[] = $key;
                $params[$key] = $row[$col] ?? null;
            }
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }

        $sql = "INSERT INTO bi_itens (" . implode(', ', $columns) . ") VALUES "
            . implode(', ', $placeholders)
            . " ON DUPLICATE KEY UPDATE "
            . implode(', ', array_map(fn($col) => "$col = VALUES($col)", $columns));


        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            // Força sequencial como inteiro
            if (strpos($key, 'sequencial') !== false) {
                $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }

        $stmt->execute();

        // 4️⃣ Gera log formatado
        $logData = [
            'data_execucao'      => date('Y-m-d H:i:s'),
            'totalRecebido'      => count($dados),
            'inseridos'          => count($listaInseridos),
            'atualizados'        => count($listaAtualizados),
            'detalhes_inseridos' => $listaInseridos,
            'detalhes_atualizados' => $listaAtualizados
        ];


        return [
            'success' => true,
            'message' => 'Itens BI inseridos/atualizados com sucesso.',
            'log'     => $logData
        ];
    }








}
?>
