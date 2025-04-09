<?php

// Verifica se o solicitacao_id foi passado na URL
if (!isset($_GET['solicitacao_id']) || empty($_GET['solicitacao_id'])) {
    http_response_code(400); // Bad Request
    echo "❌ Você precisa passar o parâmetro 'solicitacao_id' na URL. Ex: ?solicitacao_id=123456";
    exit;
}

$solicitacao_id = $_GET['solicitacao_id'];

// Função para chamar a API de rastreio
function fetchLinksFromAPI($solicitacao_id) {
    echo "📡 Chamando a API para obter links de rastreio para solicitacao_id: $solicitacao_id...\n";

    $api_url = "https://cloud.taximachine.com.br/api/integracao/obterLinkRastreio/$solicitacao_id";
    $api_key = "mch_api_h2CcjBndaZsjZGgluznxn5FA";
    $username = "ti@vemprodeck.com.br";
    $password = "S3t1c@2013";

    $options = [
        "http" => [
            "header" => [
                "Authorization: Basic " . base64_encode("$username:$password"),
                "api-key: $api_key"
            ],
            "method" => "GET",
            "timeout" => 30
        ]
    ];

    $context = stream_context_create($options);

    try {
        $response = file_get_contents($api_url, false, $context);
        echo "✅ Resposta da API recebida com sucesso.\n";

        $data = json_decode($response, true);
        echo "📦 Conteúdo da resposta:\n";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

        return $data;
    } catch (Exception $e) {
        echo "❌ Erro ao realizar requisição na API: " . $e->getMessage() . "\n";
        return null;
    }
}

// Executa a função
fetchLinksFromAPI($solicitacao_id);

