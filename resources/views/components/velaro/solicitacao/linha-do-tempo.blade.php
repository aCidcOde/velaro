{{--
[Modulo: resources/views/components/velaro/solicitacao]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Linha do tempo dos eventos de status da solicitacao, compartilhada pela tela 1.6 e pelo painel do lojista.
--}}
@props(['timeline'])
<ul class="timeline">
    @forelse ($timeline as $evento)
        <li class="tl tl--{{ $evento['state'] }}">
            <span class="tl__dot"></span>
            <span class="tl__body">
                <strong>{{ $evento['label'] }}</strong>
                @if ($evento['note'])<span class="tl__desc">{{ $evento['note'] }}</span>@endif
            </span>
            <span class="tl__when">{{ $evento['when'] }}</span>
        </li>
    @empty
        <li class="tl tl--todo">
            <span class="tl__dot"></span>
            <span class="tl__body"><strong>Sem movimentações registradas.</strong></span>
            <span class="tl__when">—</span>
        </li>
    @endforelse
</ul>
