<?php
declare(strict_types=1);

final class EstatisticasController extends Controller
{
    private Estatisticas $estatisticasModel;

    public function __construct()
    {
        $this->estatisticasModel = new Estatisticas();
    }

    public function index(): void
    {
        $this->requireAuth();

        $dados = $this->estatisticasModel->gerais();

        $this->view('estatisticas/index', [
            'dados' => $dados,
        ]);
    }
}
