<?php

namespace Rostislav\LaravelFilters\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Http\Request;

class FilterSomeRequestUtil
{
    /**
     * Шаблон для работы с фильтрацией по связи
     * @param array $request
     * @param Builder|QueryBuilder $builder
     * @param array $fillable_block
     * @param string $type
     * @param string|null $type_where "NULL"|"LIKE"
     * @return Builder|QueryBuilder
     */
    public static function template(array $request, Builder|QueryBuilder $builder, array $fillable_block = [], $type = '=', ?string $type_where = "NULL|LIKE"): Builder|QueryBuilder
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

    /**
     * Вызов одного фильтра
     * @param $type_where
     * @param $column_value
     * @param $value
     * @param $type
     * @param $builder
     * @param $key
     * @param $where
     * @return mixed
     */
    private static function once($type_where, $column_value, $value, $type, $builder, $key, $where)
    {
        if (ctype_digit($value)) $value = (int)$value;

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

    /**
     * Вызов одного фильтра с типом in в запросах where
     * @param array $request
     * @param Builder|QueryBuilder $builder
     * @param array $fillable_block
     * @return Builder|QueryBuilder
     */
    private static function in(array $request, Builder|QueryBuilder $builder, array $fillable_block = [])
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

        if (isset($request['filterSomeEQ'])) self::template($request['filterSomeEQ'], $builder, $fillable_block, '=');
        if (isset($request['filterSomeNEQ'])) self::template($request['filterSomeNEQ'], $builder, $fillable_block, '!=');

        if (isset($request['filterSomeEQN'])) self::template($request['filterSomeEQN'], $builder, $fillable_block, '=', 'NULL');
        if (isset($request['filterSomeNEQN'])) self::template($request['filterSomeNEQN'], $builder, $fillable_block, '!=', 'NULL');

        if (isset($request['filterSomeGEQ'])) self::template($request['filterSomeGEQ'], $builder, $fillable_block, '>=');
        if (isset($request['filterSomeLEQ'])) self::template($request['filterSomeLEQ'], $builder, $fillable_block, '<=');
        if (isset($request['filterSomeGE'])) self::template($request['filterSomeGE'], $builder, $fillable_block, '>');
        if (isset($request['filterSomeLE'])) self::template($request['filterSomeLE'], $builder, $fillable_block, '<');

        if (isset($request['filterSomeLIKE'])) self::template($request['filterSomeLIKE'], $builder, $fillable_block, 'LIKE', 'LIKE');

        if (isset($request['filterSomeIN'])) self::in($request['filterSomeIN'], $builder, $fillable_block);

        return $data;
    }
}
