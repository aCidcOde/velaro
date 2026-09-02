<?php

namespace App\Models;

use App\Notifications\PasswordResetRequested;
use App\Services\Acl\BackendAclService;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    public const BLOCKED_MESSAGE = 'Conta bloqueada. Fale com o suporte.';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'document',
        'password',
        'google_id',
        'theme_preference',
        'is_admin',
        'is_agent',
        'is_blocked',
        'api_token_hash',
        'api_token_expires_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'api_token_hash',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_agent' => 'boolean',
            'is_blocked' => 'boolean',
            'api_token_expires_at' => 'datetime',
        ];
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /** @return HasMany<Customer, $this> */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return BelongsToMany<AclResponsibility, $this> */
    public function responsibilities(): BelongsToMany
    {
        return $this->belongsToMany(
            AclResponsibility::class,
            'acl_user_responsibility',
            'user_id',
            'responsibility_id',
        )->withPivot(['assigned_by', 'assigned_at'])->withTimestamps();
    }

    /** @return HasMany<AclUserPermissionOverride, $this> */
    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(AclUserPermissionOverride::class);
    }

    /** @return HasMany<AgentConversation, $this> */
    public function agentConversations(): HasMany
    {
        return $this->hasMany(AgentConversation::class);
    }

    /** @return HasMany<AgentUpload, $this> */
    public function agentUploads(): HasMany
    {
        return $this->hasMany(AgentUpload::class);
    }

    /**
     * @return array<int, string>
     */
    public function effectiveBackendPermissions(): array
    {
        return app(BackendAclService::class)->effectivePermissionKeys($this);
    }

    public function hasBackendPermission(string $permissionKey): bool
    {
        return app(BackendAclService::class)->userHasPermission($this, $permissionKey);
    }

    public function canAccessBackend(): bool
    {
        return app(BackendAclService::class)->canAccessBackend($this);
    }

    public function isBlocked(): bool
    {
        return ! $this->is_admin && (bool) $this->is_blocked;
    }

    public function customerStatusKey(): ?string
    {
        if ($this->is_admin) {
            return null;
        }

        if ($this->isBlocked()) {
            return 'blocked';
        }

        if ($this->ordersCount() > 0) {
            return 'active';
        }

        return $this->hasVerifiedEmail() ? 'new' : 'pending';
    }

    public function customerStatusLabel(): ?string
    {
        return match ($this->customerStatusKey()) {
            'active' => 'Ativo',
            'new' => 'Novo',
            'pending' => 'Pendente',
            'blocked' => 'Bloqueado',
            default => null,
        };
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new PasswordResetRequested($token));
    }

    private function ordersCount(): int
    {
        if ($this->getAttribute('orders_count') !== null) {
            return (int) $this->getAttribute('orders_count');
        }

        if ($this->relationLoaded('orders')) {
            return $this->orders->count();
        }

        return $this->orders()->count();
    }
}
