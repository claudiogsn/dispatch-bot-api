<?php
date_default_timezone_set('America/Rio_Branco');
header('Content-Type: application/json; charset=utf-8');

require_once 'controllers/EstabelecimentoController.php';
require_once 'controllers/OrderController.php';
require_once 'controllers/LogController.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (isset($data['method']) && isset($data['data'])) {
    $method = $data['method'];
    $requestData = $data['data'];

    try {
        switch ($method) {
            // Métodos para EstabelecimentoController
            case 'getEstabelecimentos':
                $response = EstabelecimentoController::getEstabelecimentos();
                break;
            case 'getEstabelecimentoByCnpj':
                $response = EstabelecimentoController::getEstabelecimentoByCnpj($requestData['cnpj'], $requestData['token']);
                break;

            // Métodos para OrderController
            case 'createOrder':
                $response = OrderController::createOrder($requestData);
                break;
            case 'readOrders':
                $response = OrderController::readOrders();
                break;
            case 'updateOrder':
                $response = OrderController::updateOrder($requestData['cod_iapp'], $requestData['data']);
                break;
            case 'deleteOrder':
                $response = OrderController::deleteOrder($requestData['cod_iapp']);
                break;
            case 'getOrderByNumControle':
                $response = OrderController::getOrderByNumControle($requestData['num_controle']);
                break;
            case 'getOrderByCodIapp':
                $response = OrderController::getOrderByCodIapp($requestData['cod_iapp']);
                break;
            case 'verifyDispatch':
                    $response = OrderController::verifyDispatch($requestData['cod_iapp']);
                break;
            case 'verifyDone':
                    $response = OrderController::verifyDone($requestData['cod_iapp']);
                break;
            case 'setOrderDone':
                $response = OrderController::setOrderDone($requestData['cod_iapp']);
                break;
            case 'setOrderDispatched':
                $response = OrderController::setOrderDispatched($requestData['cod_iapp']);
                break;

            

            // Métodos para LogController
            case 'addLog':
                $response = LogController::addLog($requestData['id_loja'], $requestData['nome_loja'], $requestData['tipo_log'], $requestData['mensagem']);
                break;
            case 'getLogs':
                $response = LogController::getLogs();
                break;
            case 'getLogsByType':
                $response = LogController::getLogsByType($requestData['tipo_log']);
                break;

            default:
                http_response_code(405);
                $response = array('error' => 'Método não suportado');
                break;
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $e) {
        http_response_code(500);
        $response = array('error' => 'Erro interno do servidor: ' . $e->getMessage());
        echo json_encode($response);
    }
} else {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(array('error' => 'Parâmetros inválidos'));
}
?>
