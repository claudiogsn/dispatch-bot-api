<?php

require_once __DIR__ . '/../database/db.php'; // Caminho para a conexão com o banco de dados
require_once __DIR__ . '/../controllers/LogglyLogger.php'; // Caminho para o Logger

// Configuração do timezone
date_default_timezone_set('America/Rio_Branco');

// Função para consultar o banco e retornar os solicitacao_id sem link de rastreio
function fetchSolicitacoesSemRastreio($pdo) {
    try {
        echo "Consultando solicitações para o DECK BURGER & CHURRAS sem link de rastreio das últimas 2 horas...\n";

        // Calcular o horário de 2 horas atrás em America/Rio_Branco
        $horaAtual = new DateTime('now', new DateTimeZone('America/Rio_Branco')); // Timezone configurado
        $horaLimite = $horaAtual->modify('-2 hours')->format('Y-m-d H:i:s');

        $sql = "
            SELECT s.solicitacao_id
            FROM orders_solicitacoes s
            JOIN orders_paradas p ON s.solicitacao_id = p.solicitacao_id
            WHERE p.link_rastreio_pedido IS NULL
            AND s.empresa_id = 41815
            AND s.data_hora_solicitacao >= :hora_limite
            GROUP BY s.solicitacao_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':hora_limite' => $horaLimite]);
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo count($result) . " solicitações encontradas.\n";
        return $result;
    } catch (PDOException $e) {
        throw new Exception("Erro ao consultar solicitações no banco de dados: " . $e->getMessage());
    }
}

// Função para buscar links de rastreio na API
function fetchLinksFromAPI($solicitacao_id) {
    echo "Chamando a API para obter links de rastreio para solicitacao_id: $solicitacao_id...\n";

    $api_url = "https://cloud.taximachine.com.br/api/integracao/obterLinkRastreio/$solicitacao_id";
    $api_key = "mch_api_h2CcjBndaZsjZGgluznxn5FA";
    $username = "ti@vemprodeck.com.br";
    $password = "S3t1c@2013";

    $options = [
        "http" => [
            "header" => [
                "Authorization: Basic " . base64_encode("$username:$password"),
                "api-key: $api_key"
            ]
        ]
    ];

    $context = stream_context_create($options);

    try {
        $response = file_get_contents($api_url, false, $context);
        echo "Resposta da API recebida para $solicitacao_id.\n";
        return json_decode($response, true);
    } catch (Exception $e) {
        throw new Exception("Erro ao realizar requisição na API: " . $e->getMessage());
    }
}
function logPayload($payload) {
    $logFile = __DIR__ . '/whatsapp_payloads.log'; // Caminho do arquivo de log
    $timestamp = date('Y-m-d H:i:s'); // Timestamp para registro

    // Formatar o log
    $logEntry = "[$timestamp] " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";

    // Escrever no arquivo
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Metodo para buscar informações e enviar o request
function sendWhatsapp($pdo, $parada_id, $cod, $link_rastreio) {
    try {
        // Buscar identificador_conta e telefone
        $sqlIdentificador = "SELECT TRIM(SUBSTRING(identificador_conta, 13)) AS identificador_conta, concat('55',telefone) as telefone FROM orders_delivery WHERE cod_iapp = :cod";
        $stmtIdentificador = $pdo->prepare($sqlIdentificador);
        $stmtIdentificador->execute([':cod' => $cod]);
        $identificadorData = $stmtIdentificador->fetch(PDO::FETCH_ASSOC);

        if (!$identificadorData) {
            echo "Nenhuma informação encontrada para cod_iapp: $cod.\n";
            return;
        }

        $identificador_conta = $identificadorData['identificador_conta'];
        $telefone = $identificadorData['telefone'];

        // Buscar solicitacao_id
        $sqlSolicitacao = "SELECT solicitacao_id FROM orders_paradas WHERE id_parada = :parada_id";
        $stmtSolicitacao = $pdo->prepare($sqlSolicitacao);
        $stmtSolicitacao->execute([':parada_id' => $parada_id]);
        $solicitacao_id = $stmtSolicitacao->fetchColumn();

        if (!$solicitacao_id) {
            echo "Nenhum solicitacao_id encontrado para parada_id: $parada_id.\n";
            return;
        }

        // Buscar placa_veiculo e nome_taxista
        $sqlDetalhes = "SELECT placa_veiculo, nome_taxista FROM orders_solicitacoes WHERE solicitacao_id = :solicitacao_id";
        $stmtDetalhes = $pdo->prepare($sqlDetalhes);
        $stmtDetalhes->execute([':solicitacao_id' => $solicitacao_id]);
        $detalhes = $stmtDetalhes->fetch(PDO::FETCH_ASSOC);

        if (!$detalhes) {
            echo "Nenhuma informação encontrada para solicitacao_id: $solicitacao_id.\n";
            return;
        }

        $placa_veiculo = $detalhes['placa_veiculo'];
        $nome_taxista = $detalhes['nome_taxista'];

        // Enviar o request
        echo "Enviando request com as informações obtidas...\n";
        $api_url = "https://api.z-api.io/instances/3DF712E49DF860A86AD80A1EFCACDE10/token/A22B3AAD2C11A72646680264/send-text";
        $api_key = "F00ff92c2022b4ed290e5b6e70f36b308S";

        $mensagem = <<<EOT
        🚨 Notícia boa! {$identificador_conta}, seu pedido {$cod} já está em rota de entrega!
        Nosso motoboy {$nome_taxista} de placa {$placa_veiculo} pode ser acompanhado em tempo real pelo link: {$link_rastreio}
        Estamos chegando, até já! 😊
        Esta mensagem é automática e não deve ser respondida.
EOT;

        $payload = [
            [
                "phone" => $telefone,
                "message" => $mensagem
            ]
        ];

        // Logar o payload
        logPayload($payload);

        $options = [
            "http" => [
                "header" => [
                    "Content-Type: application/json",
                    "Client-Token: $api_key"
                ],
                "method" => "POST",
                "content" => json_encode($payload)
            ]
        ];

        $context = stream_context_create($options);

        try {
            $response = file_get_contents($api_url, false, $context);
            echo "Request enviado com sucesso.\n";
        } catch (Exception $e) {
            echo "Erro ao enviar request: " . $e->getMessage() . "\n";
        }
    } catch (Exception $e) {
        echo "Erro ao buscar informações: " . $e->getMessage() . "\n";
    }
}

// Função para atualizar o banco com os links de rastreio e alterar o status do pedido
function updateParadas($pdo, $parada_id, $link_rastreio_pedido) {
    echo "Atualizando parada_id: $parada_id com link: $link_rastreio_pedido...\n";
    try {
        $todayZeroed = date('Y-m-d 00:00:00');
        $hora_saida = date('Y-m-d H:i:s');

        // Atualizar o link na tabela orders_paradas
        $sql = "UPDATE orders_paradas SET link_rastreio_pedido = :link_rastreio_pedido WHERE id_parada = :parada_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':link_rastreio_pedido' => $link_rastreio_pedido,
            ':parada_id' => $parada_id
        ]);

        echo "Parada atualizada com sucesso.\n";

        // Determinar se o numero_pedido tem 4 dígitos
        $sqlCheckNumeroPedido = "SELECT numero_pedido FROM orders_paradas WHERE id_parada = :parada_id LIMIT 1";
        $stmtCheck = $pdo->prepare($sqlCheckNumeroPedido);
        $stmtCheck->execute([':parada_id' => $parada_id]);
        $numero_pedido = $stmtCheck->fetchColumn();

        if ($numero_pedido && preg_match('/^\d{4}$/', $numero_pedido)) {
            // Se o numero_pedido tem 4 dígitos, usar cod_ifood
            $sqlFetchCod = "
                SELECT d.cod_ifood
                FROM orders_paradas p
                JOIN orders_delivery d ON p.numero_pedido = d.cod_ifood
                WHERE p.id_parada = :parada_id
                AND d.hora_abertura > :todayZeroed
                LIMIT 1
            ";
        } else {
            // Caso contrário, usar cod_iapp
            $sqlFetchCod = "
                SELECT d.cod_iapp
                FROM orders_paradas p
                JOIN orders_delivery d ON p.numero_pedido = d.cod_iapp
                WHERE p.id_parada = :parada_id
                AND d.hora_abertura > :todayZeroed
                LIMIT 1
            ";
        }

        $stmtFetch = $pdo->prepare($sqlFetchCod);
        $stmtFetch->execute([':parada_id' => $parada_id, ':todayZeroed' => $todayZeroed]);
        $cod = $stmtFetch->fetchColumn();

        if ($cod) {
            // Verificar o comprimento do código para decidir qual coluna usar no UPDATE
            $column = (strlen($cod) === 4) ? 'cod_ifood' : 'cod_iapp';

            // Atualizar o status_pedido na tabela orders_delivery
            $sqlUpdateDelivery = "UPDATE orders_delivery SET status_pedido = 'PEDIDO DESPACHADO', hora_saida = :hora_saida  WHERE $column = :cod AND hora_abertura > :todayZeroed";
            $stmtUpdate = $pdo->prepare($sqlUpdateDelivery);
            $stmtUpdate->execute([':cod' => $cod, ':todayZeroed' => $todayZeroed, ':hora_saida' => $hora_saida]);

            echo "Status do pedido atualizado para 'PEDIDO DESPACHADO' para cod: $cod.\n";

            if ($column === 'cod_iapp') {
                sendWhatsapp($pdo, $parada_id, $cod, $link_rastreio_pedido);
            }

        } else {
            echo "Nenhum código correspondente encontrado para parada_id: $parada_id.\n";
        }
    } catch (PDOException $e) {
        throw new Exception("Erro ao atualizar parada $parada_id no banco: " . $e->getMessage());
    }
}

// Instancia o logger
$logger = new LogglyLogger();

try {
    // Obter solicitações sem links de rastreio
    global $pdo;
    $solicitacoes = fetchSolicitacoesSemRastreio($pdo);

    foreach ($solicitacoes as $solicitacao_id) {
        try {
            $api_response = fetchLinksFromAPI($solicitacao_id);

            if ($api_response['success'] && isset($api_response['response'])) {
                foreach ($api_response['response'] as $parada) {
                    $parada_id = $parada['parada_id'];
                    $link_rastreio = $parada['link_rastreio'];

                    try {
                        updateParadas($pdo, $parada_id, $link_rastreio);
                        $logger->sendLog("LINK RASTREIO BOT - Adicionado link: $link_rastreio à parada: $parada_id");
                    } catch (Exception $e) {
                        echo "Erro ao atualizar parada $parada_id: " . $e->getMessage() . "\n";
                        $logger->sendLog("Erro ao atualizar parada: $parada_id - " . $e->getMessage(), 'ERROR');
                    }
                }
            } else {
                $error_msg = $api_response['error'] ?? 'Erro desconhecido na API';
                echo "Erro ao buscar links para solicitacao_id: $solicitacao_id - $error_msg\n";
                $logger->sendLog("Erro ao buscar links para solicitacao_id: $solicitacao_id - $error_msg", 'ERROR');
            }
        } catch (Exception $e) {
            echo "Erro ao processar solicitacao_id: $solicitacao_id - " . $e->getMessage() . "\n";
            $logger->sendLog("Erro ao processar solicitacao_id: $solicitacao_id - " . $e->getMessage(), 'ERROR');
        }
    }

    echo "Processo concluído com sucesso!\n";
} catch (Exception $e) {
    echo "Erro crítico no processamento: " . $e->getMessage() . "\n";
    $logger->sendLog("Erro crítico no processamento: " . $e->getMessage(), 'ERROR');
    echo "Erro durante o processamento. Verifique os logs para mais detalhes.\n";
}
