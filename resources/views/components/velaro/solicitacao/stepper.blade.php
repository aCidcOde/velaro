{{--
[Modulo: resources/views/components/velaro/solicitacao]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Stepper das quatro etapas da habilitacao, compartilhado pelas telas 1.5, 1.6 e pelo painel do lojista.
--}}
@props([
    'steps',
    'rotulos' => [
        'received' => 'Cadastro recebido',
        'verification' => 'Validação automática',
        'approval' => 'Aprovação final Velaro',
        'access' => 'Acesso liberado',
    ],
])
<ol class="stepper">
    @foreach ($steps as $step)
        <li class="step step--{{ $step['state'] }}">
            <span class="step__dot">{{ $step['dot'] }}</span>
            <span class="step__lab">{{ $rotulos[$step['key']] ?? $step['key'] }}</span>
            <span class="step__note">{{ $step['note'] }}</span>
        </li>
    @endforeach
</ol>
