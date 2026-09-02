@props(['status'])

@php
    $normalized = is_string($status) ? strtolower(trim($status)) : '';
    $normalized = str_replace(['-', ' '], '_', $normalized);
    $baseStatus = match ($normalized) {
        'waiting_payment',
        'waiting_payments',
        'awaiting_payment',
        'awaiting_payments',
        'pending_payment',
        'payment_pending' => 'awaiting_payment',
        'paid',
        'approved' => 'paid',
        'in_progress',
        'processing' => 'in_progress',
        'completed',
        'done',
        'finished' => 'completed',
        'canceled',
        'cancelled',
        'canceled_by_user',
        'cancelled_by_user' => 'canceled',
        'error',
        'failed',
        'failure' => 'error',
        default => $normalized,
    };

    $styles = [
        'draft' => [
            'label' => 'Rascunho',
            'icon' => 'ti ti-pencil',
            'classes' => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-900/40 dark:text-slate-200 dark:ring-slate-700',
        ],
        'awaiting_payment' => [
            'label' => 'Aguardando pagamento',
            'icon' => 'ti ti-clock',
            'classes' => 'bg-amber-100 text-amber-800 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:ring-amber-700',
        ],
        'paid' => [
            'label' => 'Pago',
            'icon' => 'ti ti-circle-check',
            'classes' => 'bg-emerald-100 text-emerald-800 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:ring-emerald-700',
        ],
        'in_progress' => [
            'label' => 'Em andamento',
            'icon' => 'ti ti-loader-2',
            'classes' => 'bg-sky-100 text-sky-800 ring-sky-200 dark:bg-sky-900/30 dark:text-sky-200 dark:ring-sky-700',
        ],
        'completed' => [
            'label' => 'Concluído',
            'icon' => 'ti ti-check',
            'classes' => 'bg-teal-100 text-teal-800 ring-teal-200 dark:bg-teal-900/30 dark:text-teal-200 dark:ring-teal-700',
        ],
        'canceled' => [
            'label' => 'Cancelado',
            'icon' => 'ti ti-circle-x',
            'classes' => 'bg-rose-100 text-rose-800 ring-rose-200 dark:bg-rose-900/30 dark:text-rose-200 dark:ring-rose-700',
        ],
        'error' => [
            'label' => 'Erro',
            'icon' => 'ti ti-alert-triangle',
            'classes' => 'bg-rose-100 text-rose-800 ring-rose-200 dark:bg-rose-900/30 dark:text-rose-200 dark:ring-rose-700',
        ],
    ];

    $fallbackLabel = $baseStatus !== '' ? ucfirst(str_replace('_', ' ', $baseStatus)) : '—';
    $statusConfig = $styles[$baseStatus] ?? [
        'label' => $fallbackLabel,
        'icon' => 'ti ti-circle-dashed',
        'classes' => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-900/40 dark:text-slate-200 dark:ring-slate-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {$statusConfig['classes']}"]) }}>
    <i class="{{ $statusConfig['icon'] }}" aria-hidden="true"></i>
    <span>{{ $statusConfig['label'] }}</span>
</span>
