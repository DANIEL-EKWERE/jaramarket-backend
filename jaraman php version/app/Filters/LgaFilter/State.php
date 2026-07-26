<?php

namespace App\Filters\LgaFilter;

use App\Filters\BaseFilter;

class State extends BaseFilter
{
    protected function applyFilter($builder)
    {
        return $builder->where(function ($query) {
            $search = request($this->filterName());
            $query->whereRelation('state', 'name', 'like', '%'.$search.'%');
        });
    }
}
