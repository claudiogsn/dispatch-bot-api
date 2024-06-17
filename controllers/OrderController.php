<?php
date_default_timezone_set('America/Rio_Branco');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db.php';

class OrderController {

    public static function createOrder($order) {
        global $pdo;

        $timestamp = date('Y-m-d H:i:s'); 
    
        $query = "INSERT INTO orders (num_controle, dt_mov, cod_iapp, created_at,status_entrega) 
                  VALUES (:num_controle,:dt_mov, :cod_iapp, :created_at,:status_entrega)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':num_controle', $order['num_controle']);
        $stmt->bindParam(':dt_mov', $order['dt_mov']);
        $stmt->bindParam(':cod_iapp', $order['cod_iapp']);
        $stmt->bindParam(':created_at', $timestamp);
       $stmt->bindParam(':status_entrega', $order['status_entrega']);
    
        if ($stmt->execute()) {
            return array(["message" => "Order created successfully."]);
        } else {
            return array(["message" => "Failed to create order."]);
        }
    }
    

    public static function readOrders() {
        global $pdo;

        $timestamp = date('Y-m-d'); 

        $query = "SELECT * FROM orders where dt_mov = :dt_mov";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':dt_mov', $timestamp);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ($orders);
    }

    public static function updateOrder($cod_iapp, $order) {
        global $pdo;

        $query = "UPDATE orders SET num_controle = :num_controle, idestabelecimento = :idestabelecimento, dt_mov = :dt_mov, 
                  moment_dispatch = :moment_dispatch, created_at = :created_at WHERE cod_iapp = :cod_iapp";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':num_controle', $order['num_controle']);
        $stmt->bindParam(':idestabelecimento', $order['idestabelecimento']);
        $stmt->bindParam(':dt_mov', $order['dt_mov']);
        $stmt->bindParam(':moment_dispatch', $order['moment_dispatch']);
        $stmt->bindParam(':created_at', $timestamp);
        $stmt->bindParam(':cod_iapp', $cod_iapp);

        if ($stmt->execute()) {
            return array(["message" => "Order updated successfully."]);
        } else {
            return array(["message" => "Failed to update order."]);
        }
    }

    public static function deleteOrder($cod_iapp) {
        global $pdo;

        $query = "DELETE FROM orders WHERE cod_iapp = :cod_iapp";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':cod_iapp', $cod_iapp);

        if ($stmt->execute()) {
            return array(["message" => "Order deleted successfully."]);
        } else {
            return array(["message" => "Failed to delete order."]);
        }
    }

    public static function getOrderByNumControle($num_controle) {
        global $pdo;

        $query = "SELECT * FROM orders WHERE num_controle = :num_controle";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':num_controle', $num_controle);

        if ($stmt->execute()) {
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($order) {
                http_response_code(200);
                return ($order);
            } else {
                http_response_code(404);
                return array(["message" => "Order not found."]);
            }
        } else {
            http_response_code(400);
            return array(["message" => "Failed to retrieve order."]);
        }
    }

    public static function getOrderByCodIapp($cod_iapp) {
        global $pdo;

        $query = "SELECT * FROM orders WHERE cod_iapp = :cod_iapp";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':cod_iapp', $cod_iapp);
        if ($stmt->execute()) {
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($order) {
                http_response_code(200);
                return ($order);
            } else {
                http_response_code(404);
                return array(["message" => "Order not found."]);
            }
        } else {
            http_response_code(400);
            return array(["message" => "Failed to retrieve order."]);
        }
    }

    public static function verifyDispatch($cod_iapp) {
        global $pdo;

        $query = "SELECT COUNT(*) FROM orders WHERE cod_iapp = :cod_iapp and moment_dispatch is not null";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':cod_iapp', $cod_iapp);
        $stmt->execute();

        $count = $stmt->fetchColumn();

        return ($count > 0);
    }

    public static function verifyDone($cod_iapp) {
        global $pdo;

        $query = "SELECT COUNT(*) FROM orders WHERE cod_iapp = :cod_iapp and moment_done is not null";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':cod_iapp', $cod_iapp);
        $stmt->execute();

        $count = $stmt->fetchColumn();

        return ($count > 0);
    }

    public static function setOrderDispatched($cod_iapp,$id_loja,$nome_loja) {
        global $pdo;
        $timestamp = date('Y-m-d H:i:s'); 

        $query = "UPDATE orders SET moment_dispatch = :moment_dispatch,status_entrega = 5, id_loja = :id_loja, nome_loja = :nome_loja WHERE cod_iapp = :cod_iapp";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':moment_dispatch', $timestamp);
        $stmt->bindParam(':cod_iapp', $cod_iapp);
        $stmt->bindParam(':id_loja', $id_loja);
        $stmt->bindParam(':nome_loja', $nome_loja);
        if ($stmt->execute()) {
            return array(["message" => "Order Dispatched successfully."]);
        } else {
            return array(["message" => "Failed to update order."]);
        }
    }

    public static function setOrderDone($cod_iapp) {
        global $pdo;
        $timestamp = date('Y-m-d H:i:s'); 

        $query = "UPDATE orders SET moment_done = :moment_done,status_entrega = 9 WHERE cod_iapp = :cod_iapp";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':moment_dispatch', $timestamp);
        $stmt->bindParam(':cod_iapp', $cod_iapp);
        if ($stmt->execute()) {
            return array(["message" => "Order Done successfully."]);
        } else {
            return array(["message" => "Failed to update order."]);
        }
    }

}