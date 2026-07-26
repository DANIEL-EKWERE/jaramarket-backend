<?php

namespace App\Filters\CountryFilter;

use App\Filters\BaseFilter;

class Name extends BaseFilter
{
    protected function applyFilter($builder)
    {
        return $builder->where(function ($query) {
            $search = request($this->filterName());
            $query->where($this->filterName(), $search);
        });
    }
}
