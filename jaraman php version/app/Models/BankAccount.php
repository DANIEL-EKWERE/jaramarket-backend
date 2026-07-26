<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id', 'owner_type', 'bank_code', 'account_number', 'bank_name', 'account_name',
    ];

    public function owner()
    {
        return $this->morphTo();
    }
}
