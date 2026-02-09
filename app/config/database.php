<?php

declare(strict_types=1); // Ativa tipagem estrita (mais segurança e menos erros silenciosos)

// Carrega as constantes e configs globais (DB_HOST, DB_PORT, APP_ENV, etc.)
require_once __DIR__ . '/config.php';

final class Database
{
    /**
     * Guarda a conexão PDO única (Singleton).
     * - static: compartilhado por toda a aplicação nesta requisição
     * - ?PDO: pode ser PDO ou null (antes de conectar)
     */
    private static ?PDO $connection = null;

    /**
     * Retorna a conexão PDO.
     * - Se ainda não existir, cria.
     * - Se já existir, reutiliza.
     */
    public static function getConnection(): PDO
    {
        // Se a conexão já foi criada antes, reaproveita
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        /**
         * DSN (Data Source Name)
         * Monta a string de conexão do PDO para MySQL:
         * - host: DB_HOST (ex.: localhost)
         * - port: DB_PORT (ex.: 3306)
         * - dbname: DB_NAME (ex.: animal_sos)
         * - charset: DB_CHARSET (ex.: utf8mb4)
         */
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        try {
            /**
             * Cria a conexão PDO.
             *
             * Opções importantes:
             * - ERRMODE_EXCEPTION: lança exceções ao invés de warnings silenciosos
             * - FETCH_ASSOC: retorna resultados como array associativo por padrão
             * - EMULATE_PREPARES=false: usa prepared statements nativos do MySQL
             */
            self::$connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            /**
             * Tratamento de erro de conexão:
             *
             * Em produção (prod):
             * - Não expõe detalhes do erro (segurança)
             * - Retorna 500 e mensagem genérica
             *
             * Em desenvolvimento (local):
             * - Lança RuntimeException com detalhes para facilitar o debug
             */
            if (APP_ENV === 'prod') {
                http_response_code(500);
                exit('Erro ao conectar no banco de dados.');
            }

            throw new RuntimeException(
                'Falha na conexão com o banco: ' . $e->getMessage(),
                (int)$e->getCode()
            );
        }

        // Retorna a conexão recém-criada
        return self::$connection;
    }

    /**
     * Construtor privado:
     * Impede que alguém faça "new Database()".
     * Força o uso de Database::getConnection().
     */
    private function __construct() {}

    /**
     * Clone privado:
     * Impede duplicar o Singleton via clone.
     */
    private function __clone() {}
}
