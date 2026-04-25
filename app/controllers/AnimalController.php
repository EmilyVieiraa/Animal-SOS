<?php
declare(strict_types=1);

require_once APP_PATH . 'helpers/view_helpers.php';

final class AnimalController extends Controller
{
    private HistoricoStatus $historicoStatusModel;
    private Animal $animalModel;
    private Comentario $comentarioModel;

    /**
     * Contextos de validação CSRF para este controller.
     */
    private const CSRF_CONTEXTO_REPORTAR = 'animal_reportar';
    private const CSRF_CONTEXTO_ATUALIZAR_STATUS = 'animal_atualizar_status';
    private const CSRF_CONTEXTO_EXCLUIR = 'animal_excluir';

    /**
     * Regras de transição de status por tipo de usuário.
     */
    private const StatusPermitidosPorTipo = [
        'Comum'      => [],
        'ONG'        => ['Em andamento', 'Adoção', 'Resgatado', 'Finalizado'],
        'Autoridade' => ['Aguardando', 'Em andamento', 'Adoção', 'Resgatado', 'Finalizado'],
    ];

    /**
     * UFs brasileiras válidas para validação.
     */
    private const ESTADOS_BRASIL_VALIDOS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS',
        'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC',
        'SP', 'SE', 'TO',
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

        $totalAnimais = $this->animalModel->contarAnimais($filtroStatus);
        $totalPaginas = (int)ceil($totalAnimais / $itensParPagina) ?: 1;

        if ($paginaAtual > $totalPaginas) {
            $paginaAtual = $totalPaginas;
            $deslocamento = ($paginaAtual - 1) * $itensParPagina;
        }

        $animaisListados = $this->animalModel->listarComPaginacao($filtroStatus, $itensParPagina, $deslocamento);

        $this->view('animais/listar', [
            'animais'        => $animaisListados,
            'status'         => $filtroStatus,
            'page'           => $paginaAtual,
            'perPage'        => $itensParPagina,
            'total'          => $totalAnimais,
            'totalPages'     => $totalPaginas,
        ]);
    }

    /**
     * Exibe detalhes de uma denúncia específica.
     */
    public function detalhes(): void
    {
        $animalId = (string)($_GET['id'] ?? '');
        if ($animalId === '') {
            flashDefinir('flash_error', 'Denúncia inválida ou não informada.');
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        $dadosAnimal = $this->animalModel->buscarPorId($animalId);
        if (!$dadosAnimal) {
            http_response_code(404);
            exit('Denúncia não encontrada.');
        }

        $imagensAnimal = $this->animalModel->listarImagens($animalId);
        $comentariosAnimal = $this->comentarioModel->listarPorAnimal($animalId);
        $historicoAnimal = $this->historicoStatusModel->listarPorAnimal($animalId);

        // Prépara dados de permissão para a view
        $tipoUsuarioLogado = (string)($_SESSION['tipo_usuario'] ?? 'Comum');
        $podeAlterarStatus = in_array($tipoUsuarioLogado, ['ONG', 'Autoridade'], true);
        $statusDisponiveis = $this->obterStatusDisponiveisParaTipo($tipoUsuarioLogado);

        $this->view('animais/detalhes', [
            'animal'              => $dadosAnimal,
            'imagens'             => $imagensAnimal,
            'comentarios'         => $comentariosAnimal,
            'historico'           => $historicoAnimal,
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
            $this->processarRegistroAnimal();
            return;
        }

        $this->view('animais/reportar', [
            '_csrf_token' => csrfToken(self::CSRF_CONTEXTO_REPORTAR),
        ]);
    }

    /**
     * Processa a criação de uma nova denúncia.
     */
    private function processarRegistroAnimal(): void
    {
        // Validação CSRF
        if (!$this->validarCsrf(self::CSRF_CONTEXTO_REPORTAR)) {
            flashDefinir('flash_error', 'Token de segurança inválido ou expirado. Tente novamente.');
            $this->view('animais/reportar', [
                'old' => $_POST,
                '_csrf_token' => csrfToken(self::CSRF_CONTEXTO_REPORTAR),
            ]);
            return;
        }

        $dadosFormulario = $this->extrairDadosFormulario();
        
        $erroValidacao = $this->validarDadosAnimal($dadosFormulario);
        if ($erroValidacao !== null) {
            $this->view('animais/reportar', [
                'erro' => $erroValidacao,
                'old'  => $_POST,
                '_csrf_token' => csrfToken(self::CSRF_CONTEXTO_REPORTAR),
            ]);
            return;
        }

        try {
            $caminhosFotos = $this->salvarFotosAnimal('fotos', 5, 2 * 1024 * 1024);
        } catch (\Throwable $excecao) {
            flashDefinir('flash_error', $excecao->getMessage());
            $this->view('animais/reportar', [
                'old'  => $_POST,
                '_csrf_token' => csrfToken(self::CSRF_CONTEXTO_REPORTAR),
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

        $animalId = $this->animalModel->criar($dadosParaSalvar);

        if (!$animalId) {
            flashDefinir('flash_error', 'Não foi possível salvar a denúncia. Tente novamente.');
            $this->view('animais/reportar', [
                'old'  => $_POST,
                '_csrf_token' => csrfToken(self::CSRF_CONTEXTO_REPORTAR),
            ]);
            return;
        }

        if (!empty($caminhosFotos)) {
            $this->animalModel->adicionarImagens($animalId, $caminhosFotos);
        }

        flashDefinir('flash_success', 'Denúncia registrada com sucesso! Confira os detalhes abaixo.');
        $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($animalId));
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
     * Valida campos obrigatórios e formato de uma denúncia.
     * Retorna mensagem de erro ou null se tudo estiver válido.
     */
    private function validarDadosAnimal(array $dados): ?string
    {
        if ($dados['titulo'] === '') {
            return 'Informe o título da denúncia.';
        }

        if (mb_strlen($dados['titulo']) > 200) {
            return 'Título muito longo. Use até 200 caracteres.';
        }

        if ($dados['especie'] === '') {
            return 'Espécie é obrigatória.';
        }

        // Validação de CEP se preenchido
        if ($dados['cep'] !== '') {
            $cepLimpo = preg_replace('/\D+/', '', $dados['cep']);
            if (strlen($cepLimpo) !== 8) {
                return 'CEP inválido. Use o formato 00000-000 ou 00000000.';
            }
        }

        // Validação de UF se preenchido
        if ($dados['estado'] !== '') {
            $estadoUpper = strtoupper(trim($dados['estado']));
            if (!in_array($estadoUpper, self::ESTADOS_BRASIL_VALIDOS, true)) {
                return 'Estado inválido. Selecione um estado válido.';
            }
        }

        // Limite de caracteres para campos de texto
        $camposTexto = ['titulo' => 200, 'rua' => 100, 'bairro' => 100, 'cidade' => 100, 'descricao' => 2000];
        foreach ($camposTexto as $campo => $maximo) {
            if (isset($dados[$campo]) && mb_strlen($dados[$campo]) > $maximo) {
                return "Campo '{$campo}' excedeu o limite de {$maximo} caracteres.";
            }
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
    private function salvarFotosAnimal(string $nomeCampo, int $maxArquivos = 5, int $tamanhoMaximoBytes = 2097152): array
    {
        $caminhoUpload = dirname(__DIR__, 2) . '/public/uploads/animais';

        // Garantir pasta base
        $caminhoBaseUploads = dirname(__DIR__, 2) . '/public/uploads';
        if (!is_dir($caminhoBaseUploads)) {
            if (!@mkdir($caminhoBaseUploads, 0775, true)) {
                throw new RuntimeException('Não foi possível criar pasta de uploads.');
            }
        }

        // Cria pasta de destino para animais
        if (!is_dir($caminhoUpload)) {
            if (!@mkdir($caminhoUpload, 0775, true)) {
                throw new RuntimeException('Não foi possível criar pasta de uploads de animais.');
            }
        }

        // Normaliza e filtra arquivos sem erro
        $arquivos = $this->normalizarArquivosUpload($nomeCampo);
        $arquivos = array_values(array_filter($arquivos, fn($arq) => ($arq['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE));

        if (count($arquivos) === 0) {
            return [];
        }

        if (count($arquivos) > $maxArquivos) {
            throw new RuntimeException("Selecione no máximo {$maxArquivos} imagens.");
        }

        if (!is_writable($caminhoUpload)) {
            throw new RuntimeException("Pasta de upload sem permissão de escrita.");
        }

        $caminhosSalvos = [];
        $arquivosProcessados = [];

        foreach ($arquivos as $arquivo) {
            $tamanho = (int)($arquivo['size'] ?? 0);

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
                throw new RuntimeException("Arquivo inválido recebido no upload.");
            }

            $detectorTipo = new finfo(FILEINFO_MIME_TYPE);
            $tipoMime = $detectorTipo->file($caminhoTemporario);

            $tiposPermitidos = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];
            if (!isset($tiposPermitidos[$tipoMime])) {
                throw new RuntimeException("Formato inválido. Use JPG, PNG ou WEBP.");
            }

            $extensao = $tiposPermitidos[$tipoMime];
            $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;
            $caminhoDestino = $caminhoUpload . '/' . $nomeArquivo;

            if (!move_uploaded_file($caminhoTemporario, $caminhoDestino)) {
                error_log("Falha ao fazer upload do arquivo: {$nomeArquivo}");
                throw new RuntimeException("Falha ao salvar a imagem no servidor.");
            }

            $caminhosSalvos[] = 'uploads/animais/' . $nomeArquivo;
        }

        return $caminhosSalvos;
    }

    /**
     * Exclui uma denúncia (apenas dono ou autoridade).
     */
    public function excluir(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método não permitido.');
        }

        $animalId = (string)($_POST['id'] ?? '');
        if ($animalId === '') {
            flashDefinir('flash_error', 'Denúncia inválida.');
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        // Validação CSRF
        if (!$this->validarCsrf(self::CSRF_CONTEXTO_EXCLUIR)) {
            flashDefinir('flash_error', 'Token de segurança inválido. Tente novamente.');
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        $usuarioId = (string)($_SESSION['usuario_id'] ?? '');
        $tipoUsuario = (string)($_SESSION['tipo_usuario'] ?? 'Comum');
        $ehAutoridade = $tipoUsuario === 'Autoridade';

        $donoId = $this->animalModel->buscarDonoId($animalId);

        if ($donoId === null) {
            flashDefinir('flash_error', 'Denúncia não encontrada.');
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        $temPermissaoExcluir = $ehAutoridade || $donoId === $usuarioId;
        if (!$temPermissaoExcluir) {
            http_response_code(403);
            exit('Você não tem permissão para excluir esta denúncia.');
        }

        $excluiuOk = $this->animalModel->excluir($animalId);
        if (!$excluiuOk) {
            flashDefinir('flash_error', 'Falha ao excluir denúncia. Tente novamente.');
            $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($animalId));
            return;
        }

        flashDefinir('flash_success', 'Denúncia excluída com sucesso.');
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

        $this->requireAuth();

        // Validação CSRF
        if (!$this->validarCsrf(self::CSRF_CONTEXTO_ATUALIZAR_STATUS)) {
            flashDefinir('flash_error', 'Token de segurança inválido ou expirado. Tente novamente.');
            $animalId = trim((string)($_POST['animal_id'] ?? ''));
            $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($animalId));
            return;
        }

        $usuarioId = (string)$_SESSION['usuario_id'];
        $tipoUsuario = (string)($_SESSION['tipo_usuario'] ?? 'Comum');
        $animalId = trim((string)($_POST['animal_id'] ?? ''));
        $novoStatus = trim((string)($_POST['status'] ?? ''));

        if ($animalId === '' || $novoStatus === '') {
            flashDefinir('flash_error', 'Dados inválidos para atualizar status.');
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        $dadosAnimal = $this->animalModel->buscarPorId($animalId);
        if (!$dadosAnimal) {
            flashDefinir('flash_error', 'Denúncia não encontrada.');
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        $statusAtual = (string)($dadosAnimal['status'] ?? 'Aguardando');

        if ($novoStatus === $statusAtual) {
            flashDefinir('flash_info', 'O status já estava definido como ' . $statusAtual . '.');
            $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($animalId));
            return;
        }

        $statusDisponiveisPorTipo = self::StatusPermitidosPorTipo[$tipoUsuario] ?? [];

        if (!in_array($novoStatus, $statusDisponiveisPorTipo, true)) {
            flashDefinir('flash_error', 'Status inválido para o seu perfil de usuário.');
            $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($animalId));
            return;
        }

        $atualizouSucesso = $this->animalModel->atualizarStatusComHistorico(
            $animalId,
            $statusAtual,
            $novoStatus,
            $usuarioId
        );

        if (!$atualizouSucesso) {
            flashDefinir('flash_error', 'Falha ao atualizar o status. Tente novamente.');
            $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($animalId));
            return;
        }

        flashDefinir('flash_success', 'Status atualizado com sucesso para: ' . $novoStatus);
        $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($animalId));
    }

    /**
     * Valida token CSRF e consome o token de sessão.
     */
    private function validarCsrf(string $contexto): bool
    {
        return csrfValidarConsumo($contexto);
    }

}
