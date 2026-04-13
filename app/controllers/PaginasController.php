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

}
