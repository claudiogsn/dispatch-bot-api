<?php
date_default_timezone_set('America/Rio_Branco');
require_once 'database/db.php';

class OrdersDeliveryController {
    // Cria uma nova entrada na tabela orders_delivery
    public static function createOrderDelivery($data) {
        global $pdo;

        $query = "INSERT INTO orders_delivery (cnpj, hash, num_controle, status, modo_de_conta, identificador_conta, hora_abertura, hora_saida, intg_tipo, cod_iapp, tempo_preparo, status_pedido)
                  VALUES (:cnpj, :hash, :num_controle, :status, :modo_de_conta, :identificador_conta, :hora_abertura, :hora_saida, :intg_tipo, :cod_iapp, :tempo_preparo, :status_pedido)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':cnpj', $data['cnpj']);
        $stmt->bindParam(':hash', $data['hash']);
        $stmt->bindParam(':num_controle', $data['num_controle']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':modo_de_conta', $data['modo_de_conta']);
        $stmt->bindParam(':identificador_conta', $data['identificador_conta']);
        $stmt->bindParam(':hora_abertura', $data['hora_abertura']);
        $stmt->bindParam(':hora_saida', $data['hora_saida']);
        $stmt->bindParam(':intg_tipo', $data['intg_tipo']);
        $stmt->bindParam(':cod_iapp', $data['cod_iapp']);
        $stmt->bindParam(':tempo_preparo', $data['tempo_preparo']);
        $stmt->bindParam(':status_pedido', $data['status_pedido']);

        return $stmt->execute();
    }

    // Busca todos os pedidos
    public static function getAllOrderDeliveries() {
        global $pdo;

        $query = "SELECT * FROM orders_delivery";
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Busca um pedido pelo ID
    public static function getOrderDeliveryById($id) {
        global $pdo;

        $query = "SELECT * FROM orders_delivery WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Atualiza um pedido pelo ID
    public static function updateOrderDelivery($id, $data) {
        global $pdo;

        $query = "UPDATE orders_delivery SET
                  cnpj = :cnpj, hash = :hash, num_controle = :num_controle, status = :status, 
                  modo_de_conta = :modo_de_conta, identificador_conta = :identificador_conta, 
                  hora_abertura = :hora_abertura, hora_saida = :hora_saida, intg_tipo = :intg_tipo, 
                  cod_iapp = :cod_iapp, tempo_preparo = :tempo_preparo, status_pedido = :status_pedido
                  WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':cnpj', $data['cnpj']);
        $stmt->bindParam(':hash', $data['hash']);
        $stmt->bindParam(':num_controle', $data['num_controle']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':modo_de_conta', $data['modo_de_conta']);
        $stmt->bindParam(':identificador_conta', $data['identificador_conta']);
        $stmt->bindParam(':hora_abertura', $data['hora_abertura']);
        $stmt->bindParam(':hora_saida', $data['hora_saida']);
        $stmt->bindParam(':intg_tipo', $data['intg_tipo']);
        $stmt->bindParam(':cod_iapp', $data['cod_iapp']);
        $stmt->bindParam(':tempo_preparo', $data['tempo_preparo']);
        $stmt->bindParam(':status_pedido', $data['status_pedido']);

        return $stmt->execute();
    }

    // Deleta um pedido pelo ID
    public static function deleteOrderDelivery($id) {
        global $pdo;

        $query = "DELETE FROM orders_delivery WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }


    public static function updateOrderDeliveryByCompositeKey($cnpj, $hash, $num_controle, $data) {
        global $pdo;

        // recuperar o ID do pedido
        $queryFindId = "SELECT id FROM orders_delivery WHERE cnpj = :cnpj AND hash = :hash AND num_controle = :num_controle";
        $stmt = $pdo->prepare($queryFindId);
        $stmt->bindParam(':cnpj', $cnpj);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':num_controle', $num_controle);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return false;  // Pedido não encontrado
        }

        $id = $result['id'];

        // realiza o update usando o ID
        $queryUpdate = "UPDATE orders_delivery SET
                        status = :status, modo_de_conta = :modo_de_conta, identificador_conta = :identificador_conta, 
                        hora_abertura = :hora_abertura, hora_saida = :hora_saida, intg_tipo = :intg_tipo, 
                        cod_iapp = :cod_iapp, tempo_preparo = :tempo_preparo, status_pedido = :status_pedido
                        WHERE id = :id";
        $stmtUpdate = $pdo->prepare($queryUpdate);
        $stmtUpdate->bindParam(':id', $id);
        $stmtUpdate->bindParam(':status', $data['status']);
        $stmtUpdate->bindParam(':modo_de_conta', $data['modo_de_conta']);
        $stmtUpdate->bindParam(':identificador_conta', $data['identificador_conta']);
        $stmtUpdate->bindParam(':hora_abertura', $data['hora_abertura']);
        $stmtUpdate->bindParam(':hora_saida', $data['hora_saida']);
        $stmtUpdate->bindParam(':intg_tipo', $data['intg_tipo']);
        $stmtUpdate->bindParam(':cod_iapp', $data['cod_iapp']);
        $stmtUpdate->bindParam(':tempo_preparo', $data['tempo_preparo']);
        $stmtUpdate->bindParam(':status_pedido', $data['status_pedido']);

        return $stmtUpdate->execute();
    }

    public static function getOrderDeliveryByCompositeKey($cnpj, $hash, $num_controle) {
        global $pdo;

        $query = "SELECT * FROM orders_delivery WHERE cnpj = :cnpj AND hash = :hash AND num_controle = :num_controle";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':cnpj', $cnpj);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':num_controle', $num_controle);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}


