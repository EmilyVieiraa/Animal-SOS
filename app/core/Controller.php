<?php
declare(strict_types=1);

/**
 * Controller Base
 * - Renderiza views com layout
 * - Redireciona
 * - Protege rotas (exigir login)
 */

abstract class Controller
{
    /**
     * Renderiza uma view dentro do layout padrão.
      * Ex.: $this->view('paginas/home', ['title' => 'Animal SOS']);
     */
    protected function view(string $view, array $data = []): void
    {
        $dadosRenderizacao = $this->normalizarDadosRenderizacao($data);
        $dadosRenderizacao['contratoLayoutGlobal'] = $this->montarContratoLayoutGlobal($dadosRenderizacao);

        $arquivoView = APP_PATH . 'views/' . $view . '.php';
        $arquivoCabecalho = APP_PATH . 'views/layouts/header.php';
        $arquivoRodape = APP_PATH . 'views/layouts/footer.php';

        if (!file_exists($arquivoView)) {
            http_response_code(404);
            echo "View não encontrada: {$view}";
            exit;
        }

        if (!file_exists($arquivoCabecalho) || !file_exists($arquivoRodape)) {
            http_response_code(500);
            echo 'Layout global não encontrado.';
            exit;
        }

        // Mantém o contrato legado com views existentes, reduzindo colisões de variáveis locais.
        extract($dadosRenderizacao, EXTR_SKIP);

        require $arquivoCabecalho;
        require $arquivoView;
        require $arquivoRodape;
    }

    /**
     * Contrato global de renderização (compatível):
     * - Mantém suporte simultâneo a `tituloPagina` e `title`.
     * - Quando apenas um for enviado, espelha para o outro.
     */
    private function normalizarDadosRenderizacao(array $dados): array
    {
        $tituloNormalizado = (string)($dados['tituloPagina'] ?? $dados['title'] ?? 'Animal SOS');

        $dados['tituloPagina'] = $tituloNormalizado;
        $dados['title'] = $tituloNormalizado;

        // Contrato de modal e mensagens globais da home (legado compatível).
        $dados['usaModalAutenticacao'] = (bool)($dados['usaModalAutenticacao'] ?? false);
        $dados['modalAbertoAtual'] = $this->normalizarTextoRenderizacao($dados['modalAbertoAtual'] ?? null);

        $dados['mensagemSucessoHome'] = $this->normalizarTextoRenderizacao($dados['mensagemSucessoHome'] ?? null);
        $dados['mensagemErroLogin'] = $this->normalizarTextoRenderizacao($dados['mensagemErroLogin'] ?? null);
        $dados['mensagemErroCadastro'] = $this->normalizarTextoRenderizacao($dados['mensagemErroCadastro'] ?? null);
        $dados['mensagemSucessoCadastro'] = $this->normalizarTextoRenderizacao($dados['mensagemSucessoCadastro'] ?? null);

        return $dados;
    }

    /**
     * Normaliza qualquer valor de renderização textual para string segura.
     */
    private function normalizarTextoRenderizacao(mixed $valor): string
    {
        return is_string($valor) ? $valor : '';
    }

    /**
     * Contrato global de layout compartilhado entre header/footer e views.
     *
     * Mantém URLs no padrão legado (query string) para preservar compatibilidade.
     */
    private function montarContratoLayoutGlobal(array $dadosRenderizacao): array
    {
        $tituloDocumento = (string)($dadosRenderizacao['tituloPagina'] ?? 'Animal SOS');

        return [
            'versao' => 1,
            'tituloDocumento' => $tituloDocumento,
            'navegacao' => $this->obterUrlsNavegacaoGlobais(),
            'modal' => [
                'usaModalAutenticacao' => (bool)($dadosRenderizacao['usaModalAutenticacao'] ?? false),
                'modalAbertoAtual' => $this->normalizarTextoRenderizacao($dadosRenderizacao['modalAbertoAtual'] ?? ''),
            ],
            'sessao' => [
                'usuarioLogado' => !empty($_SESSION['usuario_id']),
            ],
        ];
    }

    /**
     * URLs globais de navegação usadas pelos layouts.
     * Mantém padrão legado por querystring para compatibilidade.
     */
    private function obterUrlsNavegacaoGlobais(): array
    {
        return [
            'urlHome' => BASE_URL . '/index.php?c=paginas&a=home',
            'urlAnimaisReportados' => BASE_URL . '/index.php?c=animal&a=listar',
            'urlReportarAnimal' => BASE_URL . '/index.php?c=animal&a=reportar',
            'urlMeuPerfil' => BASE_URL . '/index.php?c=usuario&a=meuPerfil',
            'urlLogout' => BASE_URL . '/index.php?c=auth&a=logout',
            'urlLoginHome' => BASE_URL . '/index.php?c=paginas&a=home#login',
            'urlCadastroHome' => BASE_URL . '/index.php?c=paginas&a=home#cadastro',
        ];
    }

    /**
     * Redireciona para uma rota (path relativo a BASE_URL).
     * Ex.: $this->redirect('/index.php?c=paginas&a=home');
     */
    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . $path);
        exit;
    }

    /**
     * Verifica se o usuário está logado.
     */
    protected function isLogged(): bool
    {
        return !empty($_SESSION['usuario_id']);
    }

    /**
     * Exige autenticação para acessar a ação.
     * Se não estiver logado, redireciona para login.
     */
    protected function requireAuth(): void
    {
        if (!$this->isLogged()) {
            $this->redirect('/index.php?c=auth&a=login');
        }
    }
}
