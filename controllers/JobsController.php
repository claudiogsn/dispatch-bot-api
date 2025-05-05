<?php
date_default_timezone_set('America/Rio_Branco');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../controllers/LogglyLogger.php';

class JobsController
{
    public static function fetchSolicitacoesSemRastreio($empresa_id): array
    {
        global $pdo;

        try {
            $horaAtual = new DateTime('now', new DateTimeZone('America/Rio_Branco'));
            $horaLimite = $horaAtual->modify('-2 hours')->format('Y-m-d H:i:s');

            $sql = "
            SELECT s.solicitacao_id
            FROM orders_solicitacoes s
            JOIN orders_paradas p ON s.solicitacao_id = p.solicitacao_id
            WHERE p.link_rastreio_pedido IS NULL
            AND s.empresa_id = :empresa_id
            AND s.data_hora_solicitacao >= :hora_limite
            GROUP BY s.solicitacao_id
        ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':empresa_id' => $empresa_id,
                ':hora_limite' => $horaLimite
            ]);
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return ['success' => true, 'data' => $result];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function fetchLinksFromAPI($solicitacao_id)
    {
        global $pdo;

        $api_url = "https://cloud.taximachine.com.br/api/integracao/obterLinkRastreio/$solicitacao_id";
        $api_key = "mch_api_h2CcjBndaZsjZGgluznxn5FA";
        $username = "ti@vemprodeck.com.br";
        $password = "S3t1c@2013";

        $headers = [
            "Authorization: Basic " . base64_encode("$username:$password"),
            "api-key: $api_key"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // cuidado em produção
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);

            // Atualiza como "Link Expirado"
            self::marcarLinkExpirado($solicitacao_id);

            return ['success' => false, 'error' => "Erro cURL: $error_msg"];
        }

        curl_close($ch);

        // Se erro HTTP (ex: 400), também marca como expirado
        if ($http_code >= 400) {
            self::marcarLinkExpirado($solicitacao_id);

            return [
                'success' => false,
                'error' => "Erro HTTP $http_code",
                'response' => $response
            ];
        }

        return json_decode($response, true);
    }

    private static function marcarLinkExpirado($solicitacao_id)
    {
        global $pdo;

        $sql = "UPDATE orders_paradas 
            SET link_rastreio_pedido = 'Link Expirado' 
            WHERE solicitacao_id = :solicitacao_id 
              AND link_rastreio_pedido IS NULL";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':solicitacao_id' => $solicitacao_id]);
    }

    public static function logPayload($payload, $response = null): array
    {
        $logFile = __DIR__ . 'whatsapp_payloads.log';
        $timestamp = date('Y-m-d H:i:s');

        $logEntry = "[$timestamp] Payload Enviado:\n" . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

        if ($response !== null) {
            $logEntry .= "[$timestamp] Resposta da API:\n" . json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }

        $logEntry .= str_repeat("=", 80) . "\n";

        file_put_contents($logFile, $logEntry, FILE_APPEND);

        return ['success' => true];
    }

    public static function sendWhatsapp($parada_id, $cod, $link_rastreio): array
    {
        global $pdo;


        $config = json_decode(file_get_contents(__DIR__ . '/aws-config.json'), true);
        $aws = $config['aws'];

        $accessKey = $aws['accessKeyId'];
        $secretKey = $aws['secretAccessKey'];
        $region = $aws['region'];
        $service = $aws['service'];
        $queueUrl = $aws['queueUrl'];


        try {
            // === COLETAR DADOS ===
            if ($cod && preg_match('/^\d{4}$/', $cod)) {
                $sqlIdentificador = "
                    SELECT 
                        TRIM(SUBSTRING(identificador_conta, 13)) AS identificador_conta, 
                        CONCAT('55', telefone) AS telefone 
                    FROM orders_delivery 
                    WHERE cod_ifood = :cod
                ";
            } else {
                $sqlIdentificador = "
                    SELECT 
                        TRIM(SUBSTRING(identificador_conta, 13)) AS identificador_conta, 
                        CONCAT('55', telefone) AS telefone 
                    FROM orders_delivery 
                    WHERE cod_iapp = :cod
                ";
            }

            $stmtIdentificador = $pdo->prepare($sqlIdentificador);
            $stmtIdentificador->execute([':cod' => $cod]);
            $identificadorData = $stmtIdentificador->fetch(PDO::FETCH_ASSOC);

            if (!$identificadorData) {
                return ['success' => false, 'error' => "Nenhuma informação encontrada para cod_iapp: $cod."];
            }

            $identificador_conta = $identificadorData['identificador_conta'];
            $telefone = $identificadorData['telefone'];

            // ✅ Validação do telefone
            if (empty($telefone) || !ctype_digit($telefone) || strlen($telefone) < 11) {
                $logData = [
                    'parada_id' => $parada_id,
                    'cod' => $cod,
                    'telefone_invalid' => $telefone,
                    'motivo' => 'Telefone ausente, inválido ou com letras'
                ];

                self::logPayload($logData, 'TELEFONE INVÁLIDO – mensagem ignorada.');

                return ['success' => true, 'error' => "Telefone inválido ou ausente: '$telefone'."];
            }


            $sqlSolicitacao = "SELECT solicitacao_id FROM orders_paradas WHERE id_parada = :parada_id";
            $stmtSolicitacao = $pdo->prepare($sqlSolicitacao);
            $stmtSolicitacao->execute([':parada_id' => $parada_id]);
            $solicitacao_id = $stmtSolicitacao->fetchColumn();

            if (!$solicitacao_id) {
                return ['success' => false, 'error' => "Nenhum solicitacao_id encontrado para parada_id: $parada_id."];
            }

            $sqlDetalhes = "SELECT placa_veiculo, nome_taxista FROM orders_solicitacoes WHERE solicitacao_id = :solicitacao_id";
            $stmtDetalhes = $pdo->prepare($sqlDetalhes);
            $stmtDetalhes->execute([':solicitacao_id' => $solicitacao_id]);
            $detalhes = $stmtDetalhes->fetch(PDO::FETCH_ASSOC);

            if (!$detalhes) {
                return ['success' => false, 'error' => "Nenhuma informação encontrada para solicitacao_id: $solicitacao_id."];
            }

            $placa_veiculo = $detalhes['placa_veiculo'];
            $nome_taxista = $detalhes['nome_taxista'];


            $message = json_encode([
                'identificador_conta' => $identificador_conta,
                'cod' => $cod,
                'nome_taxista' => $nome_taxista,
                'placa_veiculo' => $placa_veiculo,
                'link_rastreio' => $link_rastreio,
                'telefone' => $telefone,
            ]);

            // === AWS Signature v4 ===
            $now = gmdate('Ymd\THis\Z');
            $date = gmdate('Ymd');
            $params = http_build_query([
                'Action' => 'SendMessage',
                'MessageBody' => $message,
                'Version' => '2012-11-05'
            ]);

            $parsedUrl = parse_url($queueUrl);
            $host = $parsedUrl['host'];
            $canonicalUri = $parsedUrl['path'];
            $canonicalHeaders = "host:$host\nx-amz-date:$now\n";
            $signedHeaders = 'host;x-amz-date';
            $payloadHash = hash('sha256', $params);

            $canonicalRequest = implode("\n", [
                'POST',
                $canonicalUri,
                '',
                $canonicalHeaders,
                $signedHeaders,
                $payloadHash
            ]);

            $algorithm = 'AWS4-HMAC-SHA256';
            $credentialScope = "$date/$region/$service/aws4_request";
            $stringToSign = implode("\n", [
                $algorithm,
                $now,
                $credentialScope,
                hash('sha256', $canonicalRequest)
            ]);

            // Função de assinatura
            $sign = function ($key, $msg) {
                return hash_hmac('sha256', $msg, $key, true);
            };

            $kDate = $sign('AWS4' . $secretKey, $date);
            $kRegion = $sign($kDate, $region);
            $kService = $sign($kRegion, $service);
            $kSigning = $sign($kService, 'aws4_request');
            $signature = hash_hmac('sha256', $stringToSign, $kSigning);

            $authorizationHeader = "$algorithm Credential=$accessKey/$credentialScope, SignedHeaders=$signedHeaders, Signature=$signature";
            $headers = [
                "Authorization: $authorizationHeader",
                "x-amz-date: $now",
                "Content-Type: application/x-www-form-urlencoded"
            ];

            // === Envia cURL para o SQS ===
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $queueUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return ['success' => false, 'error' => "Erro cURL: $curlError"];
            }

            // Log da fila, se necessário
            self::logPayload(['mensagem_json' => $message], $response);

            return ['success' => true, 'response' => $response];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function updateParadas($parada_id, $link_rastreio_pedido): array
    {
        global $pdo;

        try {
            $todayZeroed = date('Y-m-d 00:00:00');
            $hora_saida = date('Y-m-d H:i:s');

            $sql = "UPDATE orders_paradas SET link_rastreio_pedido = :link_rastreio_pedido WHERE id_parada = :parada_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':link_rastreio_pedido' => $link_rastreio_pedido,
                ':parada_id' => $parada_id
            ]);

            $sqlCheckNumeroPedido = "SELECT numero_pedido FROM orders_paradas WHERE id_parada = :parada_id LIMIT 1";
            $stmtCheck = $pdo->prepare($sqlCheckNumeroPedido);
            $stmtCheck->execute([':parada_id' => $parada_id]);
            $numero_pedido = $stmtCheck->fetchColumn();

            if ($numero_pedido && preg_match('/^\d{4}$/', $numero_pedido)) {
                $sqlFetchCod = "
                SELECT d.cod_ifood
                FROM orders_paradas p
                JOIN orders_delivery d ON p.numero_pedido = d.cod_ifood
                WHERE p.id_parada = :parada_id
                AND d.hora_abertura > :todayZeroed
                LIMIT 1
            ";
            } else {
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
            $stmtFetch->execute([
                ':parada_id' => $parada_id,
                ':todayZeroed' => $todayZeroed
            ]);
            $cod = $stmtFetch->fetchColumn();

            if ($cod) {
                $column = (strlen($cod) === 4) ? 'cod_ifood' : 'cod_iapp';

                $sqlUpdateDelivery = "
                UPDATE orders_delivery
                SET status_pedido = 'PEDIDO DESPACHADO', hora_saida = :hora_saida
                WHERE $column = :cod AND hora_abertura > :todayZeroed
            ";
                $stmtUpdate = $pdo->prepare($sqlUpdateDelivery);
                $stmtUpdate->execute([
                    ':cod' => $cod,
                    ':todayZeroed' => $todayZeroed,
                    ':hora_saida' => $hora_saida
                ]);

                return [
                    'success' => true,
                    'parada_id' => $parada_id,
                    'cod' => $cod,
                    'link_rastreio' => $link_rastreio_pedido
                ];
            }

            return ['success' => true, 'message' => 'Código não encontrado para atualizar'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function salvarLogWhatsapp(array $mensagem, array $retorno): bool
    {
        global $pdo;

        try {
            // Extrair dados da mensagem
            $telefone = $mensagem['telefone'] ?? null;
            $identificador = $mensagem['identificador_conta'] ?? null;
            $cod = $mensagem['cod'] ?? null;
            $taxista = $mensagem['nome_taxista'] ?? null;
            $placa = $mensagem['placa_veiculo'] ?? null;
            $link = $mensagem['link_rastreio'] ?? null;

            // Extrair dados da resposta
            $waId = $retorno['contacts'][0]['wa_id'] ?? null;
            $messageId = $retorno['messages'][0]['id'] ?? null;
            $status = $retorno['messages'][0]['message_status'] ?? null;

            // Inserir no banco
            $stmt = $pdo->prepare("
            INSERT INTO whatsapp_mensages (
                telefone, identificador_conta, cod_pedido, nome_taxista,
                placa_veiculo, link_rastreio, wa_id, message_id, message_status
            ) VALUES (
                :telefone, :identificador_conta, :cod_pedido, :nome_taxista,
                :placa_veiculo, :link_rastreio, :wa_id, :message_id, :message_status
            )
        ");



            $stmt->execute([
                ':telefone' => $telefone,
                ':identificador_conta' => $identificador,
                ':cod_pedido' => $cod,
                ':nome_taxista' => $taxista,
                ':placa_veiculo' => $placa,
                ':link_rastreio' => $link,
                ':wa_id' => $waId,
                ':message_id' => $messageId,
                ':message_status' => $status
            ]);

            return true;

        } catch (PDOException $e) {
            return false;
        }
    }

}
