<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'group'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions', 'permission_id', 'user_id')->withTimestamps();
    }
}
