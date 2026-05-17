<?php

ini_set('post_max_size', '100M');
ini_set('upload_max_filesize', '100M');
ini_set('max_execution_time', '600');
ini_set('max_input_time', '600');
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../database/dbCrm.php';

class UserController {

    public static function getUserDetails($login): array
    {
        global $pdoCrm;

        $user = $login;

        // Verifica se é ID numérico
        $isId = is_numeric($user);

        if ($isId) {
            $stmt = $pdoCrm->prepare("
        SELECT id, name, login, function_name, system_unit_id, phone
        FROM system_users
        WHERE id = :user AND active = 'Y'
        LIMIT 1
    ");
            $stmt->bindParam(':user', $user, PDO::PARAM_INT);
        } else {
            $stmt = $pdoCrm->prepare("
        SELECT id, name, login, function_name, system_unit_id, phone
        FROM system_users
        WHERE login = :user AND active = 'Y'
        LIMIT 1
    ");
            $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        }

        $stmt->execute();
        $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userDetails) {
            return ['success' => false, 'message' => 'Usuário não encontrado'];
        }

        /* =========================
         * FOTO + FALLBACK
         * ========================= */
        $safeLogin = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', (string)$userDetails['login']);
        $basePhotos = "https://portal.mrksolucoes.com.br/app/images/photos";

        $userDetails['photo_url'] = "{$basePhotos}/{$safeLogin}.jpg";
        $userDetails['photo_fallback_url'] = "{$basePhotos}/default.jpg";
        $userDetails['photo_urls'] = [
            $userDetails['photo_url'],
            $userDetails['photo_fallback_url'],
        ];

        /* =========================
         * UNIDADE
         * ========================= */
        if (!empty($userDetails['system_unit_id'])) {
            $stmtUnit = $pdoCrm->prepare("
        SELECT id, name 
        FROM system_unit 
        WHERE id = :unit_id 
        LIMIT 1
    ");
            $stmtUnit->bindParam(':unit_id', $userDetails['system_unit_id'], PDO::PARAM_INT);
            $stmtUnit->execute();
            $unit = $stmtUnit->fetch(PDO::FETCH_ASSOC);

            $userDetails['unit'] = $unit ?: null;
        } else {
            $userDetails['unit'] = null;
        }

        /* =========================
         * TOKEN / SESSÃO
         * ========================= */
        $stmtLog = $pdoCrm->prepare("
    SELECT sessionid
    FROM system_access_log
    WHERE login = :login
      AND logout_time IS NULL
    ORDER BY login_time DESC
    LIMIT 1
");
        $stmtLog->bindParam(':login', $userDetails['login'], PDO::PARAM_STR);
        $stmtLog->execute();
        $lastAccess = $stmtLog->fetch(PDO::FETCH_ASSOC);

        $userDetails['token'] = $lastAccess['sessionid'] ?? null;
        $userDetails['is_logged'] = isset($lastAccess['sessionid']);

        /* =========================
         * GRUPO PADRÃO (pela unidade)
         * ========================= */
        $userDetails['group'] = null;

        if (!empty($userDetails['system_unit_id'])) {
            // Busca grupos vinculados à unidade
            $grupos = BiController::getGroupByUnit($userDetails['system_unit_id']);

            if (!empty($grupos)) {
                // pega o primeiro como padrão
                $userDetails['group'] = [
                    'id'   => $grupos[0]['id'],
                    'name' => $grupos[0]['nome'],
                    'slug' => $grupos[0]['slug'],
                ];
            }
        }

        return [
            'success' => true,
            'userDetails' => $userDetails
        ];
    }

}
?>
