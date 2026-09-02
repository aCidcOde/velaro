<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AclPermission extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'module',
        'label',
        'description',
    ];

    public function responsibilities(): BelongsToMany
    {
        return $this->belongsToMany(
            AclResponsibility::class,
            'acl_responsibility_permission',
            'permission_id',
            'responsibility_id',
        )->withTimestamps();
    }

    public function userOverrides(): HasMany
    {
        return $this->hasMany(AclUserPermissionOverride::class, 'permission_id');
    }
}
