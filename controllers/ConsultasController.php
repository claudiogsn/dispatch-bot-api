<?php

class ConsultaController
{
    public static function createConsulta($nome, $sql_template)
    {
        global $pdo;

        $descricao = "Consulta criada em " . date('Y-m-d H:i:s');

        try {
            $stmt = $pdo->prepare("INSERT INTO bi_consultas (nome, descricao, sql_template) VALUES (:nome, :descricao, :sql_template)");
            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao,
                ':sql_template' => $sql_template
            ]);
            return ['success' => true, 'message' => 'Consulta criada com sucesso.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao criar consulta.', 'error' => $e->getMessage()];
        }
    }

    public static function getConsultas()
    {
        global $pdo;

        try {
            $stmt = $pdo->query("SELECT * FROM bi_consultas ORDER BY nome");
            return ['success' => true, 'consultas' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao buscar consultas.', 'error' => $e->getMessage()];
        }
    }

    public static function getConsultaByNome($nome)
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("SELECT * FROM bi_consultas WHERE nome = :nome LIMIT 1");
            $stmt->execute([':nome' => $nome]);
            $consulta = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($consulta) {
                return ['success' => true, 'consulta' => $consulta];
            } else {
                return ['success' => false, 'message' => 'Consulta não encontrada.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao buscar consulta.', 'error' => $e->getMessage()];
        }
    }
    public static function executarConsultaPorNome($nome, $data_inicial = null, $data_final = null)
    {
        global $pdo;

        try {
            // Definir padrões se datas não forem informadas
            $data_final = $data_final ?: date('Y-m-d'); // hoje
            $data_inicial = $data_inicial ?: date('Y-m-d', strtotime('-1 day')); // ontem

            // Envolver em aspas simples
            $data_final_sql = "'" . $data_final . "'";
            $data_inicial_sql = "'" . $data_inicial . "'";

            // Buscar a última versão da consulta com esse nome
            $stmt = $pdo->prepare("
            SELECT sql_template 
            FROM bi_consultas 
            WHERE nome = :nome 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
            $stmt->execute([':nome' => $nome]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return ['success' => false, 'message' => 'Consulta não encontrada.'];
            }

            // Substituir os placeholders no SQL
            $sql = str_replace('{{data_inicial}}', $data_inicial_sql, $row['sql_template']);
            $sql = str_replace('{{data_final}}', $data_final_sql, $sql);

            // Retornar apenas o SQL montado
            return [
                'success' => true,
                'sql' => $sql
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao preparar consulta.',
                'error' => $e->getMessage()
            ];
        }
    }



}
