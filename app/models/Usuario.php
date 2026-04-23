<?php
declare(strict_types=1);

final class Usuario extends Model
{
    private const TIPOS_USUARIO_PERMITIDOS = ['Comum', 'ONG', 'Autoridade'];

    private function buscarUsuarioUnicoPorParametro(string $consultaSql, string $nomeParametro, string $valorParametro): ?array
    {
        $declaracao = $this->prepare($consultaSql);
        $declaracao->bindValue($nomeParametro, $valorParametro);
        $declaracao->execute();

        $registroUsuario = $declaracao->fetch();
        return $registroUsuario ?: null;
    }

    private function textoOuNulo(mixed $valor): ?string
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
        return $this->buscarUsuarioUnicoPorParametro($consultaSql, ':email', $email);
    }

    /**
     * Busca usuário por ID (perfil, alteração de senha logado, etc).
     */
    public function encontrarPorId(string $id): ?array
    {
        $consultaSql = "SELECT * FROM usuarios WHERE id = :id LIMIT 1";
        return $this->buscarUsuarioUnicoPorParametro($consultaSql, ':id', $id);
    }

    /**
     * Cria usuário (cadastro).
     * Gera UUID automaticamente via UuidHelper::generateStandard()
     * Retorna true se bem-sucedido, false caso contrário.
     */
    public function criar(array $dados): bool
    {
        $usuarioId = UuidHelper::generateStandard();
        $dadosCadastro = $this->normalizarDadosCadastro($dados, $usuarioId);

        $consultaSql = "INSERT INTO usuarios (
            id, nome, email, senha_hash, telefone, tipo_usuario,
            foto_perfil, cep, bairro, rua, numero, cidade, estado, ativo
        )
        VALUES (
            :id, :nome, :email, :senha_hash, :telefone, :tipo_usuario,
            :foto_perfil, :cep, :bairro, :rua, :numero, :cidade, :estado, TRUE
        )";

        $declaracao = $this->prepare($consultaSql);

        return $declaracao->execute($dadosCadastro);
    }

    private function normalizarDadosCadastro(array $dados, string $usuarioId): array
    {
        $tipoUsuario = (string)($dados['tipo_usuario'] ?? 'Comum');
        if (!in_array($tipoUsuario, self::TIPOS_USUARIO_PERMITIDOS, true)) {
            $tipoUsuario = 'Comum';
        }

        return [
            ':id'           => $usuarioId,
            ':nome'         => $dados['nome'],
            ':email'        => $dados['email'],
            ':senha_hash'   => $this->gerarHashSenha((string)$dados['senha']),
            ':telefone'     => $this->textoOuNulo($dados['telefone'] ?? ''),
            ':tipo_usuario' => $tipoUsuario,
            ':foto_perfil'  => $this->textoOuNulo($dados['foto_perfil'] ?? ''),
            ':cep'          => $this->textoOuNulo($dados['cep'] ?? ''),
            ':bairro'       => $this->textoOuNulo($dados['bairro'] ?? ''),
            ':rua'          => $this->textoOuNulo($dados['rua'] ?? ''),
            ':numero'       => $this->textoOuNulo($dados['numero'] ?? ''),
            ':cidade'       => $this->textoOuNulo($dados['cidade'] ?? ''),
            ':estado'       => $this->textoOuNulo($dados['estado'] ?? ''),
        ];
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
        $novoHashSenha = $this->gerarHashSenha($novaSenha);
        return $this->atualizarSenhaHash($usuarioId, $novoHashSenha);
    }
}
