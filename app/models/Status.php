<?php
declare(strict_types=1);

final class Status extends Model
{
    /**
     * Usa procedure sp_atualizar_status_animal(p_animal_id, p_novo_status, p_usuario_id) :contentReference[oaicite:6]{index=6}
     */
    public function atualizarStatus(string $animalId, string $novoStatus, string $usuarioId): bool
    {
        $sql = "CALL sp_atualizar_status_animal(:animal_id, :novo_status, :usuario_id)";
        $stmt = $this->prepare($sql);

        return $stmt->execute([
            ':animal_id'  => $animalId,
            ':novo_status'=> $novoStatus,
            ':usuario_id' => $usuarioId,
        ]);
    }
}
