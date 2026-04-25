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
     * Ex.: $this->view('auth/login', ['erro' => '...']);
     */
    protected function view(string $view, array $data = []): void
    {
        $dadosRenderizacao = $this->normalizarDadosRenderizacao($data);
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

        return $dados;
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
