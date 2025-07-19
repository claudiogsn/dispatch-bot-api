<?php
date_default_timezone_set('America/Rio_Branco');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db.php';

class NpsController
{
  public static function CreateQuestion($data): array
{
    global $pdo;

    $created_at = date('Y-m-d H:i:s');

    if (!isset($data['perguntas']) || !is_array($data['perguntas'])) {
        throw new Exception("Campo 'perguntas' ausente ou inválido.");
    }

    $ids = [];

    foreach ($data['perguntas'] as $pergunta) {
        if (!isset($pergunta['formulario'], $pergunta['titulo'], $pergunta['metodo_resposta'])) {
            continue;
        }

        // Buscar a maior ordem atual do formulário
        $stmtMax = $pdo->prepare("SELECT MAX(ordem) FROM formulario_perguntas WHERE formulario = :formulario");
        $stmtMax->execute([':formulario' => $pergunta['formulario']]);
        $maiorOrdem = (int)$stmtMax->fetchColumn();
        $novaOrdem = $maiorOrdem + 1;

        $sql = "INSERT INTO formulario_perguntas (
                    formulario, titulo, ordem, subtitulo_delivery, subtitulo_mesa, metodo_resposta,
                    ativo, obrigatoria, delivery, mesa, created_at, updated_at
                ) VALUES (
                    :formulario, :titulo, :ordem, :subtitulo_delivery, :subtitulo_mesa, :metodo_resposta,
                    :ativo, :obrigatoria, :delivery, :mesa, :created_at, :updated_at
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':formulario'          => $pergunta['formulario'],
            ':titulo'              => $pergunta['titulo'],
            ':ordem'               => $novaOrdem,
            ':subtitulo_delivery'  => $pergunta['subtitulo_delivery'] ?? null,
            ':subtitulo_mesa'      => $pergunta['subtitulo_mesa'] ?? null,
            ':metodo_resposta'     => $pergunta['metodo_resposta'],
            ':ativo'               => $pergunta['ativo'] ?? 1,
            ':obrigatoria'         => $pergunta['obrigatoria'] ?? 0,
            ':delivery'            => $pergunta['delivery'] ?? 0,
            ':mesa'                => $pergunta['mesa'] ?? 0,
            ':created_at'          => $created_at,
            ':updated_at'          => $created_at
        ]);

        $ids[] = $pdo->lastInsertId();
    }

    return ['created_ids' => $ids];
}




    public static function ListQuestions()
    {
        global $pdo;

        $stmt = $pdo->query("SELECT * FROM formulario_perguntas ORDER BY ordem ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function UpdateOrdemPerguntas($data): array
    {
        global $pdo;

        if (!isset($data['perguntas']) || !is_array($data['perguntas'])) {
            throw new Exception("Campo 'perguntas' ausente ou inválido.");
        }

        $updated = 0;

        foreach ($data['perguntas'] as $p) {
            if (!isset($p['id'], $p['ordem'])) continue;

            $stmt = $pdo->prepare("UPDATE formulario_perguntas SET ordem = :ordem, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute([
                ':ordem' => $p['ordem'],
                ':id' => $p['id']
            ]);

            $updated += $stmt->rowCount();
        }

        return ['updated' => $updated];
    }


   public static function ListQuestionsActive($formulario, $tipo = null, $modo_venda = null)
    {
        global $pdo;

        $query = "
            SELECT
                id,
                ordem,
                formulario,
                titulo,
                metodo_resposta,
                obrigatoria,
                ativo,
                delivery,
                mesa,
                created_at,
                updated_at,
                CASE
                    WHEN :modo_venda = 'mesa' THEN subtitulo_mesa
                    ELSE subtitulo_delivery
                END AS subtitulo
            FROM formulario_perguntas
            WHERE formulario = :formulario
              AND ativo = 1
        ";

        $params = [':formulario' => $formulario, ':modo_venda' => $modo_venda ?? 'delivery'];

        if ($tipo) {
            $query .= " AND metodo_resposta = :tipo";
            $params[':tipo'] = $tipo;
        }

        if ($modo_venda === 'mesa') {
            $query .= " AND mesa = 1";
        } else {
            $query .= " AND delivery = 1";
        }

        $query .= " ORDER BY ordem ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

public static function ListQuestionsActiveDash($formulario,$tipo)
    {
        global $pdo;

        $query = "
            SELECT
               *
            FROM formulario_perguntas
            WHERE formulario = :formulario
              AND metodo_resposta = :metodo_resposta
              AND ativo = 1
        ";

        $params = [':formulario' => $formulario,':metodo_resposta'=> $tipo];

        $query .= " ORDER BY ordem ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public static function ShowQuestion($id)
    {
        global $pdo;

        $stmt = $pdo->prepare("SELECT * FROM formulario_perguntas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function UpdateQuestionArray($data): array
    {
        global $pdo;

        if (!isset($data['perguntas']) || !is_array($data['perguntas'])) {
            throw new Exception("Campo 'perguntas' ausente ou inválido.");
        }

        $updated = 0;

        foreach ($data['perguntas'] as $pergunta) {
            if (
                !isset($pergunta['id'], $pergunta['formulario'], $pergunta['titulo'], $pergunta['metodo_resposta'])
            ) {
                continue;
            }

           $sql = "UPDATE formulario_perguntas SET 
                formulario = :formulario,
                titulo = :titulo,
                ordem = :ordem,
                subtitulo_delivery = :subtitulo_delivery,
                subtitulo_mesa = :subtitulo_mesa,
                metodo_resposta = :metodo_resposta,
                ativo = :ativo,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':formulario' => $pergunta['formulario'],
                ':titulo' => $pergunta['titulo'],
                ':ordem' => $pergunta['ordem'] ?? null,
                ':subtitulo_delivery' => $pergunta['subtitulo_delivery'] ?? null,
                ':subtitulo_mesa' => $pergunta['subtitulo_mesa'] ?? null,
                ':metodo_resposta' => $pergunta['metodo_resposta'],
                ':ativo' => $pergunta['ativo'] ?? 1,
                ':id' => $pergunta['id']
            ]);

            $updated += $stmt->rowCount();
        }

        return ['updated_count' => $updated];
    }

public static function UpdateQuestion(array $pergunta): array
{
    global $pdo;

    if (!isset($pergunta['id'])) {
        throw new Exception("Campo 'id' é obrigatório.");
    }

    $camposPermitidos = [
        'formulario',
        'titulo',
        'subtitulo_delivery',
        'subtitulo_mesa',
        'metodo_resposta',
        'ativo',
        'obrigatoria',
        'delivery',
        'mesa'
    ];

    $setClauses = [];
    $params = [':id' => $pergunta['id']];

    foreach ($camposPermitidos as $campo) {
        if (isset($pergunta[$campo])) {
            $setClauses[] = "$campo = :$campo";
            $params[":$campo"] = $pergunta[$campo];
        }
    }

    if (empty($setClauses)) {
        throw new Exception("Nenhum campo para atualizar foi fornecido.");
    }

    // Sempre atualiza o campo updated_at
    $setClauses[] = "updated_at = CURRENT_TIMESTAMP";

    $sql = "UPDATE formulario_perguntas SET " . implode(', ', $setClauses) . " WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return ['updated_count' => $stmt->rowCount()];
}



    public static function DeleteQuestion($id): array
    {
        global $pdo;

        $stmt = $pdo->prepare("DELETE FROM formulario_perguntas WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return ['deleted' => $stmt->rowCount()];
    }


    public static function CreateRespostas($data)
    {
        $dataHora = date('Y-m-d H:i:s');
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $infoDispositivo = self::detectarDispositivo($userAgent);
        $tipoDispositivo = $infoDispositivo['tipo'];
        $plataforma = $infoDispositivo['plataforma'];

        // Bloqueia chamadas feitas por ferramentas de testes
        if (
            stripos($userAgent, 'postman') !== false ||
            stripos($userAgent, 'insomnia') !== false ||
            stripos($userAgent, 'thunder-client') !== false
        ) {
            http_response_code(403);
            return ['success' => false, 'error' => 'Acesso negado via ferramenta de teste'];
        }

        global $pdo;

        $chave_pedido = $data['chave_pedido'] ?? null;
        $respostas = $data['respostas'] ?? [];
        $ip = $_SERVER['REMOTE_ADDR'];
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;

        if (!$chave_pedido || !is_array($respostas) || count($respostas) === 0) {
            http_response_code(400);
            return ['success' => false, 'error' => 'Chave do pedido ou respostas inválidas.'];
        }

        try {
            $pdo->beginTransaction();

            // Busca o CNPJ e o nome da loja com base na chave do pedido
            $stmtPedido = $pdo->prepare("
            SELECT od.cnpj, e.nome_fantasia as nome_loja
            FROM orders_delivery od
            LEFT JOIN estabelecimento e ON e.cnpj = od.cnpj
            WHERE od.chave_pedido = :chave_pedido
            LIMIT 1
        ");
            $stmtPedido->execute([':chave_pedido' => $chave_pedido]);
            $dadosLoja = $stmtPedido->fetch(PDO::FETCH_ASSOC);

            $cnpj = $dadosLoja['cnpj'] ?? null;
            $nomeLoja = $dadosLoja['nome_loja'] ?? null;

            foreach ($respostas as $resposta) {
                if (!isset($resposta['pergunta_id'])) {
                    throw new Exception("Cada resposta deve conter 'pergunta_id'.");
                }

                $pergunta_id = $resposta['pergunta_id'];
                $resposta_valor = $resposta['resposta'] ?? null;

                // Trata string vazia, espaços e array vazia como null
                if (is_array($resposta_valor) && count($resposta_valor) === 0) {
                    $resposta_valor = null;
                }
                if (is_string($resposta_valor) && trim($resposta_valor) === '') {
                    $resposta_valor = null;
                }

                // Verifica se a pergunta existe e se é obrigatória
                $stmtObrigatoria = $pdo->prepare("SELECT obrigatoria FROM formulario_perguntas WHERE id = :id LIMIT 1");
                $stmtObrigatoria->execute([':id' => $pergunta_id]);
                $obrigatoria = $stmtObrigatoria->fetchColumn();

                if ($obrigatoria === false) {
                    throw new Exception("Pergunta ID $pergunta_id não encontrada.");
                }

                // Se for obrigatória e a resposta for nula, lança erro
                if ((int)$obrigatoria === 1 && $resposta_valor === null) {
                    http_response_code(400);
                    throw new Exception("A pergunta ID $pergunta_id é obrigatória e não foi respondida.");
                }

                // Insere a resposta com CNPJ e nome da loja
                $stmtInsert = $pdo->prepare("
                INSERT INTO formulario_respostas (
                    pergunta_id, 
                    chave_pedido, 
                    resposta, 
                    ip, 
                    user_agent, 
                    tipo_dispositivo, 
                    plataforma,
                    latitude,
                    longitude,
                    created_at,
                    updated_at,
                    cnpj,
                    nome_loja
                )
                VALUES (
                    :pergunta_id, 
                    :chave_pedido, 
                    :resposta, 
                    :ip, 
                    :user_agent, 
                    :tipo_dispositivo, 
                    :plataforma,
                    :latitude,
                    :longitude,
                    :created_at,
                    :updated_at,
                    :cnpj,
                    :nome_loja
                )
            ");

                $stmtInsert->execute([
                    ':pergunta_id' => $pergunta_id,
                    ':chave_pedido' => $chave_pedido,
                    ':resposta' => $resposta_valor,
                    ':ip' => $ip,
                    ':user_agent' => $userAgent,
                    ':tipo_dispositivo' => $tipoDispositivo,
                    ':plataforma' => $plataforma,
                    ':latitude' => $latitude,
                    ':longitude' => $longitude,
                    ':created_at' => $dataHora,
                    ':updated_at' => $dataHora,
                    ':cnpj' => $cnpj,
                    ':nome_loja' => $nomeLoja
                ]);
            }

            // Marcar como respondido via controller centralizado
            OrdersDeliveryController::marcarNpsComoRespondido($chave_pedido);

            $pdo->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(400);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function CreateRespostasMesa($data)
    {
        $dataHora = date('Y-m-d H:i:s');
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $infoDispositivo = self::detectarDispositivo($userAgent);
        $tipoDispositivo = $infoDispositivo['tipo'];
        $plataforma = $infoDispositivo['plataforma'];

        if (
            stripos($userAgent, 'postman') !== false ||
            stripos($userAgent, 'insomnia') !== false ||
            stripos($userAgent, 'thunder-client') !== false
        ) {
            http_response_code(403);
            return ['success' => false, 'error' => 'Acesso negado via ferramenta de teste'];
        }

        global $pdo;

        $chave_mesa = $data['chave_mesa'] ?? null;
        $nomeLoja = $data['nome_loja'] ?? null;
        $respostas = $data['respostas'] ?? [];
        $ip = $_SERVER['REMOTE_ADDR'];
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;

        if (!$chave_mesa || !$nomeLoja || !is_array($respostas) || count($respostas) === 0) {
            http_response_code(400);
            return ['success' => false, 'error' => 'Dados obrigatórios ausentes (chave_mesa, nome_loja ou respostas).'];
        }

        try {
            $pdo->beginTransaction();

            // Buscar o CNPJ com base no nome da loja
            $stmtCnpj = $pdo->prepare("SELECT cnpj FROM estabelecimento WHERE nome_loja = :nome_loja LIMIT 1");
            $stmtCnpj->execute([':nome_loja' => $nomeLoja]);
            $cnpj = $stmtCnpj->fetchColumn();

            if (!$cnpj) {
                throw new Exception("CNPJ não encontrado para a loja '$nomeLoja'.");
            }

            foreach ($respostas as $resposta) {
                if (!isset($resposta['pergunta_id'])) {
                    throw new Exception("Cada resposta deve conter 'pergunta_id'.");
                }

                $pergunta_id = $resposta['pergunta_id'];
                $resposta_valor = $resposta['resposta'] ?? null;

                if (is_array($resposta_valor) && count($resposta_valor) === 0) {
                    $resposta_valor = null;
                }
                if (is_string($resposta_valor) && trim($resposta_valor) === '') {
                    $resposta_valor = null;
                }

                $stmtObrigatoria = $pdo->prepare("SELECT obrigatoria FROM formulario_perguntas WHERE id = :id LIMIT 1");
                $stmtObrigatoria->execute([':id' => $pergunta_id]);
                $obrigatoria = $stmtObrigatoria->fetchColumn();

                if ($obrigatoria === false) {
                    throw new Exception("Pergunta ID $pergunta_id não encontrada.");
                }

                if ((int)$obrigatoria === 1 && $resposta_valor === null) {
                    http_response_code(400);
                    throw new Exception("A pergunta ID $pergunta_id é obrigatória e não foi respondida.");
                }

                $stmtInsert = $pdo->prepare("
                    INSERT INTO formulario_respostas (
                        pergunta_id,
                        chave_pedido,
                        resposta,
                        ip,
                        user_agent,
                        tipo_dispositivo,
                        plataforma,
                        latitude,
                        longitude,
                        created_at,
                        updated_at,
                        nome_loja,
                        cnpj,
                        modo_venda
                    ) VALUES (
                        :pergunta_id,
                        :chave_pedido,
                        :resposta,
                        :ip,
                        :user_agent,
                        :tipo_dispositivo,
                        :plataforma,
                        :latitude,
                        :longitude,
                        :created_at,
                        :updated_at,
                        :nome_loja,
                        :cnpj,
                        'MESA'
                    )
                ");

                $stmtInsert->execute([
                    ':pergunta_id' => $pergunta_id,
                    ':chave_pedido' => $chave_mesa,
                    ':resposta' => $resposta_valor,
                    ':ip' => $ip,
                    ':user_agent' => $userAgent,
                    ':tipo_dispositivo' => $tipoDispositivo,
                    ':plataforma' => $plataforma,
                    ':latitude' => $latitude,
                    ':longitude' => $longitude,
                    ':created_at' => $dataHora,
                    ':updated_at' => $dataHora,
                    ':nome_loja' => $nomeLoja,
                    ':cnpj' => $cnpj
                ]);
            }

            $pdo->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(400);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function detectarDispositivo ($userAgent): array
    {
        $userAgent = strtolower($userAgent);

        if (strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false || strpos($userAgent, 'ipod') !== false) {
            return ['tipo' => 'mobile', 'plataforma' => 'iOS'];
        }

        if (strpos($userAgent, 'android') !== false) {
            return ['tipo' => 'mobile', 'plataforma' => 'Android'];
        }

        if (strpos($userAgent, 'windows') !== false) {
            return ['tipo' => 'desktop', 'plataforma' => 'Windows'];
        }

        if (strpos($userAgent, 'macintosh') !== false) {
            return ['tipo' => 'desktop', 'plataforma' => 'macOS'];
        }

        if (strpos($userAgent, 'linux') !== false) {
            return ['tipo' => 'desktop', 'plataforma' => 'Linux'];
        }

        return ['tipo' => 'desconhecido', 'plataforma' => 'desconhecida'];
    }

    public static function ListarRespostasPorChavePedido($chave_pedido)
    {
        global $pdo;

        if (!$chave_pedido) {
            throw new Exception("Campo 'chave_pedido' é obrigatório.");
        }

        $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.pergunta_id,
            p.formulario,
            p.titulo,
            p.subtitulo,
            p.metodo_resposta,
            r.resposta,
            r.ip,
            r.created_at
        FROM formulario_respostas r
        JOIN formulario_perguntas p ON p.id = r.pergunta_id
        WHERE r.chave_pedido = :chave_pedido
        ORDER BY r.created_at
    ");

        $stmt->execute([':chave_pedido' => $chave_pedido]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function FormularioJaRespondido($chave_pedido, $formulario): array
    {
        global $pdo;

        if (!$chave_pedido || !$formulario) {
            throw new Exception("Campos 'chave_pedido' e 'formulario' são obrigatórios.");
        }

        $stmt = $pdo->prepare("
        SELECT 1
        FROM formulario_respostas r
        JOIN formulario_perguntas p ON p.id = r.pergunta_id
        WHERE r.chave_pedido = :chave_pedido
          AND p.formulario = :formulario
        LIMIT 1
        ");

        $stmt->execute([
            ':chave_pedido' => $chave_pedido,
            ':formulario' => $formulario
        ]);

        if ($stmt->fetchColumn()) {
            return ['respondido' => true];
        } else {
            return ['respondido' => false];
        }
    }

    public static function UploadArquivo()
    {
        if (!isset($_FILES['file']) || !isset($_POST['nome'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Arquivo ou nome não enviado.']);
            exit;
        }

        $file = $_FILES['file'];
        $nomeBase = preg_replace('/[^a-zA-Z0-9\\-_]/', '', $_POST['nome']);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $nomeBase . '.' . strtolower($ext);
        $destino = __DIR__ . '/../uploads/' . $filename;

        if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'mp4', 'mov', 'webm'])) {
            http_response_code(415);
            echo json_encode(['error' => 'Formato de arquivo não suportado.']);
            exit;
        }

        if ($file['size'] > 25 * 1024 * 1024) {
            http_response_code(413);
            echo json_encode(['error' => 'Arquivo excede o limite de 25MB.']);
            exit;
        }

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao mover o arquivo.']);
            exit;
        }

        // URL pública (ajuste se necessário)
        $url = 'https://vemprodeck.com.br/uploads/' . $filename;

        return json_encode(['success' => true, 'url' => $url]);
    }

    public static function ListarRespostasPorPedido($data): array
    {
        global $pdo;

        $chave_pedido = $data['chave_pedido'] ?? null;

        if (!$chave_pedido) {
            http_response_code(400);
            return ['success' => false, 'error' => 'Chave do pedido não fornecida.'];
        }

        try {
            $stmt = $pdo->prepare("
            SELECT 
                r.id,
                r.pergunta_id,
                p.titulo AS pergunta,
                r.resposta,
                r.created_at,
                r.ip,
                r.user_agent,
                r.tipo_dispositivo,
                r.plataforma,
                r.latitude,
                r.longitude
            FROM formulario_respostas r
            LEFT JOIN formulario_perguntas p ON p.id = r.pergunta_id
            WHERE r.chave_pedido = :chave_pedido
            ORDER BY r.created_at ASC
        ");
            $stmt->execute([':chave_pedido' => $chave_pedido]);
            $respostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'respostas' => $respostas];
        } catch (Exception $e) {
            http_response_code(500);
            return ['success' => false, 'error' => 'Erro ao buscar respostas: ' . $e->getMessage()];
        }
    }

    public static function ListarTodasAsRespostas($dt_inicio,$dt_fim): array
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("
            SELECT 
                r.id,
                r.chave_pedido,
                r.modo_venda,
                r.pergunta_id,
                p.titulo AS pergunta,
                r.resposta,
                p.metodo_resposta,
                r.created_at,
                r.ip,
                r.tipo_dispositivo,
                r.plataforma,
                r.latitude,
                r.longitude,
                r.nome_loja            FROM formulario_respostas r
            LEFT JOIN formulario_perguntas p ON p.id = r.pergunta_id
            WHERE r.resposta IS NOT NULL
            AND r.created_at BETWEEN :dt_inicio AND :dt_fim
            ORDER BY r.created_at DESC
        ");
            $stmt->execute([':dt_inicio' => $dt_inicio, ':dt_fim' => $dt_fim]);
            $respostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'respostas' => $respostas];
        } catch (Exception $e) {
            http_response_code(500);
            return ['success' => false, 'error' => 'Erro ao buscar respostas: ' . $e->getMessage()];
        }
    }
    public static function ListarAgrupadoPorPedido(): array
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("
            SELECT 
                r.chave_pedido,
                r.nome_loja,
                r.modo_venda,
                r.cnpj,
                r.created_at,
                r.ip,
                r.user_agent,
                r.tipo_dispositivo,
                r.plataforma,
                r.latitude,
                r.longitude,
                r.pergunta_id,
                p.titulo AS pergunta,
                r.resposta,
                od.identificador_conta,
                od.telefone,
                od.tipo_entrega,
                od.cod_iapp,
                p.metodo_resposta AS tipo_resposta
            FROM formulario_respostas r
            LEFT JOIN formulario_perguntas p ON p.id = r.pergunta_id
            LEFT JOIN orders_delivery od ON od.chave_pedido = r.chave_pedido
            ORDER BY r.chave_pedido, r.created_at
        ");
            $stmt->execute();
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $agrupados = [];

            foreach ($resultados as $row) {
                $chave = $row['chave_pedido'];

                if (!isset($agrupados[$chave])) {
                    $agrupados[$chave] = [
                        'chave_pedido' => $row['chave_pedido'],
                        'cod_iapp' => $row['cod_iapp'],
                        'cnpj' => $row['cnpj'],
                        'modo_venda' => $row['modo_venda'],
                        'nome_loja' => $row['nome_loja'],
                        'nome_cliente' => ucwords(trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-zA-ZÀ-ÿ\s]/u', '', $row['identificador_conta'])))),
                        'telefone' => OrdersDeliveryController::formatarTelefone($row['telefone']),
                        'tipo_entrega' => $row['tipo_entrega'],
                        'created_at' => OrdersDeliveryController::formatarDataHora($row['created_at']),
                        'ip' => $row['ip'],
                        'user_agent' => $row['user_agent'],
                        'tipo_dispositivo' => $row['tipo_dispositivo'],
                        'plataforma' => $row['plataforma'],
                        'latitude' => $row['latitude'],
                        'longitude' => $row['longitude'],
                        'respostas' => []
                    ];
                }

                $agrupados[$chave]['respostas'][] = [
                    'pergunta_id' => $row['pergunta_id'],
                    'pergunta' => $row['pergunta'],
                    'resposta' => $row['resposta'],
                    'tipo_resposta' => $row['tipo_resposta']
                ];
            }

            return ['success' => true, 'dados' => array_values($agrupados)];
        } catch (Exception $e) {
            http_response_code(500);
            return ['success' => false, 'error' => 'Erro ao buscar respostas agrupadas: ' . $e->getMessage()];
        }
    }
    public static function dashNps($dt_inicio, $dt_fim)
{
    global $pdo;

    if (!$dt_inicio || !$dt_fim) {
        http_response_code(400);
        return ['success' => false, 'error' => 'Parâmetros dt_inicio e dt_fim são obrigatórios.'];
    }

    try {
        // Total de respostas distintas (por pedido)
        $stmtRespostas = $pdo->prepare("
            SELECT COUNT(DISTINCT chave_pedido) AS total, nome_loja
            FROM formulario_respostas
            WHERE resposta IS NOT NULL
              AND created_at BETWEEN :inicio AND :fim
            group by nome_loja
        ");
        $stmtRespostas->execute([
            ':inicio' => $dt_inicio,
            ':fim' => $dt_fim
        ]);
        $totalRespostas = $stmtRespostas->fetchAll(PDO::FETCH_ASSOC);

        // Total de pedidos no período
        $stmtPedidos = $pdo->prepare("
            SELECT 
                od.cnpj,
                e.nome_loja,
                COUNT(*) AS total_pedidos
            FROM orders_delivery od
            LEFT JOIN (
                SELECT cnpj, MIN(nome_fantasia) AS nome_loja
                FROM estabelecimento
                GROUP BY cnpj
            ) e ON e.cnpj = od.cnpj
            WHERE od.hora_abertura BETWEEN :inicio AND :fim
            AND intg_tipo = 'DELIVERY-DIRETO'
            GROUP BY od.cnpj, e.nome_loja
            ORDER BY total_pedidos DESC;
                    ");
        $stmtPedidos->execute([
            ':inicio' => $dt_inicio,
            ':fim' => $dt_fim
        ]);
        $totalPedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);

        // 👉 Reutiliza o método existente
        $respostasDetalhadas = self::ListarTodasAsRespostas($dt_inicio, $dt_fim);

        return [
            'success' => true,
            'estatisticas' => [
                'total_respostas' => $totalRespostas,
                'total_pedidos' => $totalPedidos,
            ],
            'respostas' => $respostasDetalhadas['respostas'] ?? []
        ];
    } catch (Exception $e) {
        http_response_code(500);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

}
