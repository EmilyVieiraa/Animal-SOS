<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/UuidHelper.php';

final class PasswordReset extends Model
{
    private function buscarResetUnico(string $consultaSql, array $parametros = []): ?array
    {
        $declaracao = $this->prepare($consultaSql);
        $declaracao->execute($parametros);

        $registro = $declaracao->fetch();
        return $registro ?: null;
    }

    private function executarComando(string $consultaSql, array $parametros = []): bool
    {
        $declaracao = $this->prepare($consultaSql);
        return $declaracao->execute($parametros);
    }

    /**
     * Cria um registro de redefinição de senha.
     * $expiraEm deve estar no formato 'Y-m-d H:i:s'
     */
    public function criar(
        string $usuarioId,
        string $tokenHash,
        string $expiraEm,
        ?string $ip = null,
        ?string $userAgent = null
    ): bool {
        $resetId = UuidHelper::generateStandard();
        $consultaSql = "INSERT INTO password_resets (id, usuario_id, token_hash, expira_em)
                       VALUES (:id, :usuario_id, :token_hash, :expira_em)";

        return $this->executarComando($consultaSql, [
            ':id'          => $resetId,
            ':usuario_id' => $usuarioId,
            ':token_hash' => $tokenHash,
            ':expira_em'  => $expiraEm,
        ]);
    }

    /**
     * Busca um token válido (não usado e não expirado) pelo hash.
     */
    public function buscarValidoPorHash(string $tokenHash): ?array
    {
        $consultaSql = "SELECT *
        FROM password_resets
        WHERE token_hash = :hash
        AND usado_em IS NULL
        AND expira_em > NOW()
        ORDER BY criado_em DESC
        LIMIT 1";

        return $this->buscarResetUnico($consultaSql, [
            ':hash' => $tokenHash,
        ]);
    }

    /**
     * Marca o token como usado.
     */
    public function marcarComoUsado(string $id): bool
    {
        $consultaSql = "UPDATE password_resets SET usado_em = NOW() WHERE id = :id";
        return $this->executarComando($consultaSql, [':id' => $id]);
    }

    /**
     * Invalida todos os tokens ainda ativos do usuário (boa prática).
     */
    public function invalidarTokensDoUsuario(string $usuarioId): void
    {
                $consultaSql = "UPDATE password_resets
                SET usado_em = NOW()
                WHERE usuario_id = :uid
                AND usado_em IS NULL";

                $this->executarComando($consultaSql, [':uid' => $usuarioId]);
    }
}
