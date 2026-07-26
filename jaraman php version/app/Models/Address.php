<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id', 'state_id', 'lga_id','country_id', 'contact_address', 'phone_number', 'is_default',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function lga()
    {
        return $this->belongsTo(Lga::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

}