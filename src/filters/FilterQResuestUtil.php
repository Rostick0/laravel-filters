<?php

namespace Rostislav\LaravelFilters\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;

class FilterQResuestUtil
{
    public static function setParam($value, Builder $builder, array $param): Builder
    {
        if ($param[1] == 'LIKE') {
            $value = '%' . $value . '%';
        }

        if (!empty($param[2])) {
            return $builder->whereHas($param[2], function ($query) use ($param, $value) {
                $query->where($param[0], $param[1], $value);
            });
        }

        return $builder->where($param[0], $param[1], $value);
    }
}
