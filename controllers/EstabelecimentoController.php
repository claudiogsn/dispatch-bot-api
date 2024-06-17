<?php
date_default_timezone_set('America/Rio_Branco');
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

    public static function createEstabelecimento($data) {
        global $pdo;

        $sql = 'INSERT INTO estabelecimento (idestabelecimento, cnpj, token, user_dd, senha_dd, x_dd_id, id_dd, nome_loja, client_id, client_secret, status) VALUES (:idestabelecimento, :cnpj, :token, :user_dd, :senha_dd, :x_dd_id, :id_dd, :nome_loja, :client_id, :client_secret, :status)';
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':idestabelecimento', $data['idestabelecimento']);
        $stmt->bindParam(':cnpj', $data['cnpj']);
        $stmt->bindParam(':token', $data['token']);
        $stmt->bindParam(':user_dd', $data['user_dd']);
        $stmt->bindParam(':senha_dd', $data['senha_dd']);
        $stmt->bindParam(':x_dd_id', $data['x_dd_id']);
        $stmt->bindParam(':id_dd', $data['id_dd']);
        $stmt->bindParam(':nome_loja', $data['nome_loja']);
        $stmt->bindParam(':client_id', $data['client_id']);
        $stmt->bindParam(':client_secret', $data['client_secret']);
        $stmt->bindParam(':status', $data['status']);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Estabelecimento criado com sucesso.'];
        } else {
            return ['success' => false, 'message' => 'Falha ao criar estabelecimento.'];
        }
    }
}
?>
