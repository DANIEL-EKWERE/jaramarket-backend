<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'admin_permissions');
    }

    public function hasPermission($permission)
    {
        if (is_string($permission)) {
            return $this->permissions->contains('slug', $permission);
        }
        return false;
    }

    public function hasAnyPermission($permissions)
    {
        if (is_array($permissions)) {
            foreach ($permissions as $permission) {
                if ($this->hasPermission($permission)) {
                    return true;
                }
            }
        }
        return false;
    }
}
