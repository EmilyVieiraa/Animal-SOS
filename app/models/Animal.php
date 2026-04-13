<?php
declare(strict_types=1);

final class Animal extends Model
{
    /**
     * Busca um animal reportado pelo seu ID.
     */
    public function buscarPorId(string $denunciaId): ?array
    {
        $consultaSql = "
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

        $declaracao = $this->prepare($consultaSql);
        $declaracao->execute([':id' => $denunciaId]);

        $resultado = $declaracao->fetch();
            if ($resultado) {
                $this->garantirTitulo($resultado);
            }
        return $resultado ?: null;
    }

    /**
     * Busca a ID do usuário criador/proprietário de uma denúncia.
     */
    public function buscarDonoId(string $denunciaId): ?string
    {
        $consultaSql = "SELECT criado_por FROM animais_reportados WHERE id = :id LIMIT 1";
        $declaracao = $this->prepare($consultaSql);
        $declaracao->execute([':id' => $denunciaId]);

        $resultado = $declaracao->fetchColumn();
        return $resultado !== false ? (string)$resultado : null;
    }

    /**
     * Cria uma nova denúncia de animal reportado.
     * Retorna o ID (UUID) criado ou null em caso de falha.
     */
    public function criar(array $dadosDenuncia): ?string
    {
        try {
            $denunciaId = UuidHelper::generateStandard();

            $consultaSql = "INSERT INTO animais_reportados
                    (id, criado_por, titulo, foto, descricao, especie, cor, condicao, localizacao, status)
                    VALUES (:id, :criado_por, :titulo, :foto, :descricao, :especie, :cor, :condicao, :localizacao, 'Aguardando')";

            $declaracao = $this->prepare($consultaSql);

            $declaracao->bindValue(':id', $denunciaId, PDO::PARAM_STR);
            $declaracao->bindValue(':criado_por', (string)($dadosDenuncia['usuario_id'] ?? ''), PDO::PARAM_STR);

            $titulo = trim((string)($dadosDenuncia['titulo'] ?? ''));
            $declaracao->bindValue(':titulo', $titulo, PDO::PARAM_STR);

            $declaracao->bindValue(':especie', (string)($dadosDenuncia['especie'] ?? ''), PDO::PARAM_STR);

            $foto = isset($dadosDenuncia['foto']) ? trim((string)$dadosDenuncia['foto']) : '';
            if ($foto === '') {
                $declaracao->bindValue(':foto', null, PDO::PARAM_NULL);
            } else {
                $declaracao->bindValue(':foto', $foto, PDO::PARAM_STR);
            }

            $descricao = isset($dadosDenuncia['descricao']) ? trim((string)$dadosDenuncia['descricao']) : '';
            if ($descricao === '') {
                $declaracao->bindValue(':descricao', null, PDO::PARAM_NULL);
            } else {
                $declaracao->bindValue(':descricao', $descricao, PDO::PARAM_STR);
            }

            $cor = isset($dadosDenuncia['cor']) ? trim((string)$dadosDenuncia['cor']) : '';
            if ($cor === '') {
                $declaracao->bindValue(':cor', null, PDO::PARAM_NULL);
            } else {
                $declaracao->bindValue(':cor', $cor, PDO::PARAM_STR);
            }

            $condicao = isset($dadosDenuncia['condicao']) ? trim((string)$dadosDenuncia['condicao']) : '';
            if ($condicao === '') {
                $declaracao->bindValue(':condicao', null, PDO::PARAM_NULL);
            } else {
                $declaracao->bindValue(':condicao', $condicao, PDO::PARAM_STR);
            }

            $localizacao = isset($dadosDenuncia['localizacao']) ? trim((string)$dadosDenuncia['localizacao']) : '';
            if ($localizacao === '') {
                $declaracao->bindValue(':localizacao', null, PDO::PARAM_NULL);
            } else {
                $declaracao->bindValue(':localizacao', $localizacao, PDO::PARAM_STR);
            }

            $executouSucesso = $declaracao->execute();
            return $executouSucesso ? $denunciaId : null;

        } catch (\Throwable $excecao) {
            return null;
        }
    }

    /**
     * Salva múltiplas imagens associadas a uma denúncia.
     */
    public function adicionarImagens(string $denunciaId, array $caminhoImagens): void
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
                ':animal_id' => $denunciaId,
                ':caminho'   => (string)$caminho,
                ':ordem'     => $ordem++,
            ]);
        }
    }

    /**
     * Lista as imagens associadas a uma denúncia, ordenadas por ordem de exibição.
     */
    public function listarImagens(string $denunciaId): array
    {
        $consultaSql = "SELECT caminho, ordem
                FROM animais_reportados_imagens
                WHERE animal_id = :id
                ORDER BY ordem ASC, criado_em ASC";
        $declaracao = $this->prepare($consultaSql);
        $declaracao->execute([':id' => $denunciaId]);

        return $declaracao->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Conta o total de denúncias, opcionalmente filtrando por status.
     */
    public function contarDenuncias(string $filtroStatus = ''): int
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
     * Lista denúncias com paginação e filtro opcional por status.
     */
    public function listarComPaginacao(string $filtroStatus, int $limite, int $deslocamento): array
    {
        $consultaSql = "
        SELECT
            ar.*,
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

        // Garante que cada denúncia tenha um título válido
        foreach ($resultados as &$linha) {
              $this->garantirTitulo($linha);
        }

        return $resultados;
    }
    /**
     * Exclui uma denúncia e suas imagens associadas em transação.
     */
    public function excluir(string $denunciaId): bool
    {
        try {
                $this->beginTransaction();

            // 1) Busca caminhos das imagens
            $declaracao = $this->prepare("SELECT caminho FROM animais_reportados_imagens WHERE animal_id = :id");
            $declaracao->execute([':id' => $denunciaId]);
            $caminhos = $declaracao->fetchAll(PDO::FETCH_COLUMN);

            // 2) Remove registros das imagens do banco
            $declaracao = $this->prepare("DELETE FROM animais_reportados_imagens WHERE animal_id = :id");
            $declaracao->execute([':id' => $denunciaId]);

            // 3) Remove a denúncia
            $declaracao = $this->prepare("DELETE FROM animais_reportados WHERE id = :id");
            $declaracao->execute([':id' => $denunciaId]);

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
            return false;
        }
    }

    /**
     * Atualiza o status de uma denúncia e registra no histórico (transação).
     */
    public function atualizarStatusComHistorico(
        string $denunciaId,
        string $statusAnterior,
        string $novoStatus,
        string $usuarioId
    ): bool
    {
        try {
            $this->beginTransaction();

            $consultaSql = "UPDATE animais_reportados
                    SET status = :novo_status
                    WHERE id = :animal_id";
            $declaracao = $this->prepare($consultaSql);
            $declaracao->execute([
                ':novo_status' => $novoStatus,
                ':animal_id'   => $denunciaId,
            ]);

            $historicoId = UuidHelper::generateStandard();
            $consultaSql = "INSERT INTO historico_status
                    (id, animal_id, status_anterior, novo_status, atualizado_por)
                    VALUES
                    (:id, :animal_id, :status_anterior, :novo_status, :usuario_id)";
            $declaracao = $this->prepare($consultaSql);
            $declaracao->execute([
                ':id'              => $historicoId,
                ':animal_id'       => $denunciaId,
                ':status_anterior' => $statusAnterior,
                ':novo_status'     => $novoStatus,
                ':usuario_id'      => $usuarioId,
            ]);

            $this->commit();
            return true;
        } catch (\Throwable $excecao) {
            $this->rollBack();
            error_log("ERRO ao atualizar status: " . $excecao->getMessage());
            return false;
        }
    }

    /**
     * Conta quantas denúncias um usuário criou.
     */
    public function contarPorAutor(string $usuarioId): int
    {
        $consultaSql = "SELECT COUNT(*) AS total
                FROM animais_reportados
                WHERE criado_por = :uid";
        $declaracao = $this->prepare($consultaSql);
        $declaracao->execute([':uid' => $usuarioId]);
        $resultado = $declaracao->fetch();
        return (int)($resultado['total'] ?? 0);
    }

    /**
     * Lista denúncias criadas por um usuário específico.
     */
    public function listarPorAutor(string $usuarioId): array
    {
        $consultaSql = "SELECT id, titulo, foto, descricao, especie, cor, condicao, status, data_hora, localizacao
                FROM animais_reportados
                WHERE criado_por = :uid
                ORDER BY data_hora DESC";
        $declaracao = $this->prepare($consultaSql);
        $declaracao->execute([':uid' => $usuarioId]);
        $resultados = $declaracao->fetchAll() ?: [];

        // Garante que cada denúncia tenha um título válido
        foreach ($resultados as &$linha) {
                $this->garantirTitulo($linha);
        }

        return $resultados;
    }

    /**
     * Garante que uma linha de resultado tenha um título válido.
     * Usa a espécie como fallback quando o título estiver vazio.
     */
    private function garantirTitulo(array &$linha): void
    {
        if (empty(trim((string)($linha['titulo'] ?? '')))) {
            $linha['titulo'] = (string)($linha['especie'] ?? 'Animal');
        }
    }

}
