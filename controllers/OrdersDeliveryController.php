<?php
date_default_timezone_set('America/Rio_Branco');
require_once 'database/db.php';

class OrdersDeliveryController {
    // Cria uma nova entrada na tabela orders_delivery
    public static function createOrderDelivery($data) {
        global $pdo;

        $query = "INSERT INTO orders_delivery (cnpj, hash, num_controle, status, modo_de_conta, identificador_conta, hora_abertura, hora_saida, intg_tipo, cod_iapp, tempo_preparo, status_pedido, quantidade_producao, quantidade_produzida, tipo_entrega)
                  VALUES (:cnpj, :hash, :num_controle, :status, :modo_de_conta, :identificador_conta, :hora_abertura, :hora_saida, :intg_tipo, :cod_iapp, :tempo_preparo, :status_pedido, :quantidade_producao, :quantidade_produzida, :tipo_entrega)";
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

        return $stmt->execute();
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

        // recuperar o ID do pedido
        $queryFindId = "SELECT id FROM orders_delivery WHERE cnpj = :cnpj AND hash = :hash AND num_controle = :num_controle";
        $stmt = $pdo->prepare($queryFindId);
        $stmt->bindParam(':cnpj', $cnpj);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':num_controle', $num_controle);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return false;  // Pedido não encontrado
        }

        $id = $result['id'];

        // realiza o update usando o ID
        $queryUpdate = "UPDATE orders_delivery SET
                        status = :status, modo_de_conta = :modo_de_conta, identificador_conta = :identificador_conta, 
                        hora_abertura = :hora_abertura, hora_saida = :hora_saida, intg_tipo = :intg_tipo, 
                        cod_iapp = :cod_iapp, tempo_preparo = :tempo_preparo, status_pedido = :status_pedido, 
                        quantidade_producao = :quantidade_producao, quantidade_produzida = :quantidade_produzida,tipo_entrega = :tipo_entrega
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



        return $stmtUpdate->execute();
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

        return $result;
    }

    public static function getOrdersDeliveryByPeriod($start, $end) {
        global $pdo;

        if (!$start || !$end) {

            return array('error' => 'Missing required fields for getOrdersByPeriod.');
        }

        if ($start > $end) {
            return array('error' => 'Invalid date range.');
        }

        if (strtotime($start) === false || strtotime($end) === false) {
            return array('error' => 'Invalid date format.');
        }

        $query = "SELECT 
             od.*,
            COALESCE(op.link_rastreio_pedido, 'Sem registro') AS link_rastreio_pedido,
            COALESCE(op.solicitacao_id, 'Sem registro') AS solicitacao_id,
            COALESCE(op.id_parada, 'Sem registro') AS id_parada
        FROM 
            orders_delivery od
        LEFT JOIN 
            orders_paradas op
        ON 
            od.cod_iapp IS NOT NULL AND od.cod_iapp != '' AND od.cod_iapp = op.numero_pedido
        WHERE 
            od.status IN (1, -1, 2) 
            AND od.hora_abertura >= :start 
            AND od.hora_abertura <= :end
        ORDER BY 
            od.hora_saida DESC;
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








}


