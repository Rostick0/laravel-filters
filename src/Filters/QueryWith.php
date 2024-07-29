<?php

namespace Rostislav\LaravelFilters\Filters;

use Illuminate\Database\Eloquent\builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class QueryWith
{
    public static function setSum($request, builder|QueryBuilder $data)
    {
        foreach (QueryString::convertSumToArray($request->extendsSum) as $item) {
            $data = $data?->withSum($item[0], $item[1]);
        }

        return $data;
    }
}
