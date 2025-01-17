<?php

namespace Rostislav\LaravelFilters\Filters;

use Illuminate\Database\Eloquent\builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class FilterRequestUtil
{
    /**
     * @param class-string<"NULL"|"LIKE"> $type_where
     */

    public static function template($request, Builder|QueryBuilder $builder, array $fillable_block = [], $type = '=', ?string $type_where = "NULL|LIKE"): Builder|QueryBuilder
    {
        collect($request)->each(function ($value, $key) use ($builder, $fillable_block, $type, $type_where) {
            if (FilterTypeUtil::check($key)) return;

            if (!empty($fillable_block) && array_search($key, $fillable_block) !== false) return;
            $where = [];
            $key = $builder->getModel()->getTable() . '.' . $key;

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
            if (FilterTypeUtil::check($key)) return;

            if (!empty($fillable) && array_search($key, $fillable) !== false) return;
            $where = QueryString::convertToArray($value);
            $key = $builder->getModel()->getTable() . '.' . $key;

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

        if (isset($request['filterEQ'])) $data = self::template($request['filterEQ'], $builder, $fillable_block, '=');
        if (isset($request['filterNEQ'])) $data = self::template($request['filterNEQ'], $builder, $fillable_block, '!=');

        if (isset($request['filterEQN'])) $data = self::template($request['filterEQN'], $builder, $fillable_block, '=', 'NULL');
        if (isset($request['filterNEQN'])) $data = self::template($request['filterNEQN'], $builder, $fillable_block, '!=', 'NULL');

        if (isset($request['filterGEQ'])) $data = self::template($request['filterGEQ'], $builder, $fillable_block, '>=');
        if (isset($request['filterLEQ'])) $data = self::template($request['filterLEQ'], $builder, $fillable_block, '<=');
        if (isset($request['filterGE'])) $data = self::template($request['filterGE'], $builder, $fillable_block, '>');
        if (isset($request['filterLE'])) $data = self::template($request['filterLE'], $builder, $fillable_block, '<');

        if (isset($request['filterLIKE'])) $data = self::template($request['filterLIKE'], $builder, $fillable_block, 'LIKE', 'LIKE');

        if (isset($request['filterIN'])) $data = self::in($request['filterIN'], $builder, $fillable_block);
        if (isset($request['filterNotIN'])) $data = self::in($request['filterNotIN'], $builder, $fillable_block, true);

        return $data;
    }
}
