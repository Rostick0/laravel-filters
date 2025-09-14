<?php

namespace Rostislav\LaravelFilters\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class QueryWith
{
    /**
     * Добавляет сумму связей записей в таблице
     *
     * @param Request $request
     * @param Builder|QueryBuilder $data
     * @return Builder
     */
    public static function setSum($request, Builder|QueryBuilder $data): Builder
    {
        foreach (QueryString::convertSumToArray($request['extendsSum']) as $item) {
            $data = $data?->withSum($item[0], $item[1]);
        }

        return $data;
    }
}
