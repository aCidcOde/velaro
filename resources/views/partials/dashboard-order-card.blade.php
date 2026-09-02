@php
    $dn = $order->public_number ?: str_pad((string) $order->id, 8, '0', STR_PAD_LEFT);
    $cc = ! in_array($order->status, ['paid', 'in_progress', 'completed', 'canceled'], true);
    $isAwaiting = $order->status === 'awaiting_payment';
    $canDelete = in_array($order->status, ['draft', 'awaiting_payment'], true);
@endphp
<div class="px-3 py-3 border-bottom d-flex flex-column gap-2">
    <h2 class="mb-0 fw-bold" style="font-size:20px">
        <a href="{{ $cc ? route('orders.continue', $order) : route('orders.show', $order) }}" class="text-decoration-none">Pedido #{{ $dn }}</a>
    </h2>
    <div><x-order-status-badge :status="$order->status" /></div>
    <div class="d-flex gap-3 text-muted" style="font-size:18px">
        <span class="fw-semibold">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</span>
        <span>{{ $order->created_at->format('d/m/Y H:i') }}</span>
    </div>
    <div class="d-flex gap-2 pt-1">
        @if ($cc)
            <a href="{{ route('orders.continue', $order) }}" class="btn btn-sm btn-primary flex-fill">
                <i class="ti ti-player-play me-1"></i> Continuar
            </a>
        @endif
        @if (! $cc)
            <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-secondary flex-fill">
                <i class="ti ti-eye me-1"></i> Detalhes
            </a>
        @endif
        @if ($isAwaiting)
            <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Cancelar pedido?');">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="ti ti-ban"></i></button>
            </form>
        @endif
        @if ($canDelete)
            <form method="POST" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('Excluir pedido?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="ti ti-trash"></i></button>
            </form>
        @endif
    </div>
</div>
