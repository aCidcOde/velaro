{{--
[Modulo: resources/views/components/velaro/solicitacao]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Checklist das cinco verificacoes da triagem automatica com IA, compartilhado pela tela 1.6 e pelo painel.
--}}
@props(['checks'])
<div class="split" style="--gcols:180px minmax(0,1fr);gap:var(--space-6);align-items:center">
    <div style="display:grid;place-items:center;gap:10px;text-align:center">
        <x-velaro.icon name="brain" style="width:44px;height:44px;color:var(--color-gold-600)" />
        <strong style="font-size:var(--text-sm);color:var(--ink)">Validação<br>automática com IA</strong>
    </div>
    <div>
        <ul class="cklist">
            @foreach ($checks as $check)
                <li @class(['ck--ok' => $check['state'] === 'ok', 'ck--wait' => $check['state'] === 'wait'])>
                    <x-velaro.icon :name="$check['icon']" />
                    <span>{{ $check['label'] }}</span>
                    <b>{{ $check['note'] }}</b>
                </li>
            @endforeach
        </ul>
    </div>
</div>
