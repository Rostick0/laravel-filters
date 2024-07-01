<?php

namespace Rostislav\LaravelFilters\Filters;

use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class FilterRequestUtil
{
    // public static function eq($request, array $fillable_block = [])
    // {
    //     $where = [];

    //     collect($request)->each(function ($value, $key) use (&$where) {
    //         $where[] = [$key, '=', $value];
    //         return [$key, '=', $value];
    //     });

    //     return $where;
    // }

    /**
     * @param class-string<"NULL"|"LIKE"> $type_where
     */

    public static function template($request, Builder|QueryBuilder $builder, array $fillable_block = [], $type = '=', ?string $type_where = "NULL|LIKE"): Builder|QueryBuilder
    {
        collect($request)->each(function ($value, $key) use ($builder, $fillable_block, $type, $type_where) {
            if (FilterTypeUtil::check($key)) return;

            if (!empty($fillable_block) && array_search($key, $fillable_block) !== false) return;
            $where = [];

            
            if (!isset($value)) {
            } else if ($type_where === 'NULL') {
                $where[] = [$key, $type, NULL];
            } else if ($type_where === 'LIKE') {
                $where[] = [$key, 'LIKE', '%' . $value . '%'];
            } else {
                $where[] = [$key, $type, $value];
            }

            $builder->where($where);
        });

        return $builder;
    }

    public static function in($request, Builder|QueryBuilder $builder, array $fillable = [], bool $is_not = false): Builder|QueryBuilder
    {
        collect($request)->each(function ($value, $key) use ($builder, $fillable, $is_not) {
            if (!empty($fillable) && array_search($key, $fillable) !== false) return;
            $where = QueryString::convertToArray($value);

            if ($is_not) {
                $builder->whereNotIn($key, $where);
            } else {
                $builder->whereIn($key, $where);
            }
        });

        return $builder;
    }

    public static function all($request, Builder|QueryBuilder $builder, array $fillable_block = []): Builder|QueryBuilder
    {
        $data = $builder;

        if ($request->filterEQ) $data = FilterRequestUtil::template($request->filterEQ, $builder, $fillable_block, '=');
        if ($request->filterNEQ) $data = FilterRequestUtil::template($request->filterNEQ, $builder, $fillable_block, '!=');

        if ($request->filterEQN) $data = FilterRequestUtil::template($request->filterEQN, $builder, $fillable_block, '=', 'NULL');
        if ($request->filterNEQN) $data = FilterRequestUtil::template($request->filterNEQN, $builder, $fillable_block, '!=', 'NULL');

        if ($request->filterGEQ) $data = FilterRequestUtil::template($request->filterGEQ, $builder, $fillable_block, '>=');
        if ($request->filterLEQ) $data = FilterRequestUtil::template($request->filterLEQ, $builder, $fillable_block, '<=');
        if ($request->filterGE) $data = FilterRequestUtil::template($request->filterGE, $builder, $fillable_block, '>');
        if ($request->filterLE) $data = FilterRequestUtil::template($request->filterLE, $builder, $fillable_block, '<');

        if ($request->filterLIKE) $data = FilterRequestUtil::template($request->filterLIKE, $builder, $fillable_block, 'LIKE', 'LIKE');

        if ($request->filterIN) $data = FilterRequestUtil::in($request->filterIN, $builder, $fillable_block);
        if ($request->filterNotIN) $data = FilterRequestUtil::in($request->filterNotIN, $builder, $fillable_block, true);

        return $data;
    }
}
