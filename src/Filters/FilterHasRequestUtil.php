<?php

namespace Rostislav\LaravelFilters\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

// Утилита для сортировки
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

    public static function in($request, Builder|QueryBuilder $builder, array $fillable = [], bool $is_not = false): Builder|QueryBuilder
    {
        collect($request)->each(function ($value, $key) use ($builder, $fillable, $is_not) {
            if (!FilterTypeUtil::check($key)) return;

            if (!empty($fillable) && array_search($key, $fillable) !== false) return;
            $where = QueryString::convertToArray($value);

            $name_array = explode('.', $key);
            $key = array_splice($name_array, -1, 1)[0];
            $name_has = implode('.', $name_array);

            if ($is_not) {
                $builder->whereHas($name_has, function ($query) use ($key, $where) {
                    $query->whereNotIn($key, $where);
                });
            } else {
                $builder->whereHas($name_has, function ($query) use ($key, $where) {
                    $query->whereIn($key, $where);
                });
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
