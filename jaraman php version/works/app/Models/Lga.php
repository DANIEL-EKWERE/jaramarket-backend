<?php

namespace App\Models;

use App\Traits\AddPipelineToModelTrait;
use Illuminate\Database\Eloquent\Model;

class Lga extends Model
{
    use AddPipelineToModelTrait;

    protected $fillable = [
        'name',
        'state_id',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
