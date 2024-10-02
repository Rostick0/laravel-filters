<?php

namespace Rostislav\LaravelFilters\Filters;

use Illuminate\Database\Eloquent\builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class FilterHasUtil
{
    public static function template($request, Builder|QueryBuilder $builder, array $fillable_block = [], $type = '='): Builder|QueryBuilder
    {
        collect($request)->each(function ($value, $key) use ($builder, $fillable_block, $type) {
            if (FilterTypeUtil::check($key)) return;

            if (!empty($fillable_block) && array_search($key, $fillable_block) !== false) return;

            if (!isset($value)) {
            } else {
                $builder->has($key, $type, $value);
            }
        });

        return $builder;
    }

    public static function all($request, Builder|QueryBuilder $builder, array $fillable_block = []): Builder|QueryBuilder
    {
        $data = $builder;

        if (isset($request['hasEQ'])) $data = FilterHasUtil::template($request['hasEQ'], $builder, $fillable_block, '=');
        if (isset($request['hasNEQ'])) $data = FilterHasUtil::template($request['hasNEQ'], $builder, $fillable_block, '!=');

        if (isset($request['hasGEQ'])) $data = FilterHasUtil::template($request['hasGEQ'], $builder, $fillable_block, '>=');
        if (isset($request['hasLEQ'])) $data = FilterHasUtil::template($request['hasLEQ'], $builder, $fillable_block, '<=');
        if (isset($request['hasCE'])) $data = FilterHasUtil::template($request['hasCE'], $builder, $fillable_block, '>');
        if (isset($request['hasLE'])) $data = FilterHasUtil::template($request['hasLE'], $builder, $fillable_block, '<');

        return $data;
    }
}
