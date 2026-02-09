<?php
declare(strict_types=1);

final class Estatisticas extends Model
{
    public function gerais(): array
    {
        $sql = "SELECT * FROM vw_estatisticas_gerais";
        $stmt = $this->query($sql);

        $row = $stmt->fetch();
        return $row ?: [];
    }
}
