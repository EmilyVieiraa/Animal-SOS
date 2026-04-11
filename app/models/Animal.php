<?php
declare(strict_types=1);

final class Animal extends Model
{
    private ?string $autorColunaView = null;

    /**
     * Lista todas as denúncias de animais reportados.
     * Se $status for fornecido, filtra por status.
     */
    public function listarTodos(?string $status = null): array
    {
        $sql = "SELECT * FROM vw_denuncias_completas";
        $params = [];

        if ($status) {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY data_hora DESC";

        $stmt = $this->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Busca um animal reportado pelo ID.
     * Retorna um array associativo ou null se não encontrado.
     */
    public function buscarDonoId(string $animalId): ?string
    {
        $sql = "SELECT criado_por FROM animais_reportados WHERE id = :id LIMIT 1";
        $stmt = $this->prepare($sql);
        $stmt->execute([':id' => $animalId]);

        $donoId = $stmt->fetchColumn();
        return $donoId !== false ? (string)$donoId : null;
    }

    /**
     * Cria uma denúncia em animais_reportados.
     * Retorna o ID (UUID) criado ou null em caso de falha.
     * UUID gerado via UuidHelper::generateStandard() - garantia de unicidade e segurança.
     */
    public function criar(array $dados): ?string
    {
        try {
            $id = UuidHelper::generateStandard(); // Gera UUID v4 via Symfony/uid (36 chars com hífens)

            $sql = "INSERT INTO animais_reportados
                    (id, criado_por, titulo, foto, descricao, especie, cor, condicao, localizacao, status)
                    VALUES
                    (:id, :criado_por, :titulo, :foto, :descricao, :especie, :cor, :condicao, :localizacao, 'Aguardando')";

            $stmt = $this->prepare($sql);

            $stmt->bindValue(':id', $id, PDO::PARAM_STR);
            $stmt->bindValue(':criado_por', (string)($dados['usuario_id'] ?? ''), PDO::PARAM_STR);

            // IMPORTANTE: título deve ser string (evita NULL e campo vazio por falha de bind)
            $titulo = trim((string)($dados['titulo'] ?? ''));
            $stmt->bindValue(':titulo', $titulo, PDO::PARAM_STR);

            $stmt->bindValue(':especie', (string)($dados['especie'] ?? ''), PDO::PARAM_STR);

            $foto = isset($dados['foto']) ? trim((string)$dados['foto']) : '';
            if ($foto === '') {
                $stmt->bindValue(':foto', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':foto', $foto, PDO::PARAM_STR);
            }

            $descricao = isset($dados['descricao']) ? trim((string)$dados['descricao']) : '';
            if ($descricao === '') {
                $stmt->bindValue(':descricao', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':descricao', $descricao, PDO::PARAM_STR);
            }

            $cor = isset($dados['cor']) ? trim((string)$dados['cor']) : '';
            if ($cor === '') {
                $stmt->bindValue(':cor', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':cor', $cor, PDO::PARAM_STR);
            }

            $condicao = isset($dados['condicao']) ? trim((string)$dados['condicao']) : '';
            if ($condicao === '') {
                $stmt->bindValue(':condicao', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':condicao', $condicao, PDO::PARAM_STR);
            }

            $loc = isset($dados['localizacao']) ? trim((string)$dados['localizacao']) : '';
            if ($loc === '') {
                $stmt->bindValue(':localizacao', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':localizacao', $loc, PDO::PARAM_STR);
            }

            $ok = $stmt->execute();
            return $ok ? $id : null;

        } catch (\Throwable $e) {
            // TEMPORÁRIO para debug, se quiser ver o erro real:
            // throw $e;
            return null;
        }
    }

    /**
     * Salva múltiplas imagens relacionadas a um animal reportado.
     */
    public function adicionarImagens(string $animalId, array $paths): void{
        if (empty($paths)) return;

        $sql = "INSERT INTO animais_reportados_imagens (id, animal_id, caminho, ordem)
                VALUES (:id, :animal_id, :caminho, :ordem)";
        $stmt = $this->prepare($sql);

        $ordem = 1;
        foreach ($paths as $p) {
            $imgId = UuidHelper::generateStandard(); // Gera UUID v4 via Symfony/uid (36 chars com hífens)

            $stmt->execute([
                ':id'        => $imgId,
                ':animal_id' => $animalId,
                ':caminho'   => (string)$p,
                ':ordem'     => $ordem++,
            ]);
        }
    }

    /**
     * Lista as imagens associadas a um animal reportado.
     */
    public function listarImagens(string $animalId): array
    {
        $sql = "SELECT caminho, ordem
                FROM animais_reportados_imagens
                WHERE animal_id = :id
                ORDER BY ordem ASC, criado_em ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $animalId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Conta o total de denúncias, opcionalmente filtrando por status.
     */
    public function countDenuncias(string $status = ''): int
    {
        $sql = "SELECT COUNT(*) AS total FROM animais_reportados";
        $params = [];

        if ($status !== '') {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }

        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Lista denúncias com paginação e filtro por status.
     */
    public function listarPaginado(string $status, int $limit, int $offset): array
    {
        $sql = "
        SELECT
            ar.*,
            u.nome AS usuario_nome
        FROM animais_reportados ar
        LEFT JOIN usuarios u ON u.id = ar.criado_por
        ";

        $params = [];

        if ($status !== '') {
            $sql .= " WHERE ar.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY ar.data_hora DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        // bindValue com tipos corretos para LIMIT/OFFSET
        if ($status !== '') {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Garantir que cada denúncia tenha um título
        foreach ($results as &$row) {
            if (empty(trim((string)($row['titulo'] ?? '')))) {
                $row['titulo'] = (string)($row['especie'] ?? 'Animal');
            }
        }

        return $results;
    }


    public function excluir(string $animalId): bool
    {
        try {
            $this->db->beginTransaction();

            // 1) Buscar caminhos das imagens
            $stmt = $this->prepare("SELECT caminho FROM animais_reportados_imagens WHERE animal_id = :id");
            $stmt->execute([':id' => $animalId]);
            $caminhos = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // 2) Apagar registros das imagens
            $stmt = $this->db->prepare("DELETE FROM animais_reportados_imagens WHERE animal_id = :id");
            $stmt->execute([':id' => $animalId]);

            // 3) Apagar denúncia
            $stmt = $this->db->prepare("DELETE FROM animais_reportados WHERE id = :id");
            $stmt->execute([':id' => $animalId]);

            $this->db->commit();

            // 4) Apagar arquivos do disco (fora da transaction)
            // Ajuste PUBLIC_PATH se no seu projeto for diferente
            foreach ($caminhos as $relPath) {
                $relPath = (string)$relPath;
                if ($relPath === '') continue;

                $abs = rtrim(PUBLIC_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relPath);
                if (is_file($abs)) {
                    @unlink($abs);
                }
            }

            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function buscarPorId(string $id): ?array
    {
        $sql = "
            SELECT
                a.*,
                u.nome  AS usuario_nome,
                u.email AS usuario_email,
                u.telefone AS usuario_telefone,
                u.tipo_usuario
            FROM animais_reportados a
            INNER JOIN usuarios u ON u.id = a.criado_por
            WHERE a.id = :id
            LIMIT 1
        ";

        $stmt = $this->prepare($sql);
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();
        if ($row && empty(trim((string)($row['titulo'] ?? '')))) {
            $row['titulo'] = (string)($row['especie'] ?? 'Animal');
        }
        return $row ?: null;
    }

    public function atualizarStatusComHistorico(string $animalId, string $statusAnterior, string $novoStatus, string $usuarioId): bool {
        try {
            $this->beginTransaction();

            $sql = "UPDATE animais_reportados
                    SET status = :novo_status
                    WHERE id = :animal_id";
            $stmt = $this->prepare($sql);
            $stmt->execute([
                ':novo_status' => $novoStatus,
                ':animal_id'   => $animalId,
            ]);

            $historicopId = UuidHelper::generateStandard();
            $sql = "INSERT INTO historico_status
                    (id, animal_id, status_anterior, novo_status, atualizado_por)
                    VALUES
                    (:id, :animal_id, :status_anterior, :novo_status, :usuario_id)";
            $stmt = $this->prepare($sql);
            $stmt->execute([
                ':id'              => $historicopId,
                ':animal_id'       => $animalId,
                ':status_anterior' => $statusAnterior,
                ':novo_status'     => $novoStatus,
                ':usuario_id'      => $usuarioId,
            ]);

            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            error_log("ERROR in atualizarStatusComHistorico: " . $e->getMessage());
            return false;
        }
    }

    public function contarPorAutor(string $usuarioId): int
    {
        $autorColuna = $this->obterColunaAutorView();
        $sql = "SELECT COUNT(*) AS total
                FROM vw_denuncias_completas
            WHERE {$autorColuna} = :uid";
        $stmt = $this->prepare($sql);
        $stmt->execute([':uid' => $usuarioId]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function listarPorAutor(string $usuarioId): array
    {
        $autorColuna = $this->obterColunaAutorView();
        $sql = "SELECT id, titulo, foto, descricao, especie, cor, condicao, status, data_hora, localizacao,
                    total_comentarios
                FROM vw_denuncias_completas
                WHERE {$autorColuna} = :uid
                ORDER BY data_hora DESC";
        $stmt = $this->prepare($sql);
        $stmt->execute([':uid' => $usuarioId]);
        $results = $stmt->fetchAll() ?: [];

        // Garantir que cada denúncia tenha um título
        foreach ($results as &$row) {
            if (empty(trim((string)($row['titulo'] ?? '')))) {
                $row['titulo'] = (string)($row['especie'] ?? 'Animal');
            }
        }

        return $results;
    }

    private function obterColunaAutorView(): string
    {
        if ($this->autorColunaView !== null) {
            return $this->autorColunaView;
        }

        try {
            $stmt = $this->query("SHOW COLUMNS FROM vw_denuncias_completas");
            $colunas = $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];

            if (in_array('usuario_id', $colunas, true)) {
                return $this->autorColunaView = 'usuario_id';
            }

            if (in_array('criado_por', $colunas, true)) {
                return $this->autorColunaView = 'criado_por';
            }
        } catch (\Throwable $e) {
            // fallback para manter comportamento legado
        }

        return $this->autorColunaView = 'usuario_id';
    }

}
