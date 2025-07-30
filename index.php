<?php
// Permitir acesso de qualquer origem
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

date_default_timezone_set('America/Rio_Branco');
header('Content-Type: application/json; charset=utf-8');

require_once 'controllers/EstabelecimentoController.php';
require_once 'controllers/OrderController.php';
require_once 'controllers/LogController.php';
require_once 'controllers/OrdersDeliveryController.php';
require_once 'controllers/ClientOrders.php';
require_once 'controllers/JobsController.php';
require_once 'controllers/NpsController.php';
require_once 'controllers/MesaController.php';
require_once 'controllers/BiController.php';
require_once 'controllers/ConsultasController.php';

$method = $_REQUEST['method'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $method = $data['method'];
    $requestData = $data['data'];
} else {
    $requestData = $_GET;
}
    try {
        if (!$method) {
            http_response_code(400);
            throw new Exception("Missing 'method' parameter.");
        }
        switch ($method) {
            // Métodos para BiController
            case 'createOrUpdateContas':
                if (isset($requestData['contas']) && is_array($requestData['contas'])) {
                    $response = BiController::createOrUpdateContas($requestData['contas']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing or invalid field: dados.");
                }
                break;
            case 'createOrUpdateItens':
                if (isset($requestData['itens']) && is_array($requestData['itens'])) {
                    $response = BiController::createOrUpdateItens($requestData['itens']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing or invalid field: dados.");
                }
                break;
            case 'getContasBi':
                if (isset($requestData['data_inicial']) && isset($requestData['data_final'])) {
                    $response = BiController::getContasBi($requestData['data_inicial'], $requestData['data_final']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields: data_inicial and data_final.");
                }
                break;
            case 'getItensBi':
                if (isset($requestData['data_inicial']) && isset($requestData['data_final'])) {
                    $response = BiController::getItensBi($requestData['data_inicial'], $requestData['data_final']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields: data_inicial and data_final.");
                }
                break;
            // Métodos para EstabelecimentoController
            case 'getEstabelecimentos':
                $response = EstabelecimentoController::getEstabelecimentos();
                break;
            case 'getNameEstabelecimentos':
                $response = EstabelecimentoController::getNameEstabelecimentos();
                break;
            case 'getEstabelecimentoByCnpj':
                $response = EstabelecimentoController::getEstabelecimentoByCnpj($requestData['cnpj'], $requestData['token']);
                break;
            case 'createEstabelecimento':
                $response = EstabelecimentoController::createEstabelecimento($requestData);
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

            // Novos métodos para OrdersDeliveryController
            case 'createOrderDelivery':
                $response = OrdersDeliveryController::createOrderDelivery($requestData);
                break;
            case 'getAllOrderDeliveries':
                $response = OrdersDeliveryController::getAllOrderDeliveries();
                break;
            case 'getOrderDeliveryById':
                $response = OrdersDeliveryController::getOrderDeliveryById($requestData['id']);
                break;
            case 'updateOrderDeliveryByCompositeKey':
                if (isset($requestData['cnpj'], $requestData['hash'], $requestData['num_controle'], $requestData['update_data'])) {
                    $response = OrdersDeliveryController::updateOrderDeliveryByCompositeKey(
                        $requestData['cnpj'], $requestData['hash'], $requestData['num_controle'], $requestData['update_data']
                    );
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields for updateOrderDeliveryByCompositeKey.");
                }
                break;
            case 'deleteOrderDelivery':
                $response = OrdersDeliveryController::deleteOrderDelivery($requestData['id']);
                break;
            case 'getOrderDeliveryByCompositeKey':
                if (isset($requestData['cnpj'], $requestData['hash'], $requestData['num_controle'])) {
                    $response = OrdersDeliveryController::getOrderDeliveryByCompositeKey(
                        $requestData['cnpj'], $requestData['hash'], $requestData['num_controle']
                    );
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields for getOrderDeliveryByCompositeKey.");
                }
                break;
            case 'getOrdersDeliveryByPeriod':
                if (isset($requestData['start'], $requestData['end'])) {
                    $response = OrdersDeliveryController::getOrdersDeliveryByPeriod($requestData['start'], $requestData['end']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields for getOrdersDeliveryByPeriod.");
                }
                break;
            case 'getOrdersDeliveryByPeriodMock':
                if (isset($requestData['start'], $requestData['end'])) {
                    $response = OrdersDeliveryController::getOrdersDeliveryByPeriodMock($requestData['start'], $requestData['end']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields for getOrdersDeliveryByPeriod.");
                }
                break;
            case 'calculateTimesByCompositeKey':
                if (isset($requestData['cnpj'], $requestData['hash'], $requestData['num_controle'])) {
                    $response = OrdersDeliveryController::calculateTimesByCompositeKey(
                        $requestData['cnpj'], $requestData['hash'], $requestData['num_controle']
                    );
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields for calculateTimesByCompositeKey.");
                }
                break;
            case 'getOrdersChartData':
                if (isset($requestData['start'], $requestData['end'])) {
                    $response = OrdersDeliveryController::getOrdersChartData($requestData['start'], $requestData['end']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields for getOrdersChartData.");
                }
                break;
            case 'getOrders':
                if (isset($requestData['identificador'], $requestData['integracao'])) {
                    $response = ClientOrders::getOrders($requestData['identificador'], $requestData['integracao']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields for getOrders.");
                }
                break;
            case 'getDeliveryInfoByNumeroParada':
                if (isset($requestData['numero_parada'])) {
                    $response = OrdersDeliveryController::getDeliveryInfoByNumeroParada($requestData['numero_parada']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields for getDeliveryInfoByNumeroParada.");
                }
                break;
            case 'changeStatusPedido':
                if (isset($requestData['cnpj'], $requestData['hash'], $requestData['num_controle'], $requestData['status'])) {
                    $response = OrdersDeliveryController::changeStatusPedido(
                        $requestData['cnpj'], $requestData['hash'], $requestData['num_controle'], $requestData['status']
                    );
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields for changeStatusPedido.");
                }
                break;
            case 'getPedidoByChave':
                if (isset($requestData['chave_pedido'])) {
                    $response = OrdersDeliveryController::getPedidoByChave($requestData['chave_pedido']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields for changeStatusPedido.");
                }
                break;

            // Métodos para JobsController
            case 'fetchSolicitacoesSemRastreio':
                if (isset($requestData['empresa_id'])) {
                    $response = JobsController::fetchSolicitacoesSemRastreio($requestData['empresa_id']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required field: empresa_id.");
                }
            break;
            case 'fetchLinksFromAPI':
                if (isset($requestData['solicitacao_id'])) {
                    $response = JobsController::fetchLinksFromAPI($requestData['solicitacao_id']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required field: solicitacao_id.");
                }
            break;
            case 'logPayload':
                if (isset($requestData['payload'])) {
                    $response = JobsController::logPayload($requestData['payload']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required field: payload.");
                }
            break;
            case 'sendWhatsapp':
                if (isset($requestData['parada_id'], $requestData['cod'], $requestData['link_rastreio'])) {
                    $response = JobsController::sendWhatsapp($requestData['parada_id'], $requestData['cod'], $requestData['link_rastreio']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields for sendWhatsapp.");
                }
            break;
            case 'updateParadas':
                if (isset($requestData['parada_id'], $requestData['link_rastreio'])) {
                    $response = JobsController::updateParadas($requestData['parada_id'], $requestData['link_rastreio']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields for updateParadas.");
                }
            break;
            case 'salvarLogWhatsapp':
                if (isset($requestData['mensagem'], $requestData['retorno'])) {
                    $response = JobsController::salvarLogWhatsapp($requestData['mensagem'], $requestData['retorno']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields for salvarLogWhatsapp.");
                }
                break;

            // Métodos para NpsController
            case 'CreateQuestion':
                if (isset($requestData['perguntas']) && is_array($requestData['perguntas'])) {
                    $response = NpsController::CreateQuestion($requestData);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing or invalid field: perguntas.");
                }
                break;

            case 'IndexQuestions':
                $response = NpsController::ListQuestions();
                break;

            case 'ListQuestionsActive':
                if(isset($requestData['formulario'])) {
                    $response = NpsController::ListQuestionsActive($requestData['formulario'], $requestData['tipo'] ?? null, $requestData['modo_venda'] ?? null);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required field: formulario.");
                }
                break;

                case 'ListQuestionsActiveDash':
                    if(isset($requestData['formulario'])) {
                        $response = NpsController::ListQuestionsActiveDash($requestData['formulario'], $requestData['tipo']);
                    } else {
                        http_response_code(400);
                        throw new Exception("Missing required field: formulario.");
                    }
                    break;

            case 'ShowQuestion':
                if (isset($requestData['id'])) {
                    $response = NpsController::ShowQuestion($requestData['id']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required field: id.");
                }
                break;

                case 'UpdateOrdemPerguntas':
                if (isset($requestData)) {
                    $response = NpsController::UpdateOrdemPerguntas($requestData);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required field: id.");
                }
                break;

            case 'UpdateQuestion':
                if (isset($requestData['perguntas']) && is_array($requestData['perguntas'])) {
                    $response = NpsController::UpdateQuestion($requestData['perguntas'][0]);

                } else {
                    http_response_code(400);
                    throw new Exception("Missing or invalid field: perguntas.");
                }
                break;

            case 'DeleteQuestion':
                if (isset($requestData['id'])) {
                    $response = NpsController::DeleteQuestion($requestData['id']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required field: id.");
                }
                break;
            case 'CreateRespostas':
                if (isset($requestData['chave_pedido'], $requestData['respostas']) && is_array($requestData['respostas'])) {
                    $response = NpsController::CreateRespostas($requestData);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing or invalid fields for CreateRespostas.");
                }
                break;
                case 'CreateRespostasMesa':
                    if (isset($requestData['chave_mesa'], $requestData['respostas']) && is_array($requestData['respostas'])) {
                        $response = NpsController::CreateRespostasMesa($requestData);
                    } else {
                        http_response_code(400);
                        throw new Exception("Missing or invalid fields for CreateRespostasMesa.");
                    }
                    break;

            case 'ListarRespostasPorChavePedido':
                if (isset($requestData['chave_pedido'])) {
                    $response = NpsController::ListarRespostasPorChavePedido($requestData['chave_pedido']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing field: chave_pedido.");
                }
                break;
            case 'FormularioJaRespondido':
                if (isset($requestData['chave_pedido'], $requestData['formulario'])) {
                    $response = NpsController::FormularioJaRespondido(
                        $requestData['chave_pedido'],
                        $requestData['formulario']
                    );
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields: chave_pedido and formulario.");
                }
                break;
            case 'salvarLogNps':
                if (isset($requestData['mensagem'], $requestData['retorno'])) {
                    $response = JobsController::salvarLogNps($requestData['mensagem'], $requestData['retorno']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields for salvarLogWhatsapp.");
                }
                break;
            case 'UploadArquivo':
                $response = NpsController::UploadArquivo();
                break;
            case 'getOrdersToNps':
                $response = JobsController::getOrdersToNps();
                break;
            case 'getOrdersToNpsF':
                $response = JobsController::getOrdersToNpsF();
                break;
            case 'sendNpsToQueue':
                if (is_array($requestData)) {
                    $response = JobsController::sendNpsToQueue($requestData);
                } else {
                    print_r($requestData['data']);
                    http_response_code(400);
                    throw new Exception("Missing or invalid field: data (expected array of pedidos).");
                }
                break;
            case 'ListarTodasAsRespostas':
                if (isset($requestData['dt_inicio']) && isset($requestData['dt_fim'])) {
                    $dt_inicio = $requestData['dt_inicio'];
                    $dt_fim = $requestData['dt_fim'];

                    if (strtotime($dt_inicio) > strtotime($dt_fim)) {
                        http_response_code(400);
                        $response = ['success' => false, 'error' => 'A data inicial não pode ser maior que a data final.'];
                    } else {
                        $response = NpsController::ListarTodasAsRespostas($dt_inicio, $dt_fim);
                    }
                } else {
                    http_response_code(400);
                    $response = ['success' => false, 'error' => 'Parâmetros obrigatórios: dt_inicio e dt_fim.'];
                }
                break;
            case 'ListarAgrupadoPorPedido':
                $response = NpsController::ListarAgrupadoPorPedido();
                break;
            case 'GetDetalhesDoPedido':
                if (isset($requestData['chave_pedido'])) {
                    $response = OrdersDeliveryController::GetDetalhesDoPedido($requestData['chave_pedido']);
                } else {
                    http_response_code(400);
                    throw new Exception("Missing field: chave_pedido.");
                }
                break;
                case 'GetDetalhesMesa':
                    if (isset($requestData['chave_pedido'])) {
                        $response = OrdersDeliveryController::GetDetalhesMesa($requestData['chave_pedido']);
                    } else {
                        http_response_code(400);
                        throw new Exception("Missing field: chave_pedido.");
                    }
                    break;
            case 'ListarPedidosPorTelefone':
                if (isset($requestData['telefone'])) {
                    $response = OrdersDeliveryController::ListarPedidosPorTelefone($requestData['telefone']);
                } else {
                    http_response_code(400);
                    $response = ['success' => false, 'error' => 'Telefone não fornecido'];
                }
                break;
                case 'dashNps':
                if (isset($requestData['dt_inicio']) && isset($requestData['dt_fim'])) {
                    $dt_inicio = $requestData['dt_inicio'];
                    $dt_fim = $requestData['dt_fim'];
                    if (strtotime($dt_inicio) > strtotime($dt_fim)) {
                        http_response_code(400);
                        $response = ['success' => false, 'error' => 'A data inicial não pode ser maior que a data final.'];
                    } else {
                        $response = NpsController::dashNps($dt_inicio, $dt_fim);
                    }
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields: dt_inicio and dt_fim.");
                }
                break;
                case 'ListQuestions':
                    $response = NpsController::ListQuestions();
                    break;

            case 'createConsulta':
                if (
                    isset($requestData['nome']) &&
                    isset($requestData['sql_template'])
                ) {
                    $descricao = $requestData['descricao'] ?? null;
                    $response = ConsultaController::CreateConsulta(
                        $requestData['nome'],
                        $requestData['sql_template'],
                        $descricao
                    );
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required fields: nome and sql_template.");
                }
                break;

            case 'getConsultas':
                $response = ConsultaController::GetConsultas();
                break;

            case 'executarConsulta':
                if (isset($requestData['nome'])) {
                    $response = ConsultaController::executarConsultaPorNome(
                        $requestData['nome'],
                        $requestData['data_inicial'] ?? null,
                        $requestData['data_final'] ?? null
                    );
                } else {
                    http_response_code(400);
                    throw new Exception("Missing required field: nome.");
                }
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
?>
