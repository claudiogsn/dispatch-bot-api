<?php
date_default_timezone_set('America/Rio_Branco');
require_once 'database/db.php';

class LogController {
    public static function addLog($id_loja, $nome_loja, $tipo_log, $mensagem) {
        global $pdo;

        $timestamp = date('Y-m-d H:i:s'); 


        $query = "INSERT INTO logs (id_loja,timestamp, nome_loja, tipo_log, mensagem) 
                  VALUES (:id_loja,:timestamp, :nome_loja, :tipo_log, :mensagem)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id_loja', $id_loja);
        $stmt->bindParam(':timestamp', $timestamp);
        $stmt->bindParam(':nome_loja', $nome_loja);
        $stmt->bindParam(':tipo_log', $tipo_log);
        $stmt->bindParam(':mensagem', $mensagem);

        return $stmt->execute();
    }

    public static function getLogs() {
        global $pdo;

        $query = "SELECT * FROM logs";
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getLogsByType($tipo_log) {
        global $pdo;

        $query = "SELECT * FROM logs WHERE tipo_log = :tipo_log";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':tipo_log', $tipo_log);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}