<?php
/**
 * Classe Base Model
 * Animal S.O.S - MVC em PHP Puro
 *
 * Objetivo:
 * - Centralizar a conexão PDO para todos os Models
 * - Fornecer helpers comuns (prepare, query, transação) para padronizar o acesso ao banco
 */

declare(strict_types=1);

abstract class Model
{
    /**
     * Conexão PDO compartilhada por cada instância de model.
     * Cada Model filho terá acesso via $this->db
     */
    protected PDO $db;

    public function __construct()
    {
        // Database::getConnection() é o Singleton de PDO definido em app/config/database.php
        $this->db = Database::getConnection();
    }

    /**
     * Helper para preparar uma query com segurança (prepared statements).
     * Retorna o PDOStatement para você bindar e executar.
     */
    protected function prepare(string $sql): PDOStatement
    {
        return $this->db->prepare($sql);
    }

    /**
     * Helper para executar query simples sem parâmetros.
     * Ex.: SELECT * FROM vw_estatisticas_gerais
     */
    protected function query(string $sql): PDOStatement
    {
        return $this->db->query($sql);
    }

    /**
     * Inicia uma transação.
     * Útil quando você precisa executar várias operações e garantir integridade.
     */
    protected function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /**
     * Confirma a transação.
     */
    protected function commit(): void
    {
        $this->db->commit();
    }

    /**
     * Desfaz a transação em caso de erro.
     */
    protected function rollBack(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
