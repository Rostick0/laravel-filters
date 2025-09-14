<?php

namespace Rostislav\LaravelFilters\Filters;

use Illuminate\Database\Eloquent\builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;

class FilterHasUtil
{
    /**
     * Шаблон для работы с фильтрацией по связи
     * @param array $request
     * @param Builder|QueryBuilder $builder
     * @param array $fillable_block
     * @param string $type
     * @return Builder|QueryBuilder
     */
    public static function template(array $request, Builder|QueryBuilder $builder, array $fillable_block = [], $type = '='): Builder|QueryBuilder
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

    /**
     * Вызов всех фильтров
     * @param Request $request
     * @param Builder|QueryBuilder $builder
     * @param array $fillable_block
     * @return Builder|QueryBuilder
     */
    public static function all(Request $request, Builder|QueryBuilder $builder, array $fillable_block = []): Builder|QueryBuilder
    {
        $data = $builder;

        if (isset($request['hasEQ'])) FilterHasUtil::template($request['hasEQ'], $builder, $fillable_block, '=');
        if (isset($request['hasNEQ'])) FilterHasUtil::template($request['hasNEQ'], $builder, $fillable_block, '!=');

        if (isset($request['hasGEQ'])) FilterHasUtil::template($request['hasGEQ'], $builder, $fillable_block, '>=');
        if (isset($request['hasLEQ'])) FilterHasUtil::template($request['hasLEQ'], $builder, $fillable_block, '<=');
        if (isset($request['hasCE'])) FilterHasUtil::template($request['hasCE'], $builder, $fillable_block, '>');
        if (isset($request['hasLE'])) FilterHasUtil::template($request['hasLE'], $builder, $fillable_block, '<');

        return $data;
    }
}
