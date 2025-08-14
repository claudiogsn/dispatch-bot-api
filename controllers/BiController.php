<?php
date_default_timezone_set('America/Rio_Branco');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db.php';

class BIController {

    public static function getContasBi($data_inicial, $data_final) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM bi_conta WHERE dt_mov BETWEEN :data_inicial AND :data_final ');
        $stmt->bindParam(':data_inicial', $data_inicial);
        $stmt->bindParam(':data_final', $data_final);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getItensBi($data_inicial, $data_final) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM bi_itens WHERE dt_mov BETWEEN :data_inicial AND :data_final');
        $stmt->bindParam(':data_inicial', $data_inicial);
        $stmt->bindParam(':data_final', $data_final);
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
