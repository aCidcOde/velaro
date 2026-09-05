<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * /dashboard virou o "hub" do login único (tela 0): cada perfil vai para o seu
 * ambiente. Master -> /backend, revendedor aprovado -> /portal. Quem tem conta
 * mas não é nenhum dos dois (pré-cadastro sem aprovação, usuário sem vínculo)
 * cai no acompanhamento da solicitação ou na home do site.
 */
class PainelRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (Gate::forUser($user)->allows('access-backend')) {
            return redirect()->route('backend.dashboard');
        }

        $reseller = $user->reseller;

        if ($reseller?->status === Reseller::STATUS_APPROVED) {
            return redirect()->route('portal.dashboard');
        }

        if ($reseller?->protocol) {
            return redirect()->route('site.solicitacao.status', $reseller);
        }

        return redirect()->route('site.home')
            ->with('status', 'Sua conta ainda não está vinculada a um revendedor aprovado.');
    }
}
