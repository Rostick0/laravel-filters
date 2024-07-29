<?php

namespace Rostislav\LaravelFilters\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Casts\Json;

class FilterSomeRequestUtil
{
    /**
     * @param class-string<"NULL"|"LIKE"> $type_where
     */

    public static function template($request, Builder|QueryBuilder $builder, array $fillable_block = [], $type = '=', ?string $type_where = "NULL|LIKE"): Builder|QueryBuilder
    {
        collect($request)->each(function ($value, $key) use ($builder, $fillable_block, $type, $type_where) {
            $values = Json::decode($value, false);

            if (!empty($fillable_block) && array_search($key, $fillable_block) !== false) return;

            if (!is_array($values)) {
                $values = [$values];
            }

            foreach ($values as $once) {
                $builder = FilterSomeRequestUtil::once(
                    $type_where,
                    $once->column_value ?? null,
                    $once->value ?? null,
                    $type,
                    $builder,
                    $key,
                    [
                        [$once->column_id ?? null, '=', $once->id ?? null]
                    ]
                );
            }
        });

        return $builder;
    }

    private static function once($type_where, $column_value, $value, $type, $builder, $key, $where)
    {
        if (ctype_digit($value)) $value = (int) $value;

        if (!isset($value)) {
        } else if ($type_where === 'NULL') {
            $where[] = [$column_value, $type, NULL];
        } else if ($type_where === 'LIKE') {
            $where[] = [$column_value, 'LIKE', '%' . $value . '%'];
        } else {
            $where[] = [$column_value, $type, $value];
        }

        return $builder->whereHas($key, function ($query) use ($where) {
            $query->where($where);
        });
    }

    private static function in($request, Builder|QueryBuilder $builder, array $fillable_block = [])
    {
        collect($request)->each(function ($value, $key) use ($builder, $fillable_block) {
            $values = Json::decode($value, false);

            if (!empty($fillable_block) && array_search($key, $fillable_block) !== false) return;

            if (!is_array($values)) $values = [$values];

            foreach ($values as $once) {
                if (!$once?->column_id ?? null && !$once->id ?? null) return;
                
                $builder->whereHas($key, function ($query) use ($once) {
                    $query->whereIn($once->column_id, QueryString::convertToArray($once->id));
                });
            }
        });

        return $builder;
    }

    public static function all($request, Builder|QueryBuilder $builder, array $fillable_block = []): Builder|QueryBuilder
    {
        $data = $builder;

        if ($request->filterSomeEQ) $data = FilterSomeRequestUtil::template($request->filterSomeEQ, $builder, $fillable_block, '=');
        if ($request->filterSomeNEQ) $data = FilterSomeRequestUtil::template($request->filterSomeNEQ, $builder, $fillable_block, '!=');

        if ($request->filterSomeEQN) $data = FilterSomeRequestUtil::template($request->filterSomeEQN, $builder, $fillable_block, '=', 'NULL');
        if ($request->filterSomeNEQN) $data = FilterSomeRequestUtil::template($request->filterSomeNEQN, $builder, $fillable_block, '!=', 'NULL');

        if ($request->filterSomeGEQ) $data = FilterSomeRequestUtil::template($request->filterSomeGEQ, $builder, $fillable_block, '>=');
        if ($request->filterSomeLEQ) $data = FilterSomeRequestUtil::template($request->filterSomeLEQ, $builder, $fillable_block, '<=');
        if ($request->filterSomeGE) $data = FilterSomeRequestUtil::template($request->filterSomeGE, $builder, $fillable_block, '>');
        if ($request->filterSomeLE) $data = FilterSomeRequestUtil::template($request->filterSomeLE, $builder, $fillable_block, '<');

        if ($request->filterSomeLIKE) $data = FilterSomeRequestUtil::template($request->filterSomeLIKE, $builder, $fillable_block, 'LIKE', 'LIKE');

        if ($request->filterSomeIN) $data = FilterSomeRequestUtil::in($request->filterSomeIN, $builder, $fillable_block);

        return $data;
    }
}
