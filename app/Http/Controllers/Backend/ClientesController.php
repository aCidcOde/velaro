<?php

/*
[Modulo: app/Http/Controllers/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Esqueleto do Painel Master: rota mapeada, tela pendente de implementacao.
*/

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ClientesController extends Controller
{
    public function index(): never
    {
        // Esqueleto: a rota existe e esta no mapa, a tela ainda nao foi construida.
        // O ambiente Site publico foi implementado primeiro; este entra na sequencia.
        throw new HttpException(501, 'Tela ainda nao implementada: backend.clientes.index');
    }

    public function show(): never
    {
        // Esqueleto: a rota existe e esta no mapa, a tela ainda nao foi construida.
        // O ambiente Site publico foi implementado primeiro; este entra na sequencia.
        throw new HttpException(501, 'Tela ainda nao implementada: backend.clientes.show');
    }
}
