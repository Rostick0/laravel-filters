<?php

namespace Rostislav\LaravelFilters\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;

// Утилита для сортировки
class FilterHasRequestUtil
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

    /**
     * Вызов одного фильтра с типом in в запросах where
     * @param array $request
     * @param Builder|QueryBuilder $builder
     * @param array $fillable
     * @param bool $is_not
     * @return Builder|QueryBuilder
     */
    public static function in(array $request, Builder|QueryBuilder $builder, array $fillable = [], bool $is_not = false): Builder|QueryBuilder
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

        if (isset($request['filterEQ'])) self::template($request['filterEQ'], $builder, $fillable_block, '=');
        if (isset($request['filterNEQ'])) self::template($request['filterNEQ'], $builder, $fillable_block, '!=');

        if (isset($request['filterEQN'])) self::template($request['filterEQN'], $builder, $fillable_block, '=', 'NULL');
        if (isset($request['filterNEQN'])) self::template($request['filterNEQN'], $builder, $fillable_block, '!=', 'NULL');

        if (isset($request['filterGEQ'])) self::template($request['filterGEQ'], $builder, $fillable_block, '>=');
        if (isset($request['filterLEQ'])) self::template($request['filterLEQ'], $builder, $fillable_block, '<=');
        if (isset($request['filterGE'])) self::template($request['filterGE'], $builder, $fillable_block, '>');
        if (isset($request['filterLE'])) self::template($request['filterLE'], $builder, $fillable_block, '<');

        if (isset($request['filterLIKE'])) self::template($request['filterLIKE'], $builder, $fillable_block, 'LIKE', 'LIKE');

        if (isset($request['filterIN'])) self::in($request['filterIN'], $builder, $fillable_block);
        if (isset($request['filterNotIN'])) self::in($request['filterNotIN'], $builder, $fillable_block, true);

        return $data;
    }
}
