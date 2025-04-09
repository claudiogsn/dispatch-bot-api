<?php

// Dados fictícios para teste — substitua pelos reais quando for testar de verdade
$telefone = "558399275543";
$identificador_conta = "ABC123";
$cod = "PEDIDO789";
$nome_taxista = "João Taxista";
$placa_veiculo = "XYZ-1234";
$link_rastreio = "https://seusite.com/rastreio/ABC123";

// Dados da API
$api_url = "https://api.z-api.io/instances/3DF712E49DF860A86AD80A1EFCACDE10/token/A22B3AAD2C11A72646680264/send-text";
$api_key = "F00ff92c2022b4ed290e5b6e70f36b308S";

// Mensagem personalizada
$mensagem = "🚨 *Notícia boa!* *{$identificador_conta}*, seu pedido *{$cod}* já está em rota de entrega!\n\n" .
    "Nosso motoboy *{$nome_taxista}* de placa *{$placa_veiculo}* pode ser acompanhado em tempo real pelo link: {$link_rastreio}\n" .
    "Estamos chegando, até já! 😊\n" .
    "_Esta mensagem é automática e não deve ser respondida._";

// Payload conforme esperado pela API Z-API
$payload = [
    "phone" => $telefone,
    "message" => $mensagem
];

// Configurações do contexto HTTP para a requisição
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

// Envia a requisição
try {
    $response = file_get_contents($api_url, false, $context);
    echo "✅ Mensagem enviada com sucesso!\n";
    echo "📩 Resposta da API: \n$response\n";
} catch (Exception $e) {
    echo "❌ Erro ao enviar mensagem: " . $e->getMessage() . "\n";
}
