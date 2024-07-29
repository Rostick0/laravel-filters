<?php

namespace Rostislav\LaravelFilters\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class FilterHasRequestUtil
{
    /**
     * @param class-string<"NULL"|"LIKE"> $type_where
     */

    public static function template($request, Builder|QueryBuilder $builder, array $fillable_block = [], $type = '=', ?string $type_where = "NULL|LIKE"): Builder|QueryBuilder
    {
        collect($request)->each(function ($value, $name) use ($builder, $fillable_block, $type, $type_where) {
            if (!FilterTypeUtil::check($name)) return;

            $name_array = explode('.', $name);
            $key = array_splice($name_array, -1, 1)[0];
            $name_has = implode('.', $name_array);

            if (!empty($fillable_block) && array_search($key, $fillable_block) !== false) return;

            $where = [];

            if (!isset($value)) {
                return $builder;
            } else if ($type_where === 'NULL') {
                $where[] = [$key, $type, NULL];
            } else if ($type_where === 'LIKE') {
                $where[] = [$key, 'LIKE', '%' . $value . '%'];
            } else {
                $where[] = [$key, $type, $value];
            }

            $builder->whereHas($name_has, function ($query) use ($where) {
                $query->where($where);
            });
        });

        return $builder;
    }

    public static function all($request, Builder|QueryBuilder $builder, array $fillable_block = []): Builder|QueryBuilder
    {
        $data = $builder;

        if ($request->filterEQ) $data = FilterHasRequestUtil::template($request->filterEQ, $builder, $fillable_block, '=');
        if ($request->filterNEQ) $data = FilterHasRequestUtil::template($request->filterNEQ, $builder, $fillable_block, '!=');

        if ($request->filterEQN) $data = FilterHasRequestUtil::template($request->filterEQN, $builder, $fillable_block, '=', 'NULL');
        if ($request->filterNEQN) $data = FilterHasRequestUtil::template($request->filterNEQN, $builder, $fillable_block, '!=', 'NULL');

        if ($request->filterGEQ) $data = FilterHasRequestUtil::template($request->filterGEQ, $builder, $fillable_block, '>=');
        if ($request->filterLEQ) $data = FilterHasRequestUtil::template($request->filterLEQ, $builder, $fillable_block, '<=');
        if ($request->filterGE) $data = FilterHasRequestUtil::template($request->filterGE, $builder, $fillable_block, '>');
        if ($request->filterLE) $data = FilterHasRequestUtil::template($request->filterLE, $builder, $fillable_block, '<');

        if ($request->filterLIKE) $data = FilterHasRequestUtil::template($request->filterLIKE, $builder, $fillable_block, 'LIKE', 'LIKE');

        return $data;
    }
}
