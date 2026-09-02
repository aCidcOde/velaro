<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AclResponsibility extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'name',
        'description',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            AclPermission::class,
            'acl_responsibility_permission',
            'responsibility_id',
            'permission_id',
        )->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'acl_user_responsibility',
            'responsibility_id',
            'user_id',
        )->withPivot(['assigned_by', 'assigned_at'])->withTimestamps();
    }
}
