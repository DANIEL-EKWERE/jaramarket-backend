<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Franchise extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location', 'owner_id'];

    /**
     * Get the owner of the franchise.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
