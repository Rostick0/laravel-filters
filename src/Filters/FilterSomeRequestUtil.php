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
                $builder = self::once(
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

        if (isset($request['filterSomeEQ'])) $data = self::template($request['filterSomeEQ'], $builder, $fillable_block, '=');
        if (isset($request['filterSomeNEQ'])) $data = self::template($request['filterSomeNEQ'], $builder, $fillable_block, '!=');

        if (isset($request['filterSomeEQN'])) $data = self::template($request['filterSomeEQN'], $builder, $fillable_block, '=', 'NULL');
        if (isset($request['filterSomeNEQN'])) $data = self::template($request['filterSomeNEQN'], $builder, $fillable_block, '!=', 'NULL');

        if (isset($request['filterSomeGEQ'])) $data = self::template($request['filterSomeGEQ'], $builder, $fillable_block, '>=');
        if (isset($request['filterSomeLEQ'])) $data = self::template($request['filterSomeLEQ'], $builder, $fillable_block, '<=');
        if (isset($request['filterSomeGE'])) $data = self::template($request['filterSomeGE'], $builder, $fillable_block, '>');
        if (isset($request['filterSomeLE'])) $data = self::template($request['filterSomeLE'], $builder, $fillable_block, '<');

        if (isset($request['filterSomeLIKE'])) $data = self::template($request['filterSomeLIKE'], $builder, $fillable_block, 'LIKE', 'LIKE');

        if (isset($request['filterSomeIN'])) $data = self::in($request['filterSomeIN'], $builder, $fillable_block);

        return $data;
    }
}
