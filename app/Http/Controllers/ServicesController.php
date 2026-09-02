<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ServicesController extends Controller
{
    public function __invoke(): View
    {
        return view('services', [
            'modules' => [
                [
                    'title' => 'Autenticação e ACL',
                    'description' => 'Base com login, verificação, Google auth, 2FA e permissões administrativas.',
                ],
                [
                    'title' => 'Clientes, produtos e pedidos',
                    'description' => 'Fluxo mínimo para operar o front do cliente e o painel administrativo.',
                ],
                [
                    'title' => 'Jobs, rotinas e deploy',
                    'description' => 'Fila, scheduler, guidelines e pipeline reaproveitáveis para novos projetos.',
                ],
            ],
        ]);
    }
}
