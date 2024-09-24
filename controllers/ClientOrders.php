<?php
date_default_timezone_set('America/Rio_Branco');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db.php';

class ClientOrders {

    public static function getOrders($idenficador, $integracao) {
        global $pdo;

        $dataAtual = date('Y-m-d');


        if ($integracao === 'HUB-IFOOD') {

            $query = 'SELECT * FROM orders_delivery WHERE hora_abertura > :dataAtual AND intg_tipo = :integracao AND status in (1,-1,2) AND identificador_conta LIKE ' . "'%#" . $idenficador . "%'";

        } elseif ($integracao === 'DELIVERY-DIRETO') {

            $query = 'SELECT * FROM orders_delivery WHERE hora_abertura > :dataAtual AND intg_tipo = :integracao AND status in (1,-1,2) AND cod_iapp = ' . $idenficador .'';

        } elseif ($integracao === 'PEDIDO DIGITADO') {

            if (strlen($idenficador) == 11) {
                $idenficador = substr($idenficador, 2);
            }elseif (strlen($idenficador) == 10) {
                $idenficador = substr($idenficador, 2);
            }
            $query = 'SELECT * FROM orders_delivery WHERE hora_abertura > :dataAtual AND intg_tipo = :integracao AND status in (1,-1,2) AND identificador_conta LIKE ' . "'%" . $idenficador . "%'";
            }
        else {
            throw new Exception("Tipo de integração desconhecido: " . $integracao);
        }

        $stmt = $pdo->prepare($query);

        $stmt->bindParam(':integracao', $integracao);
        $stmt->bindParam(':dataAtual', $dataAtual);
        $stmt->execute();

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as &$order) {
                $order['identificador'] = $idenficador;
            }

        if (empty($orders)) {
            throw new Exception("Não Foram encontrados pedidos para o identificador: " . $idenficador);
        }

        return $orders;
    }
}
