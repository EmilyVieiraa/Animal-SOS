<?php
declare(strict_types=1);

final class PaginasController extends Controller
{
    public function home(): void
    {
        $this->view('paginas/home', [
            'title' => 'Animal SOS'
        ]);
    }

    /**
     * PÚBLICO
     * Lista todas as denúncias
     */
    public function animais(): void
    {
        require_once APP_PATH . 'models/Denuncia.php';

        $denunciaModel = new Denuncia();
        $denuncias = $denunciaModel->listarTodas();

        $logado = !empty($_SESSION['user']);

        $this->view('paginas/animais', [
            'denuncias' => $denuncias,
            'logado'    => $logado,
            'title'     => 'Denúncias'
        ]);
    }

    /**
     * PÚBLICO
     * Detalhe de uma denúncia específica
     */
    public function detalheDenuncia(): void
    {
        require_once APP_PATH . 'models/Denuncia.php';
        require_once APP_PATH . 'models/Comentario.php';

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . BASE_URL . '/index.php?c=paginas&a=animais');
            exit;
        }

        $denunciaModel = new Denuncia();
        $denuncia = $denunciaModel->buscarPorId($id);

        if (!$denuncia) {
            header('Location: ' . BASE_URL . '/index.php?c=paginas&a=animais');
            exit;
        }

        $comentModel = new Comentario();
        $comentarios = $comentModel->listarPorDenuncia($id);

        $logado = !empty($_SESSION['user']);

        require APP_PATH . 'views/paginas/denuncia_detalhe.php';
    }
}
