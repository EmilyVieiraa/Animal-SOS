<?php
declare(strict_types=1);

final class Comentario extends Model
{
    /**
     * Lista comentários por animal_id.
     * Tabela: comentarios(animal_id, usuario_id, mensagem, data_hora) :contentReference[oaicite:4]{index=4}
     */
    public function listarPorAnimal(string $animalId): array
    {
        $sql = "SELECT
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

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':animal_id' => $animalId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Cria comentário.
     */
    public function criar(array $dados): bool
    {
        $sql = "INSERT INTO comentarios (id, animal_id, usuario_id, mensagem, data_hora)
                VALUES (:id, :animal_id, :usuario_id, :mensagem, NOW())";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id'        => bin2hex(random_bytes(16)),
            ':animal_id' => (string)$dados['animal_id'],
            ':usuario_id'=> (string)$dados['usuario_id'],
            ':mensagem'  => (string)$dados['mensagem'],
        ]);
    }

}
