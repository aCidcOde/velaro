<x-layouts.app title="Dashboard">
    @php
        $metricCards = [
            [
                'label' => 'Clientes',
                'value' => $stats['customers'],
                'icon' => 'ti ti-users',
                'trend' => $stats['customers'] > 0 ? 'Base ativa' : 'Sem base',
                'trend_type' => $stats['customers'] > 0 ? 'up' : 'down',
            ],
            [
                'label' => 'Pedidos',
                'value' => $stats['orders'],
                'icon' => 'ti ti-package',
                'trend' => $stats['completed_orders'].' concluídos',
                'trend_type' => $stats['completed_orders'] > 0 ? 'up' : 'down',
            ],
        ];

        $maxStatusValue = max($statusChart->max('value') ?? 0, 1);
        $activityPoints = collect($activityChart)->values()->map(function (array $point, int $index) use ($activityChart): array {
            $maxValue = max(collect($activityChart)->max('value') ?? 0, 1);
            $x = 40 + ($index * 110);
            $y = 220 - (($point['value'] / $maxValue) * 160);

            return [
                'x' => $x,
                'y' => round($y, 2),
                'label' => $point['label'],
                'value' => $point['value'],
            ];
        });

        $linePath = $activityPoints->map(fn (array $point): string => "{$point['x']},{$point['y']}")->implode(' ');
        $areaPath = collect(['M 40 220', 'L '.$activityPoints->map(fn (array $point): string => "{$point['x']} {$point['y']}")->implode(' L '), 'L 700 220 Z'])->implode(' ');
    @endphp

    @if (session('status'))
        <div class="panel-flash panel-flash--success">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-12 gap-4 md:gap-6">
        <div class="col-span-12 space-y-6 xl:col-span-7">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6">
                @foreach ($metricCards as $card)
                    <article class="panel-stat-card">
                        <div class="panel-stat-card__icon">
                            <i class="{{ $card['icon'] }}"></i>
                        </div>

                        <div class="mt-5 flex items-end justify-between gap-4">
                            <div>
                                <span class="panel-stat-card__label">{{ $card['label'] }}</span>
                                <h2 class="panel-stat-card__value">{{ number_format($card['value'], 0, ',', '.') }}</h2>
                            </div>

                            <span class="panel-stat-card__trend {{ $card['trend_type'] === 'up' ? 'panel-stat-card__trend--up' : 'panel-stat-card__trend--down' }}">
                                <i class="ti {{ $card['trend_type'] === 'up' ? 'ti-arrow-up-right' : 'ti-arrow-down-right' }}"></i>
                                {{ $card['trend'] }}
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>

            <section class="panel-card">
                <div class="panel-card__header">
                    <div>
                        <h2 class="panel-card__title">Pipeline por status</h2>
                        <p class="panel-card__subtitle">Leitura rápida do fluxo atual de pedidos da conta.</p>
                    </div>

                    <button type="button" class="panel-card__menu" aria-label="Mais opções">
                        <i class="ti ti-dots-vertical text-xl"></i>
                    </button>
                </div>

                <div class="panel-bars custom-scrollbar">
                    @foreach ($statusChart as $bar)
                        @php
                            $barHeight = $bar['value'] > 0 ? max(18, round(($bar['value'] / $maxStatusValue) * 100)) : 10;
                        @endphp
                        <div class="panel-bars__item">
                            <div class="panel-bars__bar" style="--panel-bar-size: {{ $barHeight }}%"></div>
                            <span class="panel-bars__label">{{ $bar['short'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="col-span-12 xl:col-span-5">
            <section class="panel-card panel-card--soft !overflow-hidden !p-0">
                <div class="panel-card bg-white px-5 pb-10 pt-5 dark:bg-gray-900 sm:px-6 sm:pt-6">
                    <div class="panel-card__header">
                        <div>
                            <h2 class="panel-card__title">Monthly Target</h2>
                            <p class="panel-card__subtitle">Meta de fechamento da operação atual.</p>
                        </div>

                        <button type="button" class="panel-card__menu" aria-label="Mais opções">
                            <i class="ti ti-dots-vertical text-xl"></i>
                        </button>
                    </div>

                    <div class="panel-ring" style="--panel-progress: {{ min($completionRate, 100) }}%">
                        <div class="panel-ring__center">
                            <div class="panel-ring__value">{{ number_format($completionRate, 2, ',', '.') }}%</div>
                            <span class="panel-badge panel-badge--success mt-4">Conclusão</span>
                        </div>
                    </div>

                    <p class="panel-ring__text">
                        Base pronta para criar novos sistemas com visibilidade de clientes, produtos e pedidos sem trocar de fluxo.
                    </p>
                </div>

                <div class="panel-kpi-row">
                    <div class="panel-kpi">
                        <p class="panel-kpi__label">Meta</p>
                        <p class="panel-kpi__value">{{ number_format($stats['orders'], 0, ',', '.') }}</p>
                    </div>

                    <div class="panel-kpi-divider"></div>

                    <div class="panel-kpi">
                        <p class="panel-kpi__label">Produtos</p>
                        <p class="panel-kpi__value">{{ number_format($stats['products'], 0, ',', '.') }}</p>
                    </div>

                    <div class="panel-kpi-divider"></div>

                    <div class="panel-kpi">
                        <p class="panel-kpi__label">Hoje</p>
                        <p class="panel-kpi__value">{{ number_format($stats['completed_orders'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-span-12">
            <section class="panel-card">
                <div class="panel-card__header">
                    <div>
                        <h2 class="panel-card__title">Statistics</h2>
                        <p class="panel-card__subtitle">Leitura da atividade recente da conta nos últimos sete dias.</p>
                    </div>

                    <div class="panel-line-card__toolbar">
                        <div class="panel-tab-group">
                            <button type="button" class="panel-tab is-active">Overview</button>
                            <button type="button" class="panel-tab">Sales</button>
                            <button type="button" class="panel-tab">Revenue</button>
                        </div>

                        <span class="panel-date-chip">
                            <i class="ti ti-calendar text-base"></i>
                            <span>{{ now()->subDays(6)->format('d M') }} - {{ now()->format('d M') }}</span>
                        </span>
                    </div>
                </div>

                <div class="panel-line-chart custom-scrollbar">
                    <div class="panel-line-chart__frame">
                        <svg viewBox="0 0 740 250" class="h-[260px] w-full">
                            @foreach ([60, 113, 166, 220] as $gridLine)
                                <line x1="40" y1="{{ $gridLine }}" x2="700" y2="{{ $gridLine }}" class="panel-line-chart__grid" />
                            @endforeach

                            <path d="{{ $areaPath }}" class="panel-line-chart__area"></path>
                            <polyline points="{{ $linePath }}" class="panel-line-chart__line"></polyline>

                            @foreach ($activityPoints as $point)
                                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5" class="panel-line-chart__point"></circle>
                            @endforeach
                        </svg>
                    </div>
                </div>

                <div class="panel-line-chart__labels">
                    @foreach ($activityChart as $point)
                        <span>{{ $point['label'] }}</span>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="col-span-12">
            <section class="panel-card">
                <div class="panel-card__header">
                    <div>
                        <h2 class="panel-card__title">Pedidos recentes</h2>
                        <p class="panel-card__subtitle">Leitura executiva do que entrou por último no fluxo.</p>
                    </div>

                    <a href="{{ route('orders.index') }}" class="panel-btn panel-btn--secondary">Ver todos</a>
                </div>

                <div class="panel-table-shell">
                    <table class="panel-table">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Itens</th>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr>
                                    <td>
                                        <div class="panel-table__title">
                                            <a href="{{ route('orders.show', $order) }}">{{ $order->public_number }}</a>
                                        </div>
                                        <div class="panel-table__meta">{{ $order->reference ?: 'Sem referência' }}</div>
                                    </td>
                                    <td>{{ $order->customer?->name ?? 'Sem cliente' }}</td>
                                    <td>{{ $order->items->count() }}</td>
                                    <td>
                                        <span class="panel-badge panel-badge--brand">{{ ucfirst($order->status) }}</span>
                                    </td>
                                    <td>R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="panel-empty">Nenhum pedido recente.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
