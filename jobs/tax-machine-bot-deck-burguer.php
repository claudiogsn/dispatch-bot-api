<?php

//41815 - DECK BURGER & CHURRAS

require_once __DIR__ . '/../database/db.php'; // Caminho para a conexão com o banco de dados
require_once __DIR__ .'/../controllers/LogglyLogger.php';
global $pdo;
$logger = new LogglyLogger();

// Define o timezone como "America/Rio_Branco"
$timezone = new DateTimeZone('America/Rio_Branco');

// Calcula o horário atual e o horário de duas horas atrás em "America/Rio_Branco"
$data_hora_atual = new DateTime('now', $timezone);
$data_hora_menos_2 = (clone $data_hora_atual)->modify('-2 hours');

// Formata as datas para o formato ISO 8601 exigido pela API
$data_hora_atual_formatada = $data_hora_atual->format('Y-m-d\TH:i:s.v\Z');
$data_hora_menos_2_formatada = $data_hora_menos_2->format('Y-m-d\TH:i:s.v\Z');

// Monta a URL da API com as datas dinâmicas
$api_url = "https://cloud.taximachine.com.br/api/integracao/solicitacao?empresa_id=41815&data_hora_solicitacao_min=$data_hora_menos_2_formatada&data_hora_solicitacao_max=$data_hora_atual_formatada";

$api_key = "mch_api_h2CcjBndaZsjZGgluznxn5FA";
$username = "ti@vemprodeck.com.br";
$password = "S3t1c@2013";
$empresa_id = "41815";

// Realizando a requisição da API com Basic Auth
$options = [
    "http" => [
        "header" => [
            "Authorization: Basic " . base64_encode("$username:$password"),
            "api-key: $api_key"
        ]
    ]
];

$context = stream_context_create($options);
$response = file_get_contents($api_url, false, $context);

if ($response === FALSE) {
    // Exibindo erro na requisição
    echo "Erro ao consumir a API.";
    exit;
}

// Decodificar a resposta da API
$data = json_decode($response, true);

// Verificar se a resposta contém dados
if (isset($data['success']) && $data['success'] === true && isset($data['response'])) {
    $orders = $data['response'];

    try {
        // Iniciar a transação no banco de dados
        $pdo->beginTransaction();

        foreach ($orders as $order) {
            $solicitacao_id = $order['id'];
            $data_hora_solicitacao = $order['data_hora_solicitacao'];
            $data_hora_chegada_local = $order['data_hora_chegada_local'];
            $status_solicitacao = $order['status_solicitacao'];
            $cliente_id = $order['cliente_id'];
            $nome_passageiro = $order['nome_passageiro'];
            $empresa_id = $order['empresa_id'];
            $bandeira_chamada_id = $order['bandeira_chamada_id'];
            $bandeira_configuracao_id = $order['bandeira_configuracao_id'];
            $data_hora_aceite = $order['data_hora_aceite'];
            $data_hora_finalizacao = $order['data_hora_finalizacao'];
            $distancia_coleta_km = $order['distancia_coleta_km'];
            $valor_corrida = $order['valor_corrida'];
            $condutor_especificado = $order['condutor_especificado'];
            $com_retorno = $order['com_retorno'];
            $taxista_id = $order['taxista_id'];
            $nome_taxista = $order['nome_taxista'];
            $telefone_taxista = $order['telefone_taxista'];
            $veiculo = $order['veiculo'];
            $placa_veiculo = $order['placa_veiculo'];
            $cor_veiculo = $order['cor_veiculo'];
            $duracao_corrida = $order['duracao_corrida'];
            $distancia_percorrida_km = $order['distancia_percorrida_km'];

            // Inserir a solicitação na tabela orders_solicitacoes
            $stmt = $pdo->prepare("
                INSERT INTO orders_solicitacoes (
                    solicitacao_id, data_hora_solicitacao, data_hora_chegada_local, status_solicitacao, 
                    cliente_id, nome_passageiro, empresa_id, bandeira_chamada_id, 
                    bandeira_configuracao_id, data_hora_aceite, data_hora_finalizacao, 
                    distancia_coleta_km, valor_corrida, condutor_especificado, com_retorno, 
                    taxista_id, nome_taxista, telefone_taxista, veiculo, placa_veiculo, 
                    cor_veiculo, duracao_corrida, distancia_percorrida_km
                ) VALUES (
                    :solicitacao_id, :data_hora_solicitacao, :data_hora_chegada_local, :status_solicitacao, 
                    :cliente_id, :nome_passageiro, :empresa_id, :bandeira_chamada_id, 
                    :bandeira_configuracao_id, :data_hora_aceite, :data_hora_finalizacao, 
                    :distancia_coleta_km, :valor_corrida, :condutor_especificado, :com_retorno, 
                    :taxista_id, :nome_taxista, :telefone_taxista, :veiculo, :placa_veiculo, 
                    :cor_veiculo, :duracao_corrida, :distancia_percorrida_km
                ) ON DUPLICATE KEY UPDATE 
                    data_hora_solicitacao = VALUES(data_hora_solicitacao),
                    data_hora_chegada_local = VALUES(data_hora_chegada_local),
                    status_solicitacao = VALUES(status_solicitacao),
                    cliente_id = VALUES(cliente_id),
                    nome_passageiro = VALUES(nome_passageiro),
                    empresa_id = VALUES(empresa_id),
                    bandeira_chamada_id = VALUES(bandeira_chamada_id),
                    bandeira_configuracao_id = VALUES(bandeira_configuracao_id),
                    data_hora_aceite = VALUES(data_hora_aceite),
                    data_hora_finalizacao = VALUES(data_hora_finalizacao),
                    distancia_coleta_km = VALUES(distancia_coleta_km),
                    valor_corrida = VALUES(valor_corrida),
                    condutor_especificado = VALUES(condutor_especificado),
                    com_retorno = VALUES(com_retorno),
                    taxista_id = VALUES(taxista_id),
                    nome_taxista = VALUES(nome_taxista),
                    telefone_taxista = VALUES(telefone_taxista),
                    veiculo = VALUES(veiculo),
                    placa_veiculo = VALUES(placa_veiculo),
                    cor_veiculo = VALUES(cor_veiculo),
                    duracao_corrida = VALUES(duracao_corrida),
                    distancia_percorrida_km = VALUES(distancia_percorrida_km)
            ");

            // Exibindo dados antes de inserir
            echo "Inserindo solicitação ID: $solicitacao_id\n";

            $stmt->execute([
                ':solicitacao_id' => $solicitacao_id,
                ':data_hora_solicitacao' => $data_hora_solicitacao,
                ':data_hora_chegada_local' => $data_hora_chegada_local,
                ':status_solicitacao' => $status_solicitacao,
                ':cliente_id' => $cliente_id,
                ':nome_passageiro' => $nome_passageiro,
                ':empresa_id' => $empresa_id,
                ':bandeira_chamada_id' => $bandeira_chamada_id,
                ':bandeira_configuracao_id' => $bandeira_configuracao_id,
                ':data_hora_aceite' => $data_hora_aceite,
                ':data_hora_finalizacao' => $data_hora_finalizacao,
                ':distancia_coleta_km' => $distancia_coleta_km,
                ':valor_corrida' => $valor_corrida,
                ':condutor_especificado' => $condutor_especificado,
                ':com_retorno' => $com_retorno,
                ':taxista_id' => $taxista_id,
                ':nome_taxista' => $nome_taxista,
                ':telefone_taxista' => $telefone_taxista,
                ':veiculo' => $veiculo,
                ':placa_veiculo' => $placa_veiculo,
                ':cor_veiculo' => $cor_veiculo,
                ':duracao_corrida' => $duracao_corrida,
                ':distancia_percorrida_km' => $distancia_percorrida_km
            ]);

            // Agora inserimos as paradas, usando o mesmo solicitacao_id
            foreach ($order['paradas'] as $parada) {
                $id_parada = $parada['id'];
                $endereco = $parada['endereco'];
                $complemento = $parada['complemento'];
                $bairro = $parada['bairro'];
                $cidade = $parada['cidade'];
                $uf = $parada['uf'];
                $lat = $parada['lat'];
                $lng = $parada['lng'];
                $numero_pedido = $parada['numero_pedido'];

                // Exibindo o ID da parada para debugging
                echo "Inserindo parada ID: $id_parada, solicitacao_id: $solicitacao_id<br>";
                echo "Endereço: $endereco, Complemento: $complemento, Bairro: $bairro<br>";

                // Inserir a parada, mantendo o solicitacao_id correto
                $stmt_parada = $pdo->prepare("
                    INSERT INTO orders_paradas (
                        id_parada, solicitacao_id, endereco, complemento, bairro, cidade, uf, lat, lng, 
                        numero_pedido
                    ) VALUES (
                        :id_parada, :solicitacao_id, :endereco, :complemento, :bairro, :cidade, :uf, :lat, :lng, 
                        :numero_pedido
                    ) ON DUPLICATE KEY UPDATE
                        endereco = VALUES(endereco),
                        complemento = VALUES(complemento),
                        bairro = VALUES(bairro),
                        cidade = VALUES(cidade),
                        uf = VALUES(uf),
                        lat = VALUES(lat),
                        lng = VALUES(lng),
                        numero_pedido = VALUES(numero_pedido)");

                // Executar o statement
                $stmt_parada->execute([
                    ':id_parada' => $id_parada,
                    ':solicitacao_id' => $solicitacao_id, // Aqui usamos o solicitacao_id da API
                    ':endereco' => $endereco,
                    ':complemento' => $complemento,
                    ':bairro' => $bairro,
                    ':cidade' => $cidade,
                    ':uf' => $uf,
                    ':lat' => $lat,
                    ':lng' => $lng,
                    ':numero_pedido' => $numero_pedido
                ]);

                // Exibe o último ID inserido
                $parada_id_inserted = $pdo->lastInsertId();
                echo "Parada inserida com sucesso. ID da parada: $parada_id_inserted<br>";
            }
        }

        // Commit da transação
        $pdo->commit();

    } catch (Exception $e) {
        // Caso ocorra algum erro, rollback da transação
        $pdo->rollBack();
        echo "Erro na inserção: " . $e->getMessage();
    }
} else {
    echo "Dados inválidos ou resposta não encontrada na API.";
}

?>
