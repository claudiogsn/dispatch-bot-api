<?php
date_default_timezone_set('America/Rio_Branco');
require_once 'database/db.php';

class OrdersDeliveryController {

    public static function gerarChavePedido(): string {
        global $pdo;

        $letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        do {
            $chave = '';
            for ($i = 0; $i < 3; $i++) {
                $chave .= $letras[random_int(0, 25)];
                $chave .= random_int(0, 9);
            }
            $stmt = $pdo->prepare("SELECT 1 FROM orders_delivery WHERE chave_pedido = :chave LIMIT 1");
            $stmt->execute([':chave' => $chave]);
            $exists = $stmt->fetchColumn();
        } while ($exists);

        return $chave;
    }

    // Cria uma nova entrada na tabela orders_delivery
    public static function createOrderDelivery($data) {
        global $pdo;

        // Remove espaços em branco do início e do fim de cada valor
        $data = array_map('trim', $data);

        // Criar log da execução
        $logFile = __DIR__ . '/createOrder.log'; // Caminho do arquivo na mesma pasta
        $logMessage = "Recebido em " . date('Y-m-d H:i:s') . ":\n" .
            "DATA: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);

        // Extrair os 4 dígitos do campo identificador_conta se intg_tipo for "HUB-IFOOD"
        $cod_ifood = null;
        if ($data['intg_tipo'] === "HUB-IFOOD") {
            if (preg_match('/#(\d{4})/', $data['identificador_conta'], $matches)) {
                $cod_ifood = $matches[1];
            } else {
                // Log de erro se não conseguir extrair o código
                $errorLog = "Erro ao extrair cod_ifood do identificador_conta: " . $data['identificador_conta'] . "\n";
                file_put_contents($logFile, $errorLog, FILE_APPEND);
            }
        }

        $chave_pedido = self::gerarChavePedido();

        $query = "INSERT INTO orders_delivery (
            cnpj, hash, num_controle, status, modo_de_conta, identificador_conta,
            hora_abertura, hora_saida, intg_tipo, cod_iapp, tempo_preparo,
            status_pedido, quantidade_producao, quantidade_produzida,
            tipo_entrega, cod_ifood, chave_pedido
        ) VALUES (
            :cnpj, :hash, :num_controle, :status, :modo_de_conta, :identificador_conta,
            :hora_abertura, :hora_saida, :intg_tipo, :cod_iapp, :tempo_preparo,
            :status_pedido, :quantidade_producao, :quantidade_produzida,
            :tipo_entrega, :cod_ifood, :chave_pedido
        )";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':cnpj', $data['cnpj']);
        $stmt->bindParam(':hash', $data['hash']);
        $stmt->bindParam(':num_controle', $data['num_controle']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':modo_de_conta', $data['modo_de_conta']);
        $stmt->bindParam(':identificador_conta', $data['identificador_conta']);
        $stmt->bindParam(':hora_abertura', $data['hora_abertura']);
        $stmt->bindParam(':hora_saida', $data['hora_saida']);
        $stmt->bindParam(':intg_tipo', $data['intg_tipo']);
        $stmt->bindParam(':cod_iapp', $data['cod_iapp']);
        $stmt->bindParam(':tempo_preparo', $data['tempo_preparo']);
        $stmt->bindParam(':status_pedido', $data['status_pedido']);
        $stmt->bindParam(':quantidade_producao', $data['quantidade_producao']);
        $stmt->bindParam(':quantidade_produzida', $data['quantidade_produzida']);
        $stmt->bindParam(':tipo_entrega', $data['tipo_entrega']);
        $stmt->bindParam(':cod_ifood', $cod_ifood);
        $stmt->bindParam(':chave_pedido', $chave_pedido);

        $executed = $stmt->execute();

        // Log do resultado da execução
        $logResult = $executed ? "Inserção realizada com sucesso.\n\n" : "Falha na inserção.\n\n";
        file_put_contents($logFile, $logResult, FILE_APPEND);

        return $executed;
    }



    // Busca todos os pedidos
    public static function getAllOrderDeliveries() {
        global $pdo;

        $query = "SELECT * FROM orders_delivery";
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Busca um pedido pelo ID
    public static function getOrderDeliveryById($id) {
        global $pdo;

        $query = "SELECT * FROM orders_delivery WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Atualiza um pedido pelo ID
    public static function updateOrderDelivery($id, $data) {
        global $pdo;

        $query = "UPDATE orders_delivery SET
                  cnpj = :cnpj, hash = :hash, num_controle = :num_controle, status = :status, 
                  modo_de_conta = :modo_de_conta, identificador_conta = :identificador_conta, 
                  hora_abertura = :hora_abertura, hora_saida = :hora_saida, intg_tipo = :intg_tipo, 
                  cod_iapp = :cod_iapp, tempo_preparo = :tempo_preparo, status_pedido = :status_pedido
                  WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':cnpj', $data['cnpj']);
        $stmt->bindParam(':hash', $data['hash']);
        $stmt->bindParam(':num_controle', $data['num_controle']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':modo_de_conta', $data['modo_de_conta']);
        $stmt->bindParam(':identificador_conta', $data['identificador_conta']);
        $stmt->bindParam(':hora_abertura', $data['hora_abertura']);
        $stmt->bindParam(':hora_saida', $data['hora_saida']);
        $stmt->bindParam(':intg_tipo', $data['intg_tipo']);
        $stmt->bindParam(':cod_iapp', $data['cod_iapp']);
        $stmt->bindParam(':tempo_preparo', $data['tempo_preparo']);
        $stmt->bindParam(':status_pedido', $data['status_pedido']);

        return $stmt->execute();
    }

    // Deleta um pedido pelo ID
    public static function deleteOrderDelivery($id) {
        global $pdo;

        $query = "DELETE FROM orders_delivery WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }


    public static function updateOrderDeliveryByCompositeKey($cnpj, $hash, $num_controle, $data) {
        global $pdo;



        // Aplicar trim a todos os elementos do array $data
        $data = array_map('trim', $data);

        // Criar log da execução
        $logFile = __DIR__ . '/updateOrderDelivery.log'; // Caminho do arquivo na mesma pasta
        $logMessage = "Recebido em " . date('Y-m-d H:i:s') . ":\n" .
            "CNPJ: $cnpj\n" .
            "HASH: $hash\n" .
            "NUM_CONTROLE: $num_controle\n" .
            "DATA: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);



        // Recuperar o ID do pedido
        $queryFindId = "SELECT id FROM orders_delivery WHERE cnpj = :cnpj AND hash = :hash AND num_controle = :num_controle";
        $stmt = $pdo->prepare($queryFindId);
        $stmt->bindParam(':cnpj', $cnpj);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':num_controle', $num_controle);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            file_put_contents($logFile, "Pedido não encontrado.\n\n", FILE_APPEND);
            return false;  // Pedido não encontrado
        }

        $id = $result['id'];

        // Realiza o update usando o ID
        $queryUpdate = "UPDATE orders_delivery SET
                    status = :status, modo_de_conta = :modo_de_conta, identificador_conta = :identificador_conta, 
                    hora_abertura = :hora_abertura, hora_saida = :hora_saida, intg_tipo = :intg_tipo, 
                    cod_iapp = :cod_iapp, tempo_preparo = :tempo_preparo, status_pedido = :status_pedido, 
                    quantidade_producao = :quantidade_producao, quantidade_produzida = :quantidade_produzida, tipo_entrega = :tipo_entrega
                    WHERE id = :id";
        $stmtUpdate = $pdo->prepare($queryUpdate);
        $stmtUpdate->bindParam(':id', $id);
        $stmtUpdate->bindParam(':status', $data['status']);
        $stmtUpdate->bindParam(':modo_de_conta', $data['modo_de_conta']);
        $stmtUpdate->bindParam(':identificador_conta', $data['identificador_conta']);
        $stmtUpdate->bindParam(':hora_abertura', $data['hora_abertura']);
        $stmtUpdate->bindParam(':hora_saida', $data['hora_saida']);
        $stmtUpdate->bindParam(':intg_tipo', $data['intg_tipo']);
        $stmtUpdate->bindParam(':cod_iapp', $data['cod_iapp']);
        $stmtUpdate->bindParam(':tempo_preparo', $data['tempo_preparo']);
        $stmtUpdate->bindParam(':status_pedido', $data['status_pedido']);
        $stmtUpdate->bindParam(':quantidade_producao', $data['quantidade_producao']);
        $stmtUpdate->bindParam(':quantidade_produzida', $data['quantidade_produzida']);
        $stmtUpdate->bindParam(':tipo_entrega', $data['tipo_entrega']);

        $executed = $stmtUpdate->execute();

        // Log do resultado da execução
        $logResult = $executed ? "Atualização realizada com sucesso.\n\n" : "Falha na atualização.\n\n";
        file_put_contents($logFile, $logResult, FILE_APPEND);

        return $executed;
    }


    public static function getOrderDeliveryByCompositeKey($cnpj, $hash, $num_controle) {
        global $pdo;

        $query = "SELECT * FROM orders_delivery WHERE cnpj = :cnpj AND hash = :hash AND num_controle = :num_controle";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':cnpj', $cnpj);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':num_controle', $num_controle);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            http_response_code(404);
            return false;
        }

        return array_map('trim', $result);
    }

    public static function getOrdersDeliveryByPeriod($start, $end) {
        global $pdo;

        // Validações básicas
        if (!$start || !$end) {
            return ['error' => 'Missing required fields for getOrdersByPeriod.'];
        }

        if ($start > $end) {
            return ['error' => 'Invalid date range.'];
        }

        if (strtotime($start) === false || strtotime($end) === false) {
            return ['error' => 'Invalid date format.'];
        }

        // Consulta otimizada com UNION ALL
        $query = "
        -- Parte HUB-IFOOD
        SELECT 
            od.*,
            COALESCE(op.link_rastreio_pedido, 'Sem registro') AS link_rastreio_pedido,
            COALESCE(op.solicitacao_id, 'Sem registro')       AS solicitacao_id,
            COALESCE(op.id_parada, 'Sem registro')            AS id_parada
        FROM orders_delivery od
        LEFT JOIN orders_paradas op 
            ON od.cod_ifood = op.numero_pedido
            AND EXISTS (
                SELECT 1
                FROM orders_solicitacoes os
                WHERE os.solicitacao_id = op.solicitacao_id
                  AND os.data_hora_solicitacao >= DATE(od.hora_abertura)
                  AND os.data_hora_solicitacao < DATE(od.hora_abertura + INTERVAL 1 DAY)
            )
        WHERE od.intg_tipo = 'HUB-IFOOD'
          AND od.cod_ifood IS NOT NULL
          AND od.cod_ifood != ''
          AND od.status IN (1, -1, 2)
          AND od.hora_abertura BETWEEN :start AND :end

        UNION ALL

        -- Parte NÃO HUB-IFOOD
        SELECT 
            od.*,
            COALESCE(op.link_rastreio_pedido, 'Sem registro') AS link_rastreio_pedido,
            COALESCE(op.solicitacao_id, 'Sem registro')       AS solicitacao_id,
            COALESCE(op.id_parada, 'Sem registro')            AS id_parada
        FROM orders_delivery od
        LEFT JOIN orders_paradas op 
            ON od.cod_iapp = op.numero_pedido
        WHERE od.intg_tipo != 'HUB-IFOOD'
          AND od.cod_iapp IS NOT NULL
          AND od.cod_iapp != ''
          AND od.status IN (1, -1, 2)
          AND od.hora_abertura BETWEEN :start AND :end

        ORDER BY hora_saida DESC;
    ";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':start', $start);
        $stmt->bindParam(':end', $end);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    public static function calculateTimesByCompositeKey($cnpj, $hash, $num_controle) {
        global $pdo;

        $query = "SELECT hora_abertura, hora_saida, tempo_preparo FROM orders_delivery WHERE cnpj = :cnpj AND hash = :hash AND num_controle = :num_controle";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':cnpj', $cnpj);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':num_controle', $num_controle);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            http_response_code(404);
            return false;
        }

        $hora_abertura = strtotime($result['hora_abertura']);
        $hora_saida = ($result['hora_saida'] !== '0000-00-00 00:00:00') ? strtotime($result['hora_saida']) : null;
        $tempo_preparo = ($result['tempo_preparo'] !== '0000-00-00 00:00:00') ? strtotime($result['tempo_preparo']) : null;

        if ($hora_abertura === false) {
            return array('error' => 'Invalid date format for hora_abertura.');
        }

        $response = array();

        if ($hora_saida !== null) {
            if ($hora_saida === false) {
                return array('error' => 'Invalid date format for hora_saida.');
            }
            $response['dispatch_time'] = ($hora_saida - $tempo_preparo) / 60;
        } else {
            $response['dispatch_time'] = 'Order has not been dispatched yet.';
        }

        if ($tempo_preparo !== null) {
            if ($tempo_preparo === false) {
                return array('error' => 'Invalid date format for tempo_preparo.');
            }
            $response['preparation_time'] = ($tempo_preparo - $hora_abertura) / 60;
        } else {
            $response['preparation_time'] = 'Order is still in preparation.';
        }

        $response['total_time'] = ($hora_saida - $hora_abertura) / 60;

        return $response;
    }

    public static function getOrdersDeliveryByPeriodMock($start, $end) {
        header('Content-Type: application/json');

        // Caminho para o arquivo JSON
        $jsonFilePath = __DIR__ . '/../data/data.json';

        // Verifica se o arquivo existe
        if (!file_exists($jsonFilePath)) {
            http_response_code(404);
            echo json_encode(array('error' => 'JSON file not found.'));
            exit;
        }

        // Lê o conteúdo do arquivo JSON
        $jsonContent = file_get_contents($jsonFilePath);

        $jsonArray = json_decode($jsonContent, true);

        // Verifica se a leitura do arquivo foi bem-sucedida
        if ($jsonContent === false) {
            http_response_code(500);
            echo json_encode(array('error' => 'Failed to read JSON file.'));
            exit;
        }

        // Retorna o conteúdo do JSON
        return $jsonArray;
    }

    public static function getOrdersChartData($start, $end) {
        global $pdo;

        // Validação dos parâmetros de entrada
        if (!$start || !$end) {
            http_response_code(400);
            return array('error' => 'Missing required fields: start and end.');
        }

        if (strtotime($start) === false || strtotime($end) === false) {
            http_response_code(400);
            return array('error' => 'Invalid date format.');
        }

        if ($start > $end) {
            http_response_code(400);
            return array('error' => 'Start date must be before end date.');
        }

        // Consulta para buscar os dados dos pedidos no período especificado
        $query = "
        SELECT o.cnpj, o.hora_abertura, e.nome_fantasia 
        FROM orders_delivery o
        JOIN estabelecimento e ON o.cnpj = e.cnpj
        WHERE o.hora_abertura BETWEEN :start AND :end
    ";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':start', $start);
        $stmt->bindParam(':end', $end);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Inicializa os contadores de pedidos por hora e por CNPJ
        $dataByCNPJ = [];

        foreach ($orders as $order) {
            $cnpj = $order['cnpj'];
            $nomeFantasia = $order['nome_fantasia'];
            $horaAbertura = strtotime($order['hora_abertura']);
            $hour = (int) date('H', $horaAbertura);

            // Contar pedidos por CNPJ e hora de abertura
            if ($hour >= 12 && $hour < 24) {
                $index = $hour - 12;

                if (!isset($dataByCNPJ[$nomeFantasia])) {
                    $dataByCNPJ[$nomeFantasia] = array_fill(0, 12, 0);
                }
                $dataByCNPJ[$nomeFantasia][$index]++;
            }
        }

        // Formatação dos dados para o gráfico
        $series = [];
        foreach ($dataByCNPJ as $nomeFantasia => $data) {
            $series[] = [
                'name' => $nomeFantasia,
                'data' => $data
            ];
        }

        return $series;
    }


    public static function getDeliveryInfoByNumeroParada($numero_parada) {
        global $pdo;

        $query = "
        SELECT 
            op.endereco, 
            op.complemento, 
            op.bairro, 
            op.cidade, 
            op.uf, 
            op.link_rastreio_pedido, 
            os.nome_taxista, 
            os.telefone_taxista, 
            os.veiculo, 
            os.placa_veiculo, 
            os.cor_veiculo, 
            os.data_hora_solicitacao, 
            os.data_hora_chegada_local 
        FROM 
            orders_paradas op
        LEFT JOIN 
            orders_solicitacoes os 
        ON 
            op.solicitacao_id = os.solicitacao_id
        WHERE 
            op.id_parada = :numero_parada
    ";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':numero_parada', $numero_parada);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            http_response_code(404);
            return ['error' => 'Parada não encontrada.'];
        }

        return $result;
    }

    public static function changeStatusPedido ($cnpj, $hash, $num_controle, $status_pedido) {
        global $pdo;

        $query = "UPDATE orders_delivery SET status_pedido = :status_pedido WHERE cnpj = :cnpj AND hash = :hash AND num_controle = :num_controle";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':cnpj', $cnpj);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':num_controle', $num_controle);
        $stmt->bindParam(':status_pedido', $status_pedido);

        return $stmt->execute();
    }

    public static function getPedidoByChave($chave_pedido)
    {
        global $pdo;

        $query = "
        SELECT 
            od.*,
            wm.identificador_conta AS nome_cliente,
            od.telefone
        FROM orders_delivery od
        LEFT JOIN whatsapp_mensages wm ON wm.chave_pedido = od.chave_pedido
        WHERE od.chave_pedido = :chave_pedido
        LIMIT 1
    ";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':chave_pedido', $chave_pedido);
        $stmt->execute();

        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pedido) {
            if (!empty($pedido['telefone'])) {
                $pedido['telefone'] = self::formatarTelefone($pedido['telefone']);
            }

            if (!empty($pedido['hora_abertura']) && $pedido['hora_abertura'] !== '0000-00-00 00:00:00') {
                $pedido['hora_abertura'] = self::formatarDataHora($pedido['hora_abertura']);
            }
        }

        return $pedido;
    }

    public static function formatarTelefone($telefone)
    {
        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($telefone) === 11) {
            return sprintf("(%s) %s-%s",
                substr($telefone, 0, 2),
                substr($telefone, 2, 5),
                substr($telefone, 7)
            );
        }
        return $telefone;
    }

    public static function formatarDataHora($data)
    {
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $data);
        return $dt ? $dt->format('d/m/Y H:i') : $data;
    }



    public static function marcarNpsComoRespondido($chave_pedido): array
    {
        global $pdo;

        if (!$chave_pedido) {
            throw new Exception("Campo 'chave_pedido' é obrigatório.");
        }

        $query = "UPDATE orders_delivery SET nps = 1 WHERE chave_pedido = :chave_pedido";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':chave_pedido', $chave_pedido);
        $stmt->execute();

        return ['updated' => $stmt->rowCount()];
    }

    public static function ListarPedidosPorTelefone($telefone)
    {
        global $pdo;

        if (!$telefone) {
            http_response_code(400);
            return ['success' => false, 'error' => 'Telefone é obrigatório.'];
        }

        try {
            $stmt = $pdo->prepare("
            SELECT 
                od.chave_pedido,
                od.identificador_conta,
                od.hora_abertura,
                od.cnpj,
                od.telefone,
                (SELECT e.nome_fantasia 
                 FROM estabelecimento e 
                 WHERE e.cnpj = od.cnpj 
                 LIMIT 1) AS nome_loja,
                EXISTS (
                    SELECT 1 
                    FROM formulario_respostas fr 
                    WHERE fr.chave_pedido = od.chave_pedido
                    LIMIT 1
                ) AS respondeu
            FROM orders_delivery od
            WHERE od.telefone = :telefone
            ORDER BY od.hora_abertura DESC
        ");

            $stmt->execute([':telefone' => $telefone]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Aplica a formatação no nome_cliente
            $dados = array_map(function ($row) {
                return [
                    'chave_pedido' => $row['chave_pedido'],
                    'nome_cliente'  => self::formatarNomeCliente($row['identificador_conta']),
                    'hora_abertura' => self::formatarDataHora($row['hora_abertura']),
                    'nome_loja'     => $row['nome_loja'],
                    'respondeu'     => (bool)$row['respondeu']
                ];
            }, $rows);

            return [
                'success' => true,
                'data' => $dados
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


    public static function formatarNomeCliente($nome)
    {
        return ucwords(trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-zA-ZÀ-ÿ\s]/u', '', $nome))));
    }

    public static function GetDetalhesDoPedido($chave_pedido): array
    {
        global $pdo;

        if (!$chave_pedido) {
            return ['success' => false, 'error' => 'Chave do pedido não fornecida.'];
        }

        try {
            // 1. Busca os dados do pedido (matriz)
            $stmtPedido = $pdo->prepare("
            SELECT 
                od.*, 
                e.nome_fantasia AS nome_loja
            FROM orders_delivery od
            LEFT JOIN estabelecimento e ON e.cnpj = od.cnpj
            WHERE od.chave_pedido = :chave
            LIMIT 1;
        ");
            $stmtPedido->execute([':chave' => $chave_pedido]);
            $pedido = $stmtPedido->fetch(PDO::FETCH_ASSOC);

            if (!$pedido) {
                return ['success' => false, 'error' => 'Pedido não encontrado.'];
            }

            // 2. Formulário de respostas
            $stmtRespostas = $pdo->prepare("
            SELECT 
                r.pergunta_id,
                p.titulo AS pergunta,
                r.resposta,
                r.created_at,
                r.latitude,
                r.longitude,
                r.ip,
                r.user_agent,
                r.tipo_dispositivo,
                r.plataforma
            FROM formulario_respostas r
            LEFT JOIN formulario_perguntas p ON p.id = r.pergunta_id
            WHERE r.chave_pedido = :chave
        ");
            $stmtRespostas->execute([':chave' => $chave_pedido]);
            $respostas = $stmtRespostas->fetchAll(PDO::FETCH_ASSOC);

            // 3. WhatsApp (mensagem enviada)
            $stmtWhatsApp = $pdo->prepare("
            SELECT * FROM whatsapp_mensages WHERE chave_pedido = :chave ORDER BY created_at DESC LIMIT 1
        ");
            $stmtWhatsApp->execute([':chave' => $chave_pedido]);
            $mensagemWhatsApp = $stmtWhatsApp->fetch(PDO::FETCH_ASSOC);

            // 4. NPS (mensagem NPS enviada)
            $stmtNps = $pdo->prepare("
            SELECT * FROM mensagens_nps WHERE chave_pedido = :chave ORDER BY created_at DESC LIMIT 1
        ");
            $stmtNps->execute([':chave' => $chave_pedido]);
            $mensagemNps = $stmtNps->fetch(PDO::FETCH_ASSOC);

            // 5. Paradas (via cod_iapp ou cod_ifood)
            $paradas = [];
            if ($pedido['intg_tipo'] === 'DELIVERY-DIRETO' && $pedido['cod_iapp']) {
                $stmtParadas = $pdo->prepare("SELECT * FROM orders_paradas WHERE numero_pedido = :cod");
                $stmtParadas->execute([':cod' => $pedido['cod_iapp']]);
                $paradas = $stmtParadas->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($pedido['intg_tipo'] === 'HUB-IFOOD' && $pedido['cod_ifood']) {
                $stmtParadas = $pdo->prepare("SELECT * FROM orders_paradas WHERE numero_pedido = :cod");
                $stmtParadas->execute([':cod' => $pedido['cod_ifood']]);
                $paradas = $stmtParadas->fetchAll(PDO::FETCH_ASSOC);
            }

            // 6. Itens do pedido (bi_itens)
            $stmtItens = $pdo->prepare("
            SELECT * 
            FROM bi_itens 
            WHERE cnpj_estabelecimento = :cnpj AND num_controle = :num_controle
        ");
            $stmtItens->execute([
                ':cnpj' => $pedido['cnpj'],
                ':num_controle' => $pedido['num_controle']
            ]);
            $itensPedido = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'pedido' => $pedido,
                'respostas' => $respostas,
                'whatsapp_mensagem' => $mensagemWhatsApp,
                'mensagem_nps' => $mensagemNps,
                'paradas' => $paradas,
                'itens_pedido' => $itensPedido
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return ['success' => false, 'error' => 'Erro ao buscar detalhes: ' . $e->getMessage()];
        }
    }

    public static function GetDetalhesMesa($chave_pedido, $timestamp): array
    {
        global $pdo;

        if (!$chave_pedido) {
            return ['success' => false, 'error' => 'Chave da mesa não fornecida.'];
        }
        if (!$timestamp) {
            return ['success' => false, 'error' => 'Timestamp não fornecido.'];
        }

        try {
            $stmtRespostas = $pdo->prepare("
            SELECT
                r.pergunta_id,
                p.titulo AS pergunta,
                r.resposta,
                r.created_at,
                r.latitude,
                r.longitude,
                r.ip,
                r.user_agent,
                r.tipo_dispositivo,
                r.plataforma
            FROM formulario_respostas r
            LEFT JOIN formulario_perguntas p ON p.id = r.pergunta_id
            WHERE r.chave_pedido = :chave
              AND r.created_at = :timestamp
              AND r.modo_venda = 'MESA'
        ");

            $stmtRespostas->execute([
                ':chave'     => $chave_pedido,
                ':timestamp' => $timestamp,
            ]);

            $respostas = $stmtRespostas->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success'   => true,
                'respostas' => $respostas
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return ['success' => false, 'error' => 'Erro ao buscar respostas: ' . $e->getMessage()];
        }
    }







}


