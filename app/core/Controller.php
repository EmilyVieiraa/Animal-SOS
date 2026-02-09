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
    /**protected function view(string $view, array $data = []): void
    {
        // Transforma as chaves do array em variáveis na view
        // Ex.: $data['erro'] vira $erro
        extract($data);

        // 1. Cabeçalho (HTML inicial + menu)
        require APP_PATH . 'views/layouts/header.php';

        // 2. Conteúdo da página (home, listar, detalhes, etc.)
        require APP_PATH . 'views/' . $view . '.php';

        // 3. Rodapé (o mesmo da home)
        require APP_PATH . 'views/layouts/footer.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo "View não encontrada: {$view}";
            exit;
        }

        // Layout padrão
        $header = APP_PATH . 'views/layouts/header.php';
        $footer = APP_PATH . 'views/layouts/footer.php';

        if (file_exists($header)) require $header;
        require $viewFile;
        if (file_exists($footer)) require $footer;
    }*/

    protected function view(string $view, array $data = []): void
    {
        extract($data);

        $viewFile = APP_PATH . 'views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo "View não encontrada: {$view}";
            exit;
        }

        require APP_PATH . 'views/layouts/header.php';
        require $viewFile;
        require APP_PATH . 'views/layouts/footer.php';
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
