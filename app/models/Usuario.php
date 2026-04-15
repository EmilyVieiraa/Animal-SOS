<?php
declare(strict_types=1);

final class Usuario extends Model
{
    private function buscarUsuarioUnico(string $consultaSql, string $nomeParametro, string $valor): ?array
    {
        $declaracao = $this->prepare($consultaSql);
        $declaracao->bindValue($nomeParametro, $valor);
        $declaracao->execute();

        $usuario = $declaracao->fetch();
        return $usuario ?: null;
    }

    private function textoOuNulo($valor): ?string
    {
        $texto = trim((string)$valor);
        return $texto !== '' ? $texto : null;
    }

    private function gerarHashSenha(string $senha): string
    {
        return password_hash($senha, PASSWORD_DEFAULT);
    }

    /**
     * Busca usuário por e-mail (para login e recuperação de senha).
     */
    public function encontrarPorEmail(string $email): ?array
    {
        $consultaSql = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
        return $this->buscarUsuarioUnico($consultaSql, ':email', $email);
    }

    /**
     * Busca usuário por ID (perfil, alteração de senha logado, etc).
     */
    public function encontrarPorId(string $id): ?array
    {
        $consultaSql = "SELECT * FROM usuarios WHERE id = :id LIMIT 1";
        return $this->buscarUsuarioUnico($consultaSql, ':id', $id);
    }

    /**
     * Cria usuário (cadastro).
     * Gera UUID automaticamente via UuidHelper::generateStandard()
     * Retorna true se bem-sucedido, false caso contrário.
     */
    public function criar(array $dados): bool
    {
        $usuarioId = UuidHelper::generateStandard();

        $consultaSql = "INSERT INTO usuarios (
            id, nome, email, senha_hash, telefone, tipo_usuario,
            foto_perfil, cep, bairro, rua, numero, cidade, estado, ativo
        )
        VALUES (
            :id, :nome, :email, :senha_hash, :telefone, :tipo_usuario,
            :foto_perfil, :cep, :bairro, :rua, :numero, :cidade, :estado, TRUE
        )";

        $declaracao = $this->prepare($consultaSql);

        return $declaracao->execute([
            ':id'           => $usuarioId,
            ':nome'         => $dados['nome'],
            ':email'        => $dados['email'],
            ':senha_hash'   => $this->gerarHashSenha((string)$dados['senha']),
            ':telefone'     => $this->textoOuNulo($dados['telefone'] ?? ''),
            ':tipo_usuario' => $dados['tipo_usuario'] ?? 'Comum',
            ':foto_perfil'  => $this->textoOuNulo($dados['foto_perfil'] ?? ''),

            ':cep'    => $this->textoOuNulo($dados['cep'] ?? ''),
            ':bairro' => $this->textoOuNulo($dados['bairro'] ?? ''),
            ':rua'    => $this->textoOuNulo($dados['rua'] ?? ''),
            ':numero' => $this->textoOuNulo($dados['numero'] ?? ''),
            ':cidade' => $this->textoOuNulo($dados['cidade'] ?? ''),
            ':estado' => $this->textoOuNulo($dados['estado'] ?? ''),

        ]);
    }

    /**
     * Atualiza dados de perfil (ex.: nome/telefone/endereço).
     */
    public function atualizarPerfil(string $id, array $dados): bool
    {
        $consultaSql = "UPDATE usuarios
                SET nome = :nome,
                    telefone = :telefone,
                    rua = :rua,
                    numero = :numero,
                    bairro = :bairro,
                    cidade = :cidade,
                    estado = :estado,
                    cep = :cep,
                    mostrar_email = :mostrar_email,
                    mostrar_whatsapp = :mostrar_whatsapp
                WHERE id = :id";

        $declaracao = $this->prepare($consultaSql);

        return $declaracao->execute([
            ':nome'             => $dados['nome'],
            ':telefone'         => $this->textoOuNulo($dados['telefone'] ?? null),
            ':rua'              => $this->textoOuNulo($dados['rua'] ?? null),
            ':numero'           => $this->textoOuNulo($dados['numero'] ?? null),
            ':bairro'           => $this->textoOuNulo($dados['bairro'] ?? null),
            ':cidade'           => $this->textoOuNulo($dados['cidade'] ?? null),
            ':estado'           => $this->textoOuNulo($dados['estado'] ?? null),
            ':cep'              => $this->textoOuNulo($dados['cep'] ?? null),
            ':mostrar_email'    => (int)($dados['mostrar_email'] ?? 0),
            ':mostrar_whatsapp' => (int)($dados['mostrar_whatsapp'] ?? 0),
            ':id'               => $id,
        ]);
    }

    /**
     * Atualiza o HASH da senha SEM exigir a senha atual.
     * Uso: recuperação de senha (token).
     */
    private function atualizarSenhaHash(string $usuarioId, string $senhaHash): bool
    {
        $consultaSql = "UPDATE usuarios SET senha_hash = :hash WHERE id = :id";
        $declaracao = $this->prepare($consultaSql);

        return $declaracao->execute([
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
            $this->gerarHashSenha($novaSenha)
        );
    }
}
