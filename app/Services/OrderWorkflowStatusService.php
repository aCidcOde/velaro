<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Validation\ValidationException;

class OrderWorkflowStatusService
{
    /** @var list<string> */
    public const STATUSES = [
        'draft',
        'awaiting_payment',
        'paid',
        'in_progress',
        'completed',
        'canceled',
        'error',
    ];

    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'draft' => ['awaiting_payment', 'canceled'],
        'awaiting_payment' => ['paid', 'canceled'],
        'paid' => ['in_progress', 'error'],
        'in_progress' => ['completed', 'error'],
        'completed' => [],
        'canceled' => [],
        'error' => [],
    ];

    public function isValid(string $status): bool
    {
        return in_array($status, self::STATUSES, true);
    }

    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        return in_array($toStatus, self::TRANSITIONS[$fromStatus] ?? [], true);
    }

    public function assertTransition(string $fromStatus, string $toStatus): void
    {
        if (! $this->canTransition($fromStatus, $toStatus)) {
            throw ValidationException::withMessages([
                'status' => "Transição inválida: {$fromStatus} → {$toStatus}.",
            ]);
        }
    }

    public function transition(Order $order, string $toStatus): void
    {
        $this->assertTransition($order->status, $toStatus);
        $order->update(['status' => $toStatus]);
    }
}
