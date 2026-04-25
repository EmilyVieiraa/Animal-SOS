<?php
declare(strict_types=1);

final class HistoricoStatus extends Model
{
    /**
     * Tabela historico_status: animal_id, status_anterior, novo_status, atualizado_por, data_hora :contentReference[oaicite:5]{index=5}
     * A escrita do histórico é centralizada em Animal::atualizarStatusComHistorico().
     */
    public function listarPorAnimal(string $animalId): array
    {
        $sql = "SELECT
                hs.status_anterior,
                hs.novo_status,
                hs.data_hora,
                COALESCE(u.nome, 'Usuário não encontrado') AS atualizado_por_nome
                FROM historico_status hs
                LEFT JOIN usuarios u ON u.id = hs.atualizado_por
                WHERE hs.animal_id = :animal_id
                ORDER BY hs.data_hora DESC";

        $stmt = $this->prepare($sql);
        $stmt->execute([':animal_id' => $animalId]);

        return $stmt->fetchAll();
    }
}
