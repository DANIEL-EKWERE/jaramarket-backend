<?php

namespace App\Filters\StateFilter;

use App\Filters\BaseFilter;

class Lga extends BaseFilter
{
    protected function applyFilter($builder)
    {
        return $builder->where(function ($query) {
            $search = request($this->filterName());
            $query->whereRelation('lgas', 'name', 'like', '%'.$search.'%');
        });
    }
}
