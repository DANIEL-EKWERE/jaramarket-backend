<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_name',
        'business_address',
        'business_phone',
        'business_email',
        'business_registration_number',
        'tax_identification_number',
        'business_description',
        'bank_name',
        'account_number',
        'account_name',
        'is_verified',
        'is_active',
        'user_id'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
