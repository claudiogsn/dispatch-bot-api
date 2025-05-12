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
            if (
                !isset($pergunta['formulario'], $pergunta['titulo'], $pergunta['metodo_resposta'])
            ) {
                continue;
            }

            $sql = "INSERT INTO formulario_perguntas (formulario, titulo, subtitulo, metodo_resposta, ativo, created_at, updated_at)
                VALUES (:formulario, :titulo, :subtitulo, :metodo_resposta, :ativo, :created_at, :updated_at)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':formulario' => $pergunta['formulario'],
                ':titulo' => $pergunta['titulo'],
                ':subtitulo' => $pergunta['subtitulo'] ?? null,
                ':metodo_resposta' => $pergunta['metodo_resposta'],
                ':ativo' => $pergunta['ativo'] ?? 1,
                ':created_at' => $created_at,
                ':updated_at' => $created_at
            ]);

            $ids[] = $pdo->lastInsertId();
        }

        return ['created_ids' => $ids];
    }


    public static function ListQuestions()
    {
        global $pdo;

        $stmt = $pdo->query("SELECT * FROM formulario_perguntas ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ListQuestionsActive($formulario)
    {
        global $pdo;

        $stmt = $pdo->prepare("
        SELECT * FROM formulario_perguntas 
        WHERE formulario = :formulario 
          AND ativo = 1 
        ORDER BY created_at DESC
    ");

        $stmt->execute([':formulario' => $formulario]);
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
                        subtitulo = :subtitulo,
                        metodo_resposta = :metodo_resposta,
                        ativo = :ativo,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':formulario' => $pergunta['formulario'],
                ':titulo' => $pergunta['titulo'],
                ':subtitulo' => $pergunta['subtitulo'] ?? null,
                ':metodo_resposta' => $pergunta['metodo_resposta'],
                ':ativo' => $pergunta['ativo'] ?? 1,
                ':id' => $pergunta['id']
            ]);

            $updated += $stmt->rowCount();
        }

        return ['updated_count' => $updated];
    }

    public static function UpdateQuestion(array $pergunta ): array
    {

        global $pdo;

        if (!isset($pergunta['id'])) {
            throw new Exception("Campo 'id' é obrigatório.");
        }

        $camposPermitidos = ['formulario', 'titulo', 'subtitulo', 'metodo_resposta', 'ativo'];
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

                // Insere a resposta
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
                        longitude
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
                        :longitude
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
                    ':longitude' => $longitude
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


}
