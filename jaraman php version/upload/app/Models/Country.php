<?php

namespace App\Models;

use App\Traits\AddPipelineToModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Country extends Model
{
    use HasFactory, AddPipelineToModelTrait;

    protected $fillable = ['name', 'code', 'currency', 'currency_sym','flag_id', 'vat'];

    public function states()
    {
        return $this->hasMany(State::class);
    }

    public function institutions()
    {
        return $this->hasMany(Institution::class);
    }

    public function flag()
    {
        return $this->belongsTo(Media::class, 'flag_id');
    }

    public function getFlagUrlAttribute()
    {
        return $this->flag ? url(config('paths.uploads_base_path') . '/' .'flags/' . $this->flag->source) : '';
    }

    public function marketplaces()
    {
        return $this->belongsToMany(Marketplace::class, 'marketplace_countries','country_id','marketplace_id')->withTimestamps();
    }

    public function featurePosts()
    {
        return $this->morphMany(FeaturePost::class, 'featureable');
    }

    public function tier()
    {
        return $this->belongsTo(Tier::class, 'tier_id');
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_countries', 'country_id', 'post_id')->withTimestamps();
    }
}
