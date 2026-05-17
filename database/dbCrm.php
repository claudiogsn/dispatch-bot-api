<?php
header('Content-Type: application/json; charset=utf-8');

$config = parse_ini_file('crm.ini'); // Caminho ajustado

if ($config === false) {
    http_response_code(500);
    die("Erro ao ler o arquivo de configuração.");
}

$host = $config['host'];
$port = $config['port'];
$dbname = $config['name'];
$username = $config['user'];
$password = $config['pass'];

$dsn = "{$config['type']}:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

try {
    $pdoCrm = new PDO($dsn, $username, $password);
    $pdoCrm->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
?>
