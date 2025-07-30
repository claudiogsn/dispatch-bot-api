<?php
date_default_timezone_set('America/Rio_Branco');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db.php';

class BIController {

    public static function getContasBi($data_inicial, $data_final) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM bi_conta WHERE dt_mov BETWEEN :data_inicial AND :data_final ');
        $stmt->bindParam(':data_inicial', $data_inicial);
        $stmt->bindParam(':data_final', $data_final);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getItensBi($data_inicial, $data_final) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM bi_itens WHERE dt_mov BETWEEN :data_inicial AND :data_final');
        $stmt->bindParam(':data_inicial', $data_inicial);
        $stmt->bindParam(':data_final', $data_final);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getContaDetalhada($cnpj, $num_controle) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM bi_conta WHERE cnpj_estabelecimento = :cnpj AND num_controle = :num_controle');
        $stmt->bindParam(':cnpj', $cnpj);
        $stmt->bindParam(':num_controle', $num_controle);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getItemDetalhado($cnpj, $num_controle) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM bi_itens WHERE cnpj_estabelecimento = :cnpj AND num_controle = :num_controle');
        $stmt->bindParam(':cnpj', $cnpj);
        $stmt->bindParam(':num_controle', $num_controle);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function createOrUpdateContas($dados) {
        global $pdo;

        if (!is_array($dados) || count($dados) === 0) {
            return ['success' => false, 'message' => 'Nenhum dado fornecido.'];
        }

        // Adiciona created_at e updated_at aos dados
        $agora = date('Y-m-d H:i:s');
        foreach ($dados as &$registro) {
            $registro['created_at'] = $agora;
            $registro['updated_at'] = $agora;
        }

        // Lista de colunas (incluindo created_at e updated_at)
        $columns = array_keys($dados[0]);

        $placeholders = [];
        $params = [];

        foreach ($dados as $i => $row) {
            $rowPlaceholders = [];
            foreach ($columns as $col) {
                $key = ":{$col}_{$i}";
                $rowPlaceholders[] = $key;
                $params[$key] = $row[$col] ?? null;
            }
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }

        $sql = "INSERT INTO bi_conta (" . implode(', ', $columns) . ") VALUES "
            . implode(', ', $placeholders)
            . " ON DUPLICATE KEY UPDATE "
            . implode(', ', array_map(fn($col) => "$col = VALUES($col)", $columns));

        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return ['success' => true, 'message' => 'Contas BI inseridas/atualizadas com sucesso.'];
    }


    public static function createOrUpdateItens($dados) {
        global $pdo;

        if (!is_array($dados) || count($dados) === 0) {
            return ['success' => false, 'message' => 'Nenhum dado fornecido.'];
        }

        $columns = [
            "nome_estabelecimento", "cnpj_estabelecimento", "num_controle", "codigo", "operacao",
            "nquant", "npreco", "caixinha", "garcon", "hora", "hora_at", "dt_mov", "mesa", "fone", "nome",
            "fantasia", "intg_tipo", "npessoas", "origem_conta", "delivered_by", "hr_fechamento_conta",
            "hora_lancamento", "hora_at_despachado", "valor_total_prod", "valor_total_prodserv",
            "nome_produto", "impressora_produto", "nome_categoria", "nome_tipo", "nome_garcom",
            "parte1_descricao", "parte2_descricao", "parte3_descricao", "parte4_descricao", "parte5_descricao",
            "parte6_descricao", "parte7_descricao", "parte8_descricao", "parte9_descricao", "parte10_descricao",
            "parte11_descricao", "parte12_descricao"
        ];

        // ⬇️ Adiciona timestamps nos dados e na lista de colunas
        $agora = date('Y-m-d H:i:s');
        foreach ($dados as &$row) {
            $row['created_at'] = $agora;
            $row['updated_at'] = $agora;
        }
        $columns[] = 'created_at';
        $columns[] = 'updated_at';

        $placeholders = [];
        $params = [];
        foreach ($dados as $i => $row) {
            $rowPlaceholders = [];
            foreach ($columns as $col) {
                $key = ":{$col}_{$i}";
                $rowPlaceholders[] = $key;
                $params[$key] = $row[$col] ?? null;
            }
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }

        $sql = "INSERT INTO bi_itens (" . implode(', ', $columns) . ") VALUES "
            . implode(', ', $placeholders)
            . " ON DUPLICATE KEY UPDATE "
            . implode(', ', array_map(fn($col) => "$col = VALUES($col)", $columns));

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return ['success' => true, 'message' => 'Itens BI inseridos/atualizados com sucesso.'];
    }

}
?>
