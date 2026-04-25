<?php
declare(strict_types=1);

bootstrapAplicacao();

$path = obterPathRequisicaoNormalizado();
$destino = resolverDestinoRota($path);

if ($destino === null) {
    encerrarComErroHttp(404, 'Rota não encontrada.');
}

injetarParametrosRotaEmGet($destino['params']);
despacharControllerAcao($destino['controller'], $destino['action']);

/**
 * Carrega configurações base, banco e autoload do Composer.
 *
 * Contrato atual:
 * - config.php define constantes globais, sessão e autoload local.
 * - database.php depende de constantes já definidas em config.php.
 */
function bootstrapAplicacao(): void
{
    require_once __DIR__ . '/../app/config/config.php';
    require_once __DIR__ . '/../app/config/database.php';
    require_once __DIR__ . '/../vendor/autoload.php';
}

/**
 * Extrai e normaliza o path da requisição atual, removendo o prefixo de instalação.
 */
function obterPathRequisicaoNormalizado(): string
{
    $caminhoBase = APP_BASE_PATH;
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    if ($caminhoBase !== '' && str_starts_with($uriPath, $caminhoBase)) {
        $uriPath = substr($uriPath, strlen($caminhoBase));
        if ($uriPath === '') {
            $uriPath = '/';
        }
    }

    $path = '/' . trim($uriPath, '/');

    return $path === '//' ? '/' : $path;
}

/**
 * Catálogo único de rotas amigáveis fixas.
 *
 * Observação:
 * - Mantém aliases legados para compatibilidade controlada.
 */
function catalogoRotasAmigaveis(): array
{
    return [
        '/' => ['PaginasController', 'home'],
        '/login' => ['AuthController', 'login'],
        '/logout' => ['AuthController', 'logout'],
        '/esqueci' => ['AuthController', 'esqueciSenha'],
        '/redefinir-senha' => ['AuthController', 'redefinirSenha'],
        '/salvar-nova-senha' => ['AuthController', 'salvarNovaSenha'],

        // [LEGADO] Alias mantido por compatibilidade com links externos.
        '/home' => ['PaginasController', 'home'],

        '/perfil' => ['UsuarioController', 'meuPerfil'],

        // [LEGADO] Alias mantido por compatibilidade com links/e-mails antigos.
        '/senha' => ['AuthController', 'esqueciSenha'],

        '/denuncias' => ['AnimalController', 'listar'],
        '/reportar' => ['AnimalController', 'reportar'],

        '/comentarios' => ['ComentarioController', 'adicionar'],
    ];
}

/**
 * Resolve controller/action/params a partir da rota atual.
 *
 * Ordem de resolução preservada:
 * 1) rota amigável fixa
 * 2) rota amigável dinâmica
 * 3) fallback legado c/a
 */
function resolverDestinoRota(string $path): ?array
{
    $rotasFixas = catalogoRotasAmigaveis();

    if (isset($rotasFixas[$path])) {
        [$controllerClass, $action] = $rotasFixas[$path];

        return [
            'controller' => $controllerClass,
            'action' => $action,
            'params' => [],
            'origem' => 'amigavel_fixa',
        ];
    }

    $destinoDinamico = resolverRotaAmigavelDinamica($path);
    if ($destinoDinamico !== null) {
        return $destinoDinamico;
    }

    return resolverFallbackLegadoCA();
}

/**
 * Regras de rotas amigáveis dinâmicas.
 */
function resolverRotaAmigavelDinamica(string $path): ?array
{
    $segments = array_values(array_filter(explode('/', trim($path, '/'))));

    // /denuncias/{id}
    if (count($segments) === 2 && $segments[0] === 'denuncias') {
        return [
            'controller' => 'AnimalController',
            'action' => 'detalhes',
            'params' => ['id' => $segments[1]],
            'origem' => 'amigavel_dinamica',
        ];
    }

    return null;
}

/**
 * [LEGADO] Resolve rota por query-string (?c=...&a=...).
 *
 * Mantido por compatibilidade enquanto houver dependência real nos módulos.
 */
function resolverFallbackLegadoCA(): ?array
{
    $controllerBruto = $_GET['c'] ?? null;
    $acaoBruta = $_GET['a'] ?? null;

    if (!$controllerBruto || !$acaoBruta) {
        return null;
    }

    $controllerNormalizado = normalizarNomeControllerLegado((string)$controllerBruto);
    $acaoNormalizada = normalizarNomeAcaoLegada((string)$acaoBruta);

    if ($controllerNormalizado === null || $acaoNormalizada === null) {
        return null;
    }

    return [
        'controller' => $controllerNormalizado,
        'action' => $acaoNormalizada,
        'params' => [],
        'origem' => 'fallback_legado_ca',
    ];
}

/**
 * Normaliza o nome do controller vindo do fallback legado c/a.
 *
 * Mantém o contrato histórico de transformar em PascalCase + sufixo Controller.
 */
function normalizarNomeControllerLegado(string $controllerBruto): ?string
{
    $controllerBruto = trim($controllerBruto);
    if ($controllerBruto === '') {
        return null;
    }

    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $controllerBruto)) {
        return null;
    }

    return ucfirst(strtolower($controllerBruto)) . 'Controller';
}

/**
 * Normaliza o nome da action vinda do fallback legado c/a.
 */
function normalizarNomeAcaoLegada(string $acaoBruta): ?string
{
    $acaoBruta = trim($acaoBruta);
    if ($acaoBruta === '') {
        return null;
    }

    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $acaoBruta)) {
        return null;
    }

    return $acaoBruta;
}

/**
 * Injeta parâmetros de rota amigável no $_GET para preservar contrato legado dos controllers.
 */
function injetarParametrosRotaEmGet(array $params): void
{
    foreach ($params as $chave => $valor) {
        $_GET[$chave] = $valor;
    }
}

/**
 * Carrega o controller e executa a action resolvida.
 */
function despacharControllerAcao(string $controllerClass, string $action): void
{
    $controllerFile = APP_PATH . 'controllers/' . $controllerClass . '.php';

    if (!file_exists($controllerFile)) {
        encerrarComErroHttp(404, 'Controller não encontrado.');
    }

    require_once $controllerFile;

    if (!class_exists($controllerClass)) {
        encerrarComErroHttp(500, 'Classe do controller inválida.');
    }

    $controller = new $controllerClass();

    if (!method_exists($controller, $action)) {
        encerrarComErroHttp(404, 'Ação não encontrada.');
    }

    $controller->{$action}();
}

/**
 * Resposta HTTP de erro padronizada para a camada de entrada.
 */
function encerrarComErroHttp(int $statusCode, string $mensagem): void
{
    http_response_code($statusCode);
    echo $mensagem;
    exit;
}