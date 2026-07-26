<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StateRepresentative extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'state',
        'lga',
        'address',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
