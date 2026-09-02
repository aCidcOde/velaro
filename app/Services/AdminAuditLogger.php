<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AdminAuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function log(string $action, ?Model $target = null, ?array $before = null, ?array $after = null): ?AuditLog
    {
        $actor = auth()->user();

        if (! $actor || ! $actor->is_admin) {
            return null;
        }

        $payload = [
            'actor_id' => $actor->id,
            'action' => $action,
            'before' => $this->maskSensitive($before),
            'after' => $this->maskSensitive($after),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ];

        if ($target) {
            $payload['target_type'] = $target::class;
            $payload['target_id'] = $target->getKey();
        }

        return AuditLog::create($payload);
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @return array<string, mixed>|null
     */
    protected function maskSensitive(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        return $this->maskRecursive($data, [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    protected function maskRecursive(array $data, array $keys): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $keys, true)) {
                $data[$key] = '***';

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->maskRecursive($value, $keys);
            }
        }

        return $data;
    }
}
