<?php
declare(strict_types=1);

final class HistoricoStatus extends Model
{
    /**
     * Tabela historico_status: animal_id, status_anterior, novo_status, atualizado_por, data_hora :contentReference[oaicite:5]{index=5}
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


    public function atualizarStatus(
        int $denunciaId,
        string $novoStatus,
        int $usuarioId
    ): void {
        $this->beginTransaction();

        $statusAtual = $this->buscarStatusAtual($denunciaId);

        $sql = "UPDATE animais_reportados
                SET status = :status
                WHERE id = :id";
        $stmt = $this->prepare($sql);
        $stmt->execute([
            ':status' => $novoStatus,
            ':id' => $denunciaId
        ]);

        $sql = "INSERT INTO denuncias_status_historico
                (denuncia_id, status_anterior, status_novo, alterado_por)
                VALUES (:denuncia, :anterior, :novo, :usuario)";
        $stmt = $this->prepare($sql);
        $stmt->execute([
            ':denuncia' => $denunciaId,
            ':anterior' => $statusAtual,
            ':novo' => $novoStatus,
            ':usuario' => $usuarioId
        ]);

        $this->commit();
    }

}
