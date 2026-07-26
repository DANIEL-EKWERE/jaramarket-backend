<?php

namespace App\Models;

use App\Traits\AddPipelineToModelTrait;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use AddPipelineToModelTrait;

    protected $fillable = [
        'name',
        'country_id',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function lgas()
    {
        return $this->hasMany(Lga::class);
    }
}
