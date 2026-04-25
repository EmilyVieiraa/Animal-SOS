<?php
declare(strict_types=1);

final class Animal extends Model
{
    /**
     * Busca um animal reportado pelo seu ID.
     * Retorna sempre com campos normalizados (usuario_id, data_criacao).
     */
    public function buscarPorId(string $animalId): ?array
    {
        $consultaSql = "
            SELECT
                a.id,
                a.criado_por AS usuario_id,
                a.titulo,
                a.foto,
                a.descricao,
                a.especie,
                a.cor,
                a.condicao,
                a.localizacao,
                a.status,
                a.data_hora AS data_criacao,
                u.nome  AS usuario_nome,
                u.email AS usuario_email,
                u.telefone AS usuario_telefone,
                u.tipo_usuario
            FROM animais_reportados a
            INNER JOIN usuarios u ON u.id = a.criado_por
            WHERE a.id = :id
            LIMIT 1
        ";

        $declaracao = $this->prepare($consultaSql);
        $declaracao->execute([':id' => $animalId]);

        $resultado = $declaracao->fetch();
        if ($resultado) {
            $this->garantirTitulo($resultado);
        }
        return $resultado ?: null;
    }

    /**
     * Busca a ID do usuário criador de um animal reportado.
     */
    public function buscarDonoId(string $animalId): ?string
    {
        $consultaSql = "SELECT criado_por FROM animais_reportados WHERE id = :id LIMIT 1";
        $declaracao = $this->prepare($consultaSql);
        $declaracao->execute([':id' => $animalId]);

        $resultado = $declaracao->fetchColumn();
        return $resultado !== false ? (string)$resultado : null;
    }

    /**
     * Cria um novo registro de animal em risco.
     * Retorna o ID (UUID) criado ou null em caso de falha.
     */
    public function criar(array $dadosAnimal): ?string
    {
        try {
            $animalId = UuidHelper::generateStandard();

            $consultaSql = "INSERT INTO animais_reportados
                    (id, criado_por, titulo, foto, descricao, especie, cor, condicao, localizacao, status)
                    VALUES (:id, :criado_por, :titulo, :foto, :descricao, :especie, :cor, :condicao, :localizacao, 'Aguardando')";

            $declaracao = $this->prepare($consultaSql);

            $declaracao->bindValue(':id', $animalId, PDO::PARAM_STR);
            $declaracao->bindValue(':criado_por', (string)($dadosAnimal['usuario_id'] ?? ''), PDO::PARAM_STR);

            $titulo = trim((string)($dadosAnimal['titulo'] ?? ''));
            $declaracao->bindValue(':titulo', $titulo, PDO::PARAM_STR);

            $declaracao->bindValue(':especie', (string)($dadosAnimal['especie'] ?? ''), PDO::PARAM_STR);

            $foto = isset($dadosAnimal['foto']) ? trim((string)$dadosAnimal['foto']) : '';
            if ($foto === '') {
                $declaracao->bindValue(':foto', null, PDO::PARAM_NULL);
            } else {
                $declaracao->bindValue(':foto', $foto, PDO::PARAM_STR);
            }

            $descricao = isset($dadosAnimal['descricao']) ? trim((string)$dadosAnimal['descricao']) : '';
            if ($descricao === '') {
                $declaracao->bindValue(':descricao', null, PDO::PARAM_NULL);
            } else {
                $declaracao->bindValue(':descricao', $descricao, PDO::PARAM_STR);
            }

            $cor = isset($dadosAnimal['cor']) ? trim((string)$dadosAnimal['cor']) : '';
            if ($cor === '') {
                $declaracao->bindValue(':cor', null, PDO::PARAM_NULL);
            } else {
                $declaracao->bindValue(':cor', $cor, PDO::PARAM_STR);
            }

            $condicao = isset($dadosAnimal['condicao']) ? trim((string)$dadosAnimal['condicao']) : '';
            if ($condicao === '') {
                $declaracao->bindValue(':condicao', null, PDO::PARAM_NULL);
            } else {
                $declaracao->bindValue(':condicao', $condicao, PDO::PARAM_STR);
            }

            $localizacao = isset($dadosAnimal['localizacao']) ? trim((string)$dadosAnimal['localizacao']) : '';
            if ($localizacao === '') {
                $declaracao->bindValue(':localizacao', null, PDO::PARAM_NULL);
            } else {
                $declaracao->bindValue(':localizacao', $localizacao, PDO::PARAM_STR);
            }

            $executouSucesso = $declaracao->execute();
            return $executouSucesso ? $animalId : null;

        } catch (\Throwable $excecao) {
            error_log("Erro ao criar animal: " . $excecao->getMessage());
            return null;
        }
    }

    /**
     * Salva múltiplas imagens de um animal reportado.
     */
    public function adicionarImagens(string $animalId, array $caminhoImagens): void
    {
        if (empty($caminhoImagens)) {
            return;
        }

        $consultaSql = "INSERT INTO animais_reportados_imagens (id, animal_id, caminho, ordem)
                        VALUES (:id, :animal_id, :caminho, :ordem)";
        $declaracao = $this->prepare($consultaSql);

        $ordem = 1;
        foreach ($caminhoImagens as $caminho) {
            $imagemId = UuidHelper::generateStandard();

            $declaracao->execute([
                ':id'        => $imagemId,
                ':animal_id' => $animalId,
                ':caminho'   => (string)$caminho,
                ':ordem'     => $ordem++,
            ]);
        }
    }

    /**
     * Lista as imagens de um animal reportado, ordenadas por exibição.
     */
    public function listarImagens(string $animalId): array
    {
        $consultaSql = "SELECT caminho, ordem
                FROM animais_reportados_imagens
                WHERE animal_id = :id
                ORDER BY ordem ASC";
        $declaracao = $this->prepare($consultaSql);
        $declaracao->execute([':id' => $animalId]);

        return $declaracao->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Conta o total de animais reportados, opcionalmente filtrando por status.
     */
    public function contarAnimais(string $filtroStatus = ''): int
    {
        $consultaSql = "SELECT COUNT(*) AS total FROM animais_reportados";
        $parametros = [];

        if ($filtroStatus !== '') {
            $consultaSql .= " WHERE status = :status";
            $parametros[':status'] = $filtroStatus;
        }

        $declaracao = $this->prepare($consultaSql);
        $declaracao->execute($parametros);
        return (int)$declaracao->fetchColumn();
    }

    /**
     * Lista animais reportados com paginação, filtro opcional por status.
     * Retorna sempre com campos normalizados (usuario_id, data_criacao).
     */
    public function listarComPaginacao(string $filtroStatus, int $limite, int $deslocamento): array
    {
        $consultaSql = "
        SELECT
            ar.id,
            ar.criado_por AS usuario_id,
            ar.titulo,
            ar.foto,
            ar.descricao,
            ar.especie,
            ar.cor,
            ar.condicao,
            ar.localizacao,
            ar.status,
            ar.data_hora AS data_criacao,
            u.nome AS usuario_nome
        FROM animais_reportados ar
        LEFT JOIN usuarios u ON u.id = ar.criado_por
        ";

        $parametros = [];

        if ($filtroStatus !== '') {
            $consultaSql .= " WHERE ar.status = :status";
            $parametros[':status'] = $filtroStatus;
        }

        $consultaSql .= " ORDER BY ar.data_hora DESC LIMIT :limit OFFSET :offset";

        $declaracao = $this->prepare($consultaSql);
        if ($filtroStatus !== '') {
            $declaracao->bindValue(':status', $filtroStatus, PDO::PARAM_STR);
        }
        $declaracao->bindValue(':limit', $limite, PDO::PARAM_INT);
        $declaracao->bindValue(':offset', $deslocamento, PDO::PARAM_INT);

        $declaracao->execute();
        $resultados = $declaracao->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Garante que cada animal tenha um título válido
        foreach ($resultados as &$linha) {
            $this->garantirTitulo($linha);
        }

        return $resultados;
    }

    /**
     * Exclui um animal reportado e suas imagens associadas em transação.
     */
    public function excluir(string $animalId): bool
    {
        try {
            $this->beginTransaction();

            // 1) Busca caminhos das imagens
            $declaracao = $this->prepare("SELECT caminho FROM animais_reportados_imagens WHERE animal_id = :id");
            $declaracao->execute([':id' => $animalId]);
            $caminhos = $declaracao->fetchAll(PDO::FETCH_COLUMN);

            // 2) Remove registros das imagens do banco
            $declaracao = $this->prepare("DELETE FROM animais_reportados_imagens WHERE animal_id = :id");
            $declaracao->execute([':id' => $animalId]);

            // 3) Remove o animal reportado
            $declaracao = $this->prepare("DELETE FROM animais_reportados WHERE id = :id");
            $declaracao->execute([':id' => $animalId]);

            $this->commit();

            // 4) Remove arquivos do disco (fora da transação)
            foreach ($caminhos as $caminhoParcial) {
                $caminhoParcial = (string)$caminhoParcial;
                if ($caminhoParcial === '') {
                    continue;
                }

                $caminhoAbsoluto = rtrim(PUBLIC_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR 
                    . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminhoParcial);
                if (is_file($caminhoAbsoluto)) {
                    @unlink($caminhoAbsoluto);
                }
            }

            return true;
        } catch (\Throwable $excecao) {
            $this->rollBack();
            error_log("Erro ao excluir animal: " . $excecao->getMessage());
            return false;
        }
    }

    /**
     * Atualiza o status de um animal reportado e registra no histórico (transação).
     * Esta é a ÚNICA fonte de verdade para atualização de status com histórico.
     */
    public function atualizarStatusComHistorico(
        string $animalId,
        string $statusAnterior,
        string $novoStatus,
        string $usuarioId
    ): bool
    {
        try {
            $this->beginTransaction();

            // 1) Atualiza status da denúncia
            $consultaSql = "UPDATE animais_reportados
                    SET status = :novo_status
                    WHERE id = :animal_id";
            $declaracao = $this->prepare($consultaSql);
            $declaracao->execute([
                ':novo_status' => $novoStatus,
                ':animal_id'   => $animalId,
            ]);

            // 2) Registra histórico
            $historicoId = UuidHelper::generateStandard();
            $consultaSql = "INSERT INTO historico_status
                    (id, animal_id, status_anterior, novo_status, atualizado_por)
                    VALUES
                    (:id, :animal_id, :status_anterior, :novo_status, :usuario_id)";
            $declaracao = $this->prepare($consultaSql);
            $declaracao->execute([
                ':id'              => $historicoId,
                ':animal_id'       => $animalId,
                ':status_anterior' => $statusAnterior,
                ':novo_status'     => $novoStatus,
                ':usuario_id'      => $usuarioId,
            ]);

            $this->commit();
            return true;
        } catch (\Throwable $excecao) {
            $this->rollBack();
            error_log("Erro ao atualizar status: " . $excecao->getMessage());
            return false;
        }
    }

    /**
    * Garante que o título de um animal nunca será vazio.
     * Se vazio, usa a espécie como fallback.
     * NOTA: Modifica o array por referência.
     */
    private function garantirTitulo(array &$linha): void
    {
        if (empty($linha['titulo']) || trim($linha['titulo']) === '') {
            $linha['titulo'] = $linha['especie'] ?? 'Animal sem título';
        }
    }
}

