<?php
declare(strict_types=1);

final class Comentario extends Model
{
    private const SQL_LISTAR_POR_ANIMAL = "SELECT
            c.id,
            c.animal_id,
            c.usuario_id,
            c.mensagem,
            c.data_hora,
            u.nome AS usuario_nome
        FROM comentarios c
        INNER JOIN usuarios u ON u.id = c.usuario_id
        WHERE c.animal_id = :animal_id
        ORDER BY c.data_hora DESC";

    private const SQL_CRIAR_COMENTARIO = "INSERT INTO comentarios (id, animal_id, usuario_id, mensagem, data_hora)
        VALUES (:id, :animal_id, :usuario_id, :mensagem, NOW())";

    public function listarPorAnimal(string $animalId): array
    {
        $comando = $this->prepare(self::SQL_LISTAR_POR_ANIMAL);
        $comando->execute([':animal_id' => $animalId]);

        $comentarios = $comando->fetchAll(PDO::FETCH_ASSOC);
        return is_array($comentarios) ? $comentarios : [];
    }

    public function criar(array $dados): bool
    {
        $parametros = $this->montarParametrosCriacao($dados);
        $comando = $this->prepare(self::SQL_CRIAR_COMENTARIO);
        return $comando->execute($parametros);
    }

    private function montarParametrosCriacao(array $dados): array
    {
        $animalId = $this->obterCampoTextoObrigatorio($dados, 'animal_id');
        $usuarioId = $this->obterCampoTextoObrigatorio($dados, 'usuario_id');
        $mensagem = $this->obterCampoTextoObrigatorio($dados, 'mensagem');

        return [
            ':id' => UuidHelper::generateStandard(),
            ':animal_id' => $animalId,
            ':usuario_id' => $usuarioId,
            ':mensagem' => $mensagem,
        ];
    }

    private function obterCampoTextoObrigatorio(array $dados, string $campo): string
    {
        $valor = trim((string)($dados[$campo] ?? ''));

        if ($valor === '') {
            throw new InvalidArgumentException("Campo obrigatório ausente para comentário: {$campo}");
        }

        return $valor;
    }

}
