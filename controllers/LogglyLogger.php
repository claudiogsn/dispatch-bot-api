<?php

class LogglyLogger
{
    // Definindo o token diretamente na classe
    private $url = 'https://logs-01.loggly.com/inputs/c9bbe18b-b06f-471e-b21e-4307da3413f8/tag/http/';

    // Método para enviar o log
    public function sendLog($message, $level = 'INFO')
    {
        // Dados que você deseja enviar
        $data = [
            'message' => $message,
            'level' => $level, // Pode ser 'INFO', 'ERROR', 'DEBUG', etc.
        ];

        // Opções de configuração para a requisição HTTP
        $options = [
            'http' => [
                'header'  => "Content-type: application/json",
                'method'  => 'POST',
                'content' => json_encode($data),
            ]
        ];

        // Criando o contexto de requisição
        $context = stream_context_create($options);

        // Enviando a requisição para o Loggly
        $response = file_get_contents($this->url, false, $context);

        // Verificando se houve algum erro
        if ($response === FALSE) {
            throw new Exception('Erro ao enviar log para o Loggly');
        }

        return 'Log enviado com sucesso!';
    }
}

?>
