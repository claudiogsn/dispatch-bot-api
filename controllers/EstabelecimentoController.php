<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db.php';

class EstabelecimentoController {

    public static function getEstabelecimentos() {
        global $pdo;

        $stmt = $pdo->prepare('SELECT * FROM estabelecimento');
        $stmt->execute();

        $estabelecimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $estabelecimentos;
    }

    public static function getEstabelecimentoByCnpj($cnpj, $token) {
        global $pdo;

        $stmt = $pdo->prepare('SELECT * FROM estabelecimento WHERE cnpj = :cnpj AND token = :token');
        $stmt->bindParam(':cnpj', $cnpj);
        $stmt->bindParam(':token', $token);
        $stmt->execute();


        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
