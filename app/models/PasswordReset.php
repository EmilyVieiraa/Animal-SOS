<?php
declare(strict_types=1);

final class PasswordReset extends Model
{
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
        $sql = "INSERT INTO password_resets (usuario_id, token_hash, expira_em)
                VALUES (:usuario_id, :token_hash, :expira_em)";

        $stmt = $this->prepare($sql);

        return $stmt->execute([
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
        $sql = "SELECT *
                  FROM password_resets
                 WHERE token_hash = :hash
                   AND usado_em IS NULL
                   AND expira_em > NOW()
              ORDER BY criado_em DESC
                 LIMIT 1";

        $stmt = $this->prepare($sql);
        $stmt->bindValue(':hash', $tokenHash);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Marca o token como usado.
     */
    public function marcarComoUsado(string $id): bool
    {
        $sql = "UPDATE password_resets SET usado_em = NOW() WHERE id = :id";
        $stmt = $this->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Invalida todos os tokens ainda ativos do usuário (boa prática).
     */
    public function invalidarTokensDoUsuario(string $usuarioId): void
    {
        $sql = "UPDATE password_resets
                   SET usado_em = NOW()
                 WHERE usuario_id = :uid
                   AND usado_em IS NULL";

        $stmt = $this->prepare($sql);
        $stmt->execute([':uid' => $usuarioId]);
    }
}
