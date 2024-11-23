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

// Função para atualizar o banco com os links de rastreio
function updateParadas($pdo, $parada_id, $link_rastreio_pedido) {
    echo "Atualizando parada_id: $parada_id com link: $link_rastreio_pedido...\n";
    try {
        $sql = "UPDATE orders_paradas SET link_rastreio_pedido = :link_rastreio_pedido WHERE id_parada = :parada_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':link_rastreio_pedido' => $link_rastreio_pedido,
            ':parada_id' => $parada_id
        ]);
        echo "Parada atualizada com sucesso.\n";
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
