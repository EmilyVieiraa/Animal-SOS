<?php
declare(strict_types=1);

final class ComentarioController extends Controller
{
    private Comentario $comentarioModel;

    public function __construct()
    {
        $this->comentarioModel = new Comentario();
    }

    public function adicionar(): void
    {
        // precisa estar logado
        if (empty($_SESSION['usuario_id'])) {
            $this->redirect('/index.php?c=auth&a=login');
            return;
        }

        $usuarioId = (string)$_SESSION['usuario_id'];
        $animalId  = trim((string)($_POST['animal_id'] ?? ''));
        $mensagem  = trim((string)($_POST['mensagem'] ?? ''));

        if ($animalId === '' || $mensagem === '') {
            $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($animalId));
            return;
        }

        $ok = $this->comentarioModel->criar([
            'animal_id'  => $animalId,
            'usuario_id' => $usuarioId,
            'mensagem'   => $mensagem,
        ]);

        $this->redirect('/index.php?c=animal&a=detalhes&id=' . urlencode($animalId));
    }

}
