<?php
declare(strict_types=1);

final class AnimalController extends Controller
{
    private HistoricoStatus $historicoStatusModel;
    private Animal $animalModel;
    private Comentario $comentarioModel;

    /**
     * Regras de transição de status por tipo de usuário.
     */
    private const StatusPermitidosPorTipo = [
        'Comum'      => [],
        'ONG'        => ['Em andamento', 'Adoção', 'Resgatado', 'Finalizado'],
        'Autoridade' => ['Aguardando', 'Em andamento', 'Adoção', 'Resgatado', 'Finalizado'],
        'Admin'      => ['Aguardando', 'Em andamento', 'Adoção', 'Resgatado', 'Finalizado'],
    ];

    public function __construct()
    {
        $this->animalModel = new Animal();
        $this->comentarioModel = new Comentario();
        $this->historicoStatusModel = new HistoricoStatus();
    }

    /**
     * Lista denúncias de animais com paginação e filtro por status.
     */
    public function listar(): void
    {
        $filtroStatus = $_GET['status'] ?? '';
        $filtroStatus = is_string($filtroStatus) ? trim($filtroStatus) : '';

        $paginaAtual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        if ($paginaAtual < 1) {
            $paginaAtual = 1;
        }

        $itensParPagina = 9;
        $deslocamento = ($paginaAtual - 1) * $itensParPagina;

        $totalDenuncias = $this->animalModel->contarDenuncias($filtroStatus);
        $totalPaginas = (int)ceil($totalDenuncias / $itensParPagina) ?: 1;

        if ($paginaAtual > $totalPaginas) {
            $paginaAtual = $totalPaginas;
            $deslocamento = ($paginaAtual - 1) * $itensParPagina;
        }

        $denunciasListadas = $this->animalModel->listarComPaginacao($filtroStatus, $itensParPagina, $deslocamento);

        $this->view('animais/listar', [
            'denuncias'      => $denunciasListadas,
            'status'         => $filtroStatus,
            'page'           => $paginaAtual,
            'perPage'        => $itensParPagina,
            'total'          => $totalDenuncias,
            'totalPages'     => $totalPaginas,
        ]);
    }

    /**
     * Exibe detalhes de uma denúncia específica.
     */
    public function detalhes(): void
    {
        $denunciaId = (string)($_GET['id'] ?? '');
        if ($denunciaId === '') {
            $_SESSION['flash_error'] = 'Denúncia inválida ou não informada.';
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        $informacaoDenuncia = $this->animalModel->buscarPorId($denunciaId);
        if (!$informacaoDenuncia) {
            http_response_code(404);
            exit('Denúncia não encontrada.');
        }

        $imagensDenuncia = $this->animalModel->listarImagens($denunciaId);
        $comentariosDenuncia = $this->comentarioModel->listarPorAnimal($denunciaId);
        $historicoDenuncia = $this->historicoStatusModel->listarPorAnimal($denunciaId);

        // Prépara dados de permissão para a view
        $tipoUsuarioLogado = (string)($_SESSION['tipo_usuario'] ?? 'Comum');
        $podeAlterarStatus = in_array($tipoUsuarioLogado, ['ONG', 'Autoridade', 'Admin'], true);
        $statusDisponiveis = $this->obterStatusDisponiveisParaTipo($tipoUsuarioLogado);

        $this->view('animais/detalhes', [
            'denuncia'            => $informacaoDenuncia,
            'imagens'             => $imagensDenuncia,
            'comentarios'         => $comentariosDenuncia,
            'historico'           => $historicoDenuncia,
            'podeAlterarStatus'   => $podeAlterarStatus,
            'statusDisponiveis'   => $statusDisponiveis,
            'imagemPlaceholder'   => BASE_URL . '/assets/img/placeholder-animal.jpg',
        ]);
    }

    /**
     * Retorna os status disponíveis para alterar conforme o tipo de usuário.
     */
    private function obterStatusDisponiveisParaTipo(string $tipoUsuario): array
    {
        return self::StatusPermitidosPorTipo[$tipoUsuario] ?? [];
    }

    /**
     * GET: Exibe formulário de denúncia
     * POST: Processa e cria nova denúncia
     */
    public function reportar(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarNovaDenuncia();
            return;
        }

        $this->view('animais/reportar');
    }

    /**
     * Processa a criação de uma nova denúncia.
     */
    private function processarNovaDenuncia(): void
    {
        $dadosFormulario = $this->extrairDadosFormulario();
        
        $erroValidacao = $this->validarDadosDenuncia($dadosFormulario);
        if ($erroValidacao !== null) {
            $this->view('animais/reportar', [
                'erro' => $erroValidacao,
                'old'  => $_POST,
            ]);
            return;
        }

        try {
            $caminhosFotos = $this->salvarUploadsFotosDenuncia('fotos', 5, 2 * 1024 * 1024);
        } catch (\Throwable $excecao) {
            $this->view('animais/reportar', [
                'erro' => $excecao->getMessage(),
                'old'  => $_POST,
            ]);
            return;
        }

        $descricaoConstruida = $this->construirDescricaoLocalizacao(
            $dadosFormulario['rua'],
            $dadosFormulario['numero'],
            $dadosFormulario['bairro'],
            $dadosFormulario['cidade'],
            $dadosFormulario['estado'],
            $dadosFormulario['cep']
        );

        $fotoCapa = $caminhosFotos[0] ?? null;

        $dadosParaSalvar = [
            'usuario_id'  => (string)($_SESSION['usuario_id'] ?? ''),
            'foto'        => $fotoCapa,
            'titulo'      => $dadosFormulario['titulo'],
            'descricao'   => $dadosFormulario['descricao'] !== '' ? $dadosFormulario['descricao'] : null,
            'especie'     => $dadosFormulario['especie'],
            'cor'         => $dadosFormulario['cor'] !== '' ? $dadosFormulario['cor'] : null,
            'condicao'    => $dadosFormulario['condicao'] !== '' ? $dadosFormulario['condicao'] : null,
            'localizacao' => $descricaoConstruida !== '' ? $descricaoConstruida : null,
        ];

        $denunciaId = $this->animalModel->criar($dadosParaSalvar);

        if (!$denunciaId) {
            $this->view('animais/reportar', [
                'erro' => 'Não foi possível salvar a denúncia.',
                'old'  => $_POST,
            ]);
            return;
        }

        if (!empty($caminhosFotos)) {
            $this->animalModel->adicionarImagens($denunciaId, $caminhosFotos);
        }

        $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($denunciaId));
    }

    /**
     * Extrai e padroniza dados do formulário de denúncia.
     */
    private function extrairDadosFormulario(): array
    {
        return [
            'titulo'      => trim((string)($_POST['titulo'] ?? '')),
            'especie'     => trim((string)($_POST['especie'] ?? '')),
            'cor'         => trim((string)($_POST['cor'] ?? '')),
            'condicao'    => trim((string)($_POST['condicao'] ?? '')),
            'descricao'   => trim((string)($_POST['descricao'] ?? '')),
            'cep'         => trim((string)($_POST['cep'] ?? '')),
            'rua'         => trim((string)($_POST['rua'] ?? '')),
            'numero'      => trim((string)($_POST['numero'] ?? '')),
            'bairro'      => trim((string)($_POST['bairro'] ?? '')),
            'cidade'      => trim((string)($_POST['cidade'] ?? '')),
            'estado'      => trim((string)($_POST['estado'] ?? '')),
        ];
    }

    /**
     * Valida campos obrigatórios de uma denúncia.
     * Retorna mensagem de erro ou null se tudo estiver válido.
     */
    private function validarDadosDenuncia(array $dados): ?string
    {
        if ($dados['titulo'] === '') {
            return 'Informe o título da denúncia.';
        }

        if ($dados['especie'] === '') {
            return 'Espécie é obrigatória.';
        }

        return null;
    }

    /**
     * Constrói a descrição de localização a partir dos campos de endereço.
     */
    private function construirDescricaoLocalizacao(
        string $rua,
        string $numero,
        string $bairro,
        string $cidade,
        string $estado,
        string $cep
    ): string
    {
        $partesEndereco = array_filter([
            $rua !== '' ? $rua : null,
            $numero !== '' ? "nº {$numero}" : null,
            $bairro !== '' ? $bairro : null,
            ($cidade !== '' || $estado !== '') ? trim(implode(' - ', array_filter([$cidade, $estado]))) : null,
            $cep !== '' ? "CEP {$cep}" : null,
        ]);

        return implode(', ', $partesEndereco);
    }

    /**
     * Normaliza array $_FILES para facilitar processamento de uploads múltiplos.
     */
    private function normalizarArquivosUpload(string $nomeCampo): array
    {
        if (empty($_FILES[$nomeCampo])) {
            return [];
        }

        $arquivos = $_FILES[$nomeCampo];

        // Input simples (name="arquivo")
        if (!is_array($arquivos['name'])) {
            return [[
                'name'     => $arquivos['name'] ?? '',
                'type'     => $arquivos['type'] ?? '',
                'tmp_name' => $arquivos['tmp_name'] ?? '',
                'error'    => $arquivos['error'] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $arquivos['size'] ?? 0,
            ]];
        }

        // Input múltiplo (name="arquivos[]")
        $resultado = [];
        $quantidade = count($arquivos['name'] ?? []);
        for ($indice = 0; $indice < $quantidade; $indice++) {
            $resultado[] = [
                'name'     => $arquivos['name'][$indice] ?? '',
                'type'     => $arquivos['type'][$indice] ?? '',
                'tmp_name' => $arquivos['tmp_name'][$indice] ?? '',
                'error'    => $arquivos['error'][$indice] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $arquivos['size'][$indice] ?? 0,
            ];
        }
        return $resultado;
    }

    /**
     * Salva arquivos de fotos de denúncias em public/uploads/animais.
     * Retorna array com caminhos relativos dos arquivos salvos.
     * Lança RuntimeException em caso de erro.
     */
    private function salvarUploadsFotosDenuncia(string $nomeCampo, int $maxArquivos = 5, int $tamanhoMaximoBytes = 2097152): array
    {
        $caminhoUpload = dirname(__DIR__, 2) . '/public/uploads/animais';
        $caminhoDebug = dirname(__DIR__, 2) . '/public/uploads/debug_upload.txt';

        // Garantir pasta base
        $caminhoBaseUploads = dirname(__DIR__, 2) . '/public/uploads';
        if (!is_dir($caminhoBaseUploads)) {
            @mkdir($caminhoBaseUploads, 0775, true);
        }

        $informacoesDebug = [
            'time' => date('c'),
            'field' => $nomeCampo,
            'uploadDir' => $caminhoUpload,
            'is_dir(uploadDir)' => is_dir($caminhoUpload),
            'is_writable(uploadDir)' => is_writable($caminhoUpload),
            'ini' => [
                'file_uploads' => ini_get('file_uploads'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'max_file_uploads' => ini_get('max_file_uploads'),
                'upload_tmp_dir' => ini_get('upload_tmp_dir'),
            ],
            '_FILES_keys' => array_keys($_FILES ?? []),
            '_FILES_field' => $_FILES[$nomeCampo] ?? null,
        ];

        // Cria pasta de destino
        if (!is_dir($caminhoUpload)) {
            @mkdir($caminhoUpload, 0775, true);
        }
        $informacoesDebug['after_mkdir_is_dir'] = is_dir($caminhoUpload);
        $informacoesDebug['after_mkdir_is_writable'] = is_writable($caminhoUpload);

        @file_put_contents($caminhoDebug, json_encode($informacoesDebug, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n", FILE_APPEND);

        // Normaliza e filtra arquivos sem erro
        $arquivos = $this->normalizarArquivosUpload($nomeCampo);
        $arquivos = array_values(array_filter($arquivos, fn($arq) => ($arq['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE));

        if (count($arquivos) === 0) {
            @file_put_contents($caminhoDebug, "SEM ARQUIVOS: array vazio após normalização.\n\n", FILE_APPEND);
            return [];
        }

        if (count($arquivos) > $maxArquivos) {
            throw new RuntimeException("Selecione no máximo {$maxArquivos} imagens.");
        }

        if (!is_writable($caminhoUpload)) {
            throw new RuntimeException("Sem permissão para salvar em: {$caminhoUpload}");
        }

        $caminhosSalvos = [];
        $arquivosProcessados = [];

        foreach ($arquivos as $indiceArquivo => $arquivo) {
            $tamanho = (int)($arquivo['size'] ?? 0);

            @file_put_contents($caminhoDebug, "ARQUIVO {$indiceArquivo}: " . json_encode([
                'name' => $arquivo['name'] ?? null,
                'size' => $tamanho,
                'error' => $arquivo['error'] ?? null,
                'tmp_name' => $arquivo['tmp_name'] ?? null,
                'is_uploaded_file' => isset($arquivo['tmp_name']) ? is_uploaded_file($arquivo['tmp_name']) : null,
            ], JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

            if ($tamanho > $tamanhoMaximoBytes) {
                $megabytes = (int)($tamanhoMaximoBytes / 1024 / 1024);
                throw new RuntimeException("Cada imagem deve ter no máximo {$megabytes}MB.");
            }

            $chaveDeduplicacao = ($arquivo['name'] ?? '') . '|' . (string)$tamanho;
            if (isset($arquivosProcessados[$chaveDeduplicacao])) {
                continue;
            }
            $arquivosProcessados[$chaveDeduplicacao] = true;

            $caminhoTemporario = (string)($arquivo['tmp_name'] ?? '');
            if ($caminhoTemporario === '' || !is_uploaded_file($caminhoTemporario)) {
                throw new RuntimeException("Arquivo inválido recebido no upload (tmp_name inválido).");
            }

            $detectorTipo = new finfo(FILEINFO_MIME_TYPE);
            $tipoMime = $detectorTipo->file($caminhoTemporario);

            $tiposPermitidos = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];
            if (!isset($tiposPermitidos[$tipoMime])) {
                throw new RuntimeException("Formato inválido ({$tipoMime}). Use JPG, PNG ou WEBP.");
            }

            $extensao = $tiposPermitidos[$tipoMime];
            $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;
            $caminhoDestino = $caminhoUpload . '/' . $nomeArquivo;

            $movimentoSucesso = move_uploaded_file($caminhoTemporario, $caminhoDestino);
            @file_put_contents($caminhoDebug, "MOVE {$indiceArquivo}: ok=" . ($movimentoSucesso ? '1' : '0') . " dest={$caminhoDestino}\n\n", FILE_APPEND);

            if (!$movimentoSucesso) {
                $ultimoErro = error_get_last();
                @file_put_contents($caminhoDebug, "MOVE FAILED last_error=" . json_encode($ultimoErro) . "\n\n", FILE_APPEND);
                throw new RuntimeException("Falha ao salvar a imagem no servidor (move_uploaded_file).");
            }

            $caminhosSalvos[] = 'uploads/animais/' . $nomeArquivo;
        }

        return $caminhosSalvos;
    }

    /**
     * Exclui uma denúncia (apenas dono ou admin).
     */
    public function excluir(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método não permitido.');
        }

        $denunciaId = (string)($_POST['id'] ?? '');
        if ($denunciaId === '') {
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        $usuarioId = (string)($_SESSION['usuario_id'] ?? '');
        $tipoUsuario = (string)($_SESSION['tipo_usuario'] ?? '');
        $ehAdministrador = in_array(mb_strtolower($tipoUsuario), ['admin', 'administrador'], true);

        $denunciaDonoId = $this->animalModel->buscarDonoId($denunciaId);

        if ($denunciaDonoId === null) {
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        $temPermissaoExcluir = $ehAdministrador || $denunciaDonoId === $usuarioId;
        if (!$temPermissaoExcluir) {
            http_response_code(403);
            exit('Você não tem permissão para excluir esta denúncia.');
        }

        $this->animalModel->excluir($denunciaId);
        $this->redirect('/index.php?c=animal&a=listar');
    }

    /**
     * Atualiza o status de uma denúncia com registro de histórico.
     */
    public function atualizarStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método não permitido.');
        }

        if (empty($_SESSION['usuario_id'])) {
            $_SESSION['flash_error'] = 'Você precisa estar logado para alterar o status.';
            $this->redirect('/index.php?c=auth&a=login');
            return;
        }

        $usuarioId = (string)$_SESSION['usuario_id'];
        $tipoUsuario = (string)($_SESSION['tipo_usuario'] ?? 'Comum');
        $denunciaId = trim((string)($_POST['animal_id'] ?? ''));
        $novoStatus = trim((string)($_POST['status'] ?? ''));

        if ($denunciaId === '' || $novoStatus === '') {
            $_SESSION['flash_error'] = 'Dados inválidos para atualizar status.';
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        $informacaoDenuncia = $this->animalModel->buscarPorId($denunciaId);
        if (!$informacaoDenuncia) {
            $_SESSION['flash_error'] = 'Denúncia não encontrada.';
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        $statusAtual = (string)($informacaoDenuncia['status'] ?? 'Aguardando');

        if ($novoStatus === $statusAtual) {
            $_SESSION['flash_success'] = 'Nenhuma alteração: o status já estava definido.';
            $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($denunciaId));
            return;
        }

        $statusDisponiveisPorTipo = self::StatusPermitidosPorTipo[$tipoUsuario] ?? [];

        if (!in_array($novoStatus, $statusDisponiveisPorTipo, true)) {
            $_SESSION['flash_error'] = 'Status inválido para o seu perfil.';
            $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($denunciaId));
            return;
        }

        $atualizacaoSucesso = $this->animalModel->atualizarStatusComHistorico(
            $denunciaId,
            $statusAtual,
            $novoStatus,
            $usuarioId
        );

        if (!$atualizacaoSucesso) {
            $_SESSION['flash_error'] = 'Falha ao atualizar o status. Tente novamente.';
            $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($denunciaId));
            return;
        }

        $_SESSION['flash_success'] = 'Status atualizado com sucesso.';
        $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($denunciaId));
    }

}
