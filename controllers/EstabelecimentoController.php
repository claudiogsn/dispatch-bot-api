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
}
?>
