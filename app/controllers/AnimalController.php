<?php
declare(strict_types=1);

final class AnimalController extends Controller
{
    private HistoricoStatus $historicoStatusModel;

    private Animal $animalModel;
    private Comentario $comentarioModel;
    private Status $statusModel;

    private const STATUS_VALIDOS = ['Aguardando', 'Em andamento', 'Resgatado', 'Adoção', 'Finalizado'];

    /**
     * Regras de transição por papel.
     * Ajuste se quiser permitir mais/menos.
     */
    private const STATUS_POR_PAPEL = [
        'Comum'      => [],
        'ONG'        => ['Em andamento', 'Adoção', 'Resgatado', 'Finalizado'],
        'Autoridade' => ['Aguardando', 'Em andamento', 'Adoção', 'Resgatado', 'Finalizado'],
        'Admin'      => ['Aguardando', 'Em andamento', 'Adoção', 'Resgatado', 'Finalizado'],
    ];

    /**
     * Retorna true se o usuário pode mudar o status do animal para $novoStatus
     * de acordo com o papel + regras adicionais (ex.: ONG precisa ser responsável).
     */
    private function podeAlterarStatus(array $usuario, array $animal, string $novoStatus): bool
    {
        $papel = $usuario['tipo_usuario'] ?? 'Comum';

        // valida status conhecido
        if (!in_array($novoStatus, self::STATUS_VALIDOS, true)) {
            return false;
        }

        // valida se o papel existe na matriz
        if (!array_key_exists($papel, self::STATUS_POR_PAPEL)) {
            return false;
        }

        // valida se o papel pode escolher esse status
        if (!in_array($novoStatus, self::STATUS_POR_PAPEL[$papel], true)) {
            return false;
        }

        // regra extra: ONG só pode mudar se for responsável
        if ($papel === 'ONG') {
            $responsavelId = $animal['responsavel_id'] ?? null;
            return $responsavelId === ($usuario['id'] ?? null);
        }

        return true;
    }

    /**
     * Construtor
     */
    public function __construct()
    {
        $this->animalModel = new Animal();
        $this->comentarioModel = new Comentario();
        $this->statusModel = new Status();
        $this->historicoStatusModel = new HistoricoStatus();
    }

    /**
     * Lista todas as denúncias de animais (público)
     */
    public function listar(): void
    {
        $status = $_GET['status'] ?? '';
        $status = is_string($status) ? trim($status) : '';

        $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        if ($page < 1) $page = 1;

        $perPage = 9;
        $offset  = ($page - 1) * $perPage;

        // Total de registros (para calcular páginas)
        $total = $this->animalModel->countDenuncias($status);
        $totalPages = (int)ceil($total / $perPage);
        if ($totalPages < 1) $totalPages = 1;

        // Ajusta caso o usuário peça uma página além do máximo
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        // Lista da página atual
        $animais = $this->animalModel->listarPaginado($status, $perPage, $offset);

        $this->view('animais/listar', [
            'animais'     => $animais,
            'status'      => $status,
            'page'        => $page,
            'perPage'     => $perPage,
            'total'       => $total,
            'totalPages'  => $totalPages,
        ]);
    }

    /**
     * Detalhes de uma denúncia
     */
    public function detalhes(): void
    {
        $id = (string)($_GET['id'] ?? '');
        if ($id === '') {
            // Em vez de morrer, redireciona para a listagem (melhor UX)
            $_SESSION['flash_error'] = 'Denúncia inválida ou não informada.';
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        $animal = $this->animalModel->buscarPorId($id);
        if (!$animal) {
            http_response_code(404);
            exit('Denúncia não encontrada.');
        }

        $animalId = (string)($animal['id'] ?? $id);

        $imagens = method_exists($this->animalModel, 'listarImagens')
            ? $this->animalModel->listarImagens($animalId)
            : [];

        $comentarios = method_exists($this->comentarioModel, 'listarPorAnimal')
            ? $this->comentarioModel->listarPorAnimal($animalId)
            : [];

        $historico = $this->historicoStatusModel->listarPorAnimal($animalId);

        $this->view('animais/detalhes', [
            'animal'      => $animal,
            'imagens'     => $imagens,
            'comentarios' => $comentarios,
            'historico'   => $historico,
        ]);
    }

    /**
     * GET  -> mostra formulário
     * POST -> cria denúncia
     */
    public function reportar(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // =========================
            // Campos do formulário
            // =========================
            $titulo    = trim((string)($_POST['titulo'] ?? ''));
            $especie   = trim((string)($_POST['especie'] ?? ''));
            $cor       = trim((string)($_POST['cor'] ?? ''));
            $condicao  = trim((string)($_POST['condicao'] ?? ''));
            $descricao = trim((string)($_POST['descricao'] ?? ''));

            // Endereço (CEP + campos)
            $cep    = trim((string)($_POST['cep'] ?? ''));
            $rua    = trim((string)($_POST['rua'] ?? ''));
            $numero = trim((string)($_POST['numero'] ?? ''));
            $bairro = trim((string)($_POST['bairro'] ?? ''));
            $cidade = trim((string)($_POST['cidade'] ?? ''));
            $estado = trim((string)($_POST['estado'] ?? ''));

            // =========================
            // Validações
            // =========================

            if ($titulo === '') {
            $this->view('animais/reportar', [
                'erro' => 'Informe o título da denúncia.',
                'old'  => $_POST
            ]);
            return;
            }

            if ($especie === '') {
                $this->view('animais/reportar', [
                    'erro' => 'Espécie é obrigatória.',
                    'old'  => $_POST,
                ]);
                return;
            }

            // =========================
            // Monta localizacao (texto)
            // =========================
            $enderecoParts = array_filter([
                $rua !== '' ? $rua : null,
                $numero !== '' ? "nº {$numero}" : null,
                $bairro !== '' ? $bairro : null,
                ($cidade !== '' || $estado !== '') ? trim($cidade . ' - ' . $estado) : null,
                $cep !== '' ? "CEP {$cep}" : null,
            ]);

            $localizacaoFinal = implode(', ', $enderecoParts);

            // =========================
            // Upload (1 ou várias)
            // =========================
            try {
                $paths = $this->salvarUploadsAnimais('fotos', 5, 2 * 1024 * 1024);
            } catch (\Throwable $e) {
                $this->view('animais/reportar', [
                    'erro' => $e->getMessage(),
                    'old'  => $_POST,
                ]);
                return;
            }

            // CAPA = primeira imagem
            $fotoCapa = $paths[0] ?? null;

            // =========================
            // Salva denúncia
            // =========================
            $dados = [
                'usuario_id'  => (string)($_SESSION['usuario_id'] ?? ''),
                'foto'        => $fotoCapa, // capa
                'titulo'      => $titulo,
                'descricao'   => $descricao !== '' ? $descricao : null,
                'especie'     => $especie,
                'cor'         => $cor !== '' ? $cor : null,
                'condicao'    => $condicao !== '' ? $condicao : null,
                'localizacao' => $localizacaoFinal !== '' ? $localizacaoFinal : null,
            ];

            $titulo = trim((string)($_POST['titulo'] ?? ''));
            if ($titulo === '') {
            // retorna erro
            }

            $id = $this->animalModel->criar($dados);

            if (!$id) {
                $this->view('animais/reportar', [
                    'erro' => 'Não foi possível salvar a denúncia.',
                    'old'  => $_POST,
                ]);
                return;
            }

            // GALERIA
            if (!empty($paths)) {
                $this->animalModel->adicionarImagens((string)$id, $paths);
            }

            $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode((string)$id));
            return;
        }

        // GET
        $this->view('animais/reportar');
    }

    /**
     * Normaliza o array $_FILES para facilitar o processamento de uploads múltiplos.
     * Retorna um array de arquivos no formato padrão do PHP.
     */
    private function normalizarArquivos(string $field): array
    {
        if (empty($_FILES[$field])) return [];

        $f = $_FILES[$field];

        // caso seja input single (name="foto")
        if (!is_array($f['name'])) {
            return [[
                'name'     => $f['name'] ?? '',
                'type'     => $f['type'] ?? '',
                'tmp_name' => $f['tmp_name'] ?? '',
                'error'    => $f['error'] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $f['size'] ?? 0,
            ]];
        }

        // caso seja input multiple (name="fotos[]")
        $out = [];
        $count = count($f['name']);
        for ($i = 0; $i < $count; $i++) {
            $out[] = [
                'name'     => $f['name'][$i] ?? '',
                'type'     => $f['type'][$i] ?? '',
                'tmp_name' => $f['tmp_name'][$i] ?? '',
                'error'    => $f['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $f['size'][$i] ?? 0,
            ];
        }
        return $out;
    }

    /**
     * Salva múltiplos arquivos enviados via upload em public/uploads/animais.
     * Retorna um array com os caminhos relativos dos arquivos salvos.
     * Lança RuntimeException em caso de erro.
     */
    private function salvarUploadsAnimais(string $field, int $maxFiles = 5, int $maxSizeBytes = 2097152): array
    {
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/animais';
        $debugFile = dirname(__DIR__, 2) . '/public/uploads/debug_upload.txt';

        // cria pasta base do debug
        $baseUploads = dirname(__DIR__, 2) . '/public/uploads';
        if (!is_dir($baseUploads)) @mkdir($baseUploads, 0775, true);

        $debug = [
            'time' => date('c'),
            'field' => $field,
            'uploadDir' => $uploadDir,
            'is_dir(uploadDir)' => is_dir($uploadDir),
            'is_writable(uploadDir)' => is_writable($uploadDir),
            'ini' => [
                'file_uploads' => ini_get('file_uploads'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'max_file_uploads' => ini_get('max_file_uploads'),
                'upload_tmp_dir' => ini_get('upload_tmp_dir'),
            ],
            '_FILES_keys' => array_keys($_FILES ?? []),
            '_FILES_field' => $_FILES[$field] ?? null,
        ];

        // garante pasta de destino
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        $debug['after_mkdir_is_dir'] = is_dir($uploadDir);
        $debug['after_mkdir_is_writable'] = is_writable($uploadDir);

        @file_put_contents($debugFile, json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n", FILE_APPEND);

        // normaliza e filtra vazios
        $files = $this->normalizarArquivos($field);
        $files = array_values(array_filter($files, fn($x) => ($x['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE));

        if (count($files) === 0) {
            @file_put_contents($debugFile, "SEM ARQUIVOS: \$files vazio após normalização.\n\n", FILE_APPEND);
            return [];
        }

        if (count($files) > $maxFiles) {
            throw new RuntimeException("Selecione no máximo {$maxFiles} imagens.");
        }

        if (!is_writable($uploadDir)) {
            throw new RuntimeException("Sem permissão para salvar em: {$uploadDir}");
        }

        $paths = [];
        $seen  = [];

        foreach ($files as $idx => $file) {
            $size = (int)($file['size'] ?? 0);

            @file_put_contents($debugFile, "FILE {$idx}: " . json_encode([
                'name' => $file['name'] ?? null,
                'size' => $size,
                'error' => $file['error'] ?? null,
                'tmp_name' => $file['tmp_name'] ?? null,
                'is_uploaded_file' => isset($file['tmp_name']) ? is_uploaded_file($file['tmp_name']) : null,
            ], JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

            if ($size > $maxSizeBytes) {
                $mb = (int)($maxSizeBytes / 1024 / 1024);
                throw new RuntimeException("Cada imagem deve ter no máximo {$mb}MB.");
            }

            $key = ($file['name'] ?? '') . '|' . (string)$size;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $tmp = (string)($file['tmp_name'] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                throw new RuntimeException("Arquivo inválido recebido no upload (tmp_name inválido).");
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($tmp);

            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];
            if (!isset($allowed[$mime])) {
                throw new RuntimeException("Formato inválido ({$mime}). Use JPG, PNG ou WEBP.");
            }

            $ext = $allowed[$mime];
            $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
            $dest = $uploadDir . '/' . $fileName;

            $ok = move_uploaded_file($tmp, $dest);
            @file_put_contents($debugFile, "MOVE {$idx}: ok=" . ($ok ? '1' : '0') . " dest={$dest}\n\n", FILE_APPEND);

            if (!$ok) {
                $last = error_get_last();
                @file_put_contents($debugFile, "MOVE FAILED last_error=" . json_encode($last) . "\n\n", FILE_APPEND);
                throw new RuntimeException("Falha ao salvar a imagem no servidor (move_uploaded_file).");
            }

            $paths[] = 'uploads/animais/' . $fileName;
        }

        return $paths;
    }

    public function excluir(): void
    {
        $this->requireAuth();

        // Segurança mínima: não excluir via GET
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método não permitido.');
        }

        $id = (string)($_POST['id'] ?? '');
        if ($id === '') {
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        $usuarioId = (string)($_SESSION['usuario_id'] ?? '');

        // Ajuste conforme seu projeto (ex.: 'admin', 'Administrador', etc.)
        $tipo = (string)($_SESSION['tipo_usuario'] ?? '');
        $isAdmin = (mb_strtolower($tipo) === 'admin' || mb_strtolower($tipo) === 'administrador');

        // Dono da denúncia
        $donoId = $this->animalModel->buscarDonoId($id);

        if ($donoId === null) {
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        // Regra: admin OU dono
        if (!$isAdmin && $donoId !== $usuarioId) {
            http_response_code(403);
            exit('Você não tem permissão para excluir esta denúncia.');
        }

        $this->animalModel->excluir($id);

        $this->redirect('/index.php?c=animal&a=listar');
    }

    public function atualizarStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método não permitido.');
        }

        // Exige login
        if (empty($_SESSION['usuario_id'])) {
            $_SESSION['flash_error'] = 'Você precisa estar logado para alterar o status.';
            $this->redirect('/index.php?c=auth&a=login');
            return;
        }

        $usuarioId    = (string)$_SESSION['usuario_id'];
        $tipoUsuario  = (string)($_SESSION['tipo_usuario'] ?? 'Comum');

        $animalId     = trim((string)($_POST['animal_id'] ?? ''));
        $novoStatus   = trim((string)($_POST['status'] ?? ''));

        if ($animalId === '' || $novoStatus === '') {
            $_SESSION['flash_error'] = 'Dados inválidos para atualizar status.';
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        // Busca status atual
        $animal = $this->animalModel->buscarPorId($animalId);
        if (!$animal) {
            $_SESSION['flash_error'] = 'Denúncia não encontrada.';
            $this->redirect('/index.php?c=animal&a=listar');
            return;
        }

        $statusAtual = (string)($animal['status'] ?? 'Aguardando');

        // Evita gravar histórico duplicado
        if ($novoStatus === $statusAtual) {
            $_SESSION['flash_success'] = 'Nenhuma alteração: o status já estava definido.';
            $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($animalId));
            return;
        }

        // Regra por tipo de usuário (à prova de UI)
        $permitidos = [];
        if ($tipoUsuario === 'ONG') {
            $permitidos = ['Aguardando', 'Em andamento', 'Adoção', 'Resgatado', 'Finalizado'];
        } elseif ($tipoUsuario === 'Autoridade') {
            $permitidos = ['Aguardando', 'Em andamento', 'Adoção', 'Resgatado', 'Finalizado'];
        } elseif ($tipoUsuario === 'Admin') {
            $permitidos = ['Aguardando', 'Em andamento', 'Adoção', 'Resgatado', 'Finalizado'];
        } else {
            // Comum (e qualquer outro)
            $_SESSION['flash_error'] = 'Você não tem permissão para alterar o status.';
            $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($animalId));
            return;
        }

        if (!in_array($novoStatus, $permitidos, true)) {
            $_SESSION['flash_error'] = 'Status inválido para o seu perfil.';
            $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($animalId));
            return;
        }

        // Atualiza status + grava histórico (transação no model)
        $ok = $this->animalModel->atualizarStatusComHistorico(
            $animalId,
            $statusAtual,
            $novoStatus,
            $usuarioId
        );

        if (!$ok) {
            $_SESSION['flash_error'] = 'Falha ao atualizar o status. Tente novamente.';
            $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($animalId));
            return;
        }

        $_SESSION['flash_success'] = 'Status atualizado com sucesso.';
        $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($animalId));
    }

}
