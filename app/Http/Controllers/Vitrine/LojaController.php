<?php

/*
[Modulo: app/Http/Controllers/Vitrine]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Esqueleto do Vitrine white label: rota mapeada, tela pendente de implementacao.
*/

namespace App\Http\Controllers\Vitrine;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LojaController extends Controller
{
    public function carrinho(): never
    {
        // Esqueleto: a rota existe e esta no mapa, a tela ainda nao foi construida.
        // O ambiente Site publico foi implementado primeiro; este entra na sequencia.
        throw new HttpException(501, 'Tela ainda nao implementada: vitrine.loja.carrinho');
    }

    public function confirmado(): never
    {
        // Esqueleto: a rota existe e esta no mapa, a tela ainda nao foi construida.
        // O ambiente Site publico foi implementado primeiro; este entra na sequencia.
        throw new HttpException(501, 'Tela ainda nao implementada: vitrine.loja.confirmado');
    }

    public function finalizar(): never
    {
        // Esqueleto: a rota existe e esta no mapa, a tela ainda nao foi construida.
        // O ambiente Site publico foi implementado primeiro; este entra na sequencia.
        throw new HttpException(501, 'Tela ainda nao implementada: vitrine.loja.finalizar');
    }

    public function index(): never
    {
        // Esqueleto: a rota existe e esta no mapa, a tela ainda nao foi construida.
        // O ambiente Site publico foi implementado primeiro; este entra na sequencia.
        throw new HttpException(501, 'Tela ainda nao implementada: vitrine.loja.index');
    }

    public function produto(): never
    {
        // Esqueleto: a rota existe e esta no mapa, a tela ainda nao foi construida.
        // O ambiente Site publico foi implementado primeiro; este entra na sequencia.
        throw new HttpException(501, 'Tela ainda nao implementada: vitrine.loja.produto');
    }
}
