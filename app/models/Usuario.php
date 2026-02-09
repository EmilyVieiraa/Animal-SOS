<?php
declare(strict_types=1);

final class Usuario extends Model
{
    /**
     * Busca usuário por e-mail (para login e recuperação de senha).
     */
    public function encontrarPorEmail(string $email): ?array
    {
        $sql = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
        $stmt = $this->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }

    /**
     * Busca usuário por ID (perfil, alteração de senha logado, etc).
     */
    public function encontrarPorId(string $id): ?array
    {
        $sql = "SELECT * FROM usuarios WHERE id = :id LIMIT 1";
        $stmt = $this->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }

    /**
     * Cria usuário (cadastro).
     * Ajuste os campos conforme o seu controller/form.
     */
    public function criar(array $dados): bool
    {
        $sql = "INSERT INTO usuarios (
            nome, email, senha_hash, telefone, tipo_usuario,
            foto_perfil, cep, bairro, rua, numero, cidade, estado, ativo
        )
        VALUES (
            :nome, :email, :senha_hash, :telefone, :tipo_usuario,
            :foto_perfil, :cep, :bairro, :rua, :numero, :cidade, :estado, TRUE
        )";

        $stmt = $this->prepare($sql);

        return $stmt->execute([
            ':nome'         => $dados['nome'],
            ':email'        => $dados['email'],
            ':senha_hash'   => password_hash((string)$dados['senha'], PASSWORD_DEFAULT),
            ':telefone'     => $dados['telefone'] !== '' ? $dados['telefone'] : null,
            ':tipo_usuario' => $dados['tipo_usuario'] ?? 'Comum',
            ':foto_perfil' => ($dados['foto_perfil'] ?? '') !== '' ? $dados['foto_perfil'] : null,

            ':cep'    => ($dados['cep'] ?? '') !== '' ? $dados['cep'] : null,
            ':bairro' => ($dados['bairro'] ?? '') !== '' ? $dados['bairro'] : null,
            ':rua'    => ($dados['rua'] ?? '') !== '' ? $dados['rua'] : null,
            ':numero' => ($dados['numero'] ?? '') !== '' ? $dados['numero'] : null,
            ':cidade' => ($dados['cidade'] ?? '') !== '' ? $dados['cidade'] : null,
            ':estado' => ($dados['estado'] ?? '') !== '' ? $dados['estado'] : null,

        ]);

    }

    /**
     * Atualiza dados de perfil (ex.: nome/telefone).
     */
    public function atualizarPerfil(string $id, array $dados): bool
    {
        $sql = "UPDATE usuarios
                   SET nome = :nome,
                       telefone = :telefone
                 WHERE id = :id";

        $stmt = $this->prepare($sql);

        return $stmt->execute([
            ':nome'     => $dados['nome'],
            ':telefone' => $dados['telefone'] ?? null,
            ':id'       => $id,
        ]);
    }

    /**
     * ALTERAR SENHA COM SENHA ATUAL (usuário logado).
     * (Seu método, mantido)
     */
    public function alterarSenha(string $id, string $senhaAtual, string $novaSenha): bool
    {
        $usuario = $this->encontrarPorId($id);

        if (!$usuario || !isset($usuario['senha_hash'])) {
            return false;
        }

        if (!password_verify($senhaAtual, (string)$usuario['senha_hash'])) {
            return false;
        }

        $sql = "UPDATE usuarios SET senha_hash = :senha WHERE id = :id";
        $stmt = $this->prepare($sql);

        return $stmt->execute([
            ':senha' => password_hash($novaSenha, PASSWORD_DEFAULT),
            ':id'    => $id,
        ]);
    }

    /**
     * Atualiza o HASH da senha SEM exigir a senha atual.
     * Uso: recuperação de senha (token).
     */
    public function atualizarSenhaHash(string $usuarioId, string $senhaHash): bool
    {
        $sql = "UPDATE usuarios SET senha_hash = :hash WHERE id = :id";
        $stmt = $this->prepare($sql);

        return $stmt->execute([
            ':hash' => $senhaHash,
            ':id'   => $usuarioId,
        ]);
    }

    /**
     * Atalho: redefine senha SEM senha atual, gerando o hash internamente.
     * Uso: recuperação de senha (token).
     */
    public function redefinirSenhaSemSenhaAtual(string $usuarioId, string $novaSenha): bool
    {
        return $this->atualizarSenhaHash(
            $usuarioId,
            password_hash($novaSenha, PASSWORD_DEFAULT)
        );
    }
}
