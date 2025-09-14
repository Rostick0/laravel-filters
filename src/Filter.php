<?php

namespace Rostislav\LaravelFilters;

use Rostislav\LaravelFilters\Filters\FilterHasRequestUtil;
use Rostislav\LaravelFilters\Filters\FilterHasUtil;
use Rostislav\LaravelFilters\Filters\FilterQResuestUtil;
use Rostislav\LaravelFilters\Filters\FilterRequestUtil;
use Rostislav\LaravelFilters\Filters\FilterSomeRequestUtil;
use Rostislav\LaravelFilters\Filters\OrderByUtil;
use Rostislav\LaravelFilters\Filters\QueryString;
use Rostislav\LaravelFilters\Filters\QueryWith;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class Filter
{
    /**
     * Получить все записи с фильтрацией и пагинацией.
     *
     * @param Request $request
     * @param Model|QueryBuilder $model
     * @param array $fillableBlock Список полей, доступных для фильтрации
     * @param array $where Вложенные условия (whereHas/whereDoesntHave)
     * @param array $qRequest Список полей для поиска по `filterQ`
     * @param bool $isPaginate Включить пагинацию
     * @return Paginator|Builder
     */
    public static function all(Request $request, Model|QueryBuilder $model, array $fillable_block = [], array $where = [], array $q_request = [], bool $is_paginate = true): Paginator|Builder
    {
        // значение по нескольким столбам
        if ($q_request && $request->has('filterQ')) {
            $data = FilterQResuestUtil::setParam($request['filterQ'], Filter::query($request, $model, $fillable_block, $where), $q_request[0]);

            foreach (array_slice($q_request, 1) as $param) {
                $data->union(FilterQResuestUtil::setParam($request['filterQ'], Filter::query($request, $model, $fillable_block, $where), $param));
            }

            if ($request->has('sort')) {
                OrderByUtil::set($request['sort'], $data);
            }
            if ($model instanceof Model) {
                $data?->withCount(QueryString::convertToArray($request['extendsCount']));
                QueryWith::setSum($request, $data);
            }
        } else {
            $data = Filter::query($request, $model, $fillable_block, $where);
        }

        if ($is_paginate) return $data->paginate($request['limit']);

        return $data;
    }

    /**
     * Базовая логика построения запроса с фильтрами.
     *
     * @param Request $request
     * @param Model|QueryBuilder $model
     * @param array $fillableBlock
     * @param array $where
     * @return Builder|QueryBuilder
     */
    public static function query(Request $request, Model|QueryBuilder $model, array $fillable_block = [], array $where = []): Builder|QueryBuilder
    {
        if ($model instanceof \Illuminate\Database\Query\Builder) {
            $data = $model;
        } else {
            $data = $model->query();
            if ($request->has('extends')) {
                $data->with(QueryString::convertToArray($request['extends']));
            }

            if ($request->has('doesntHave')) {
                foreach (QueryString::convertToArray($request['doesntHave']) as $doesntHaveitem) {
                    $data->doesntHave($doesntHaveitem);
                }
            }
        }

        FilterRequestUtil::all($request, $data, $fillable_block);
        FilterHasRequestUtil::all($request, $data, $fillable_block);
        FilterHasUtil::all($request, $data, $fillable_block);
        FilterSomeRequestUtil::all($request, $data, $fillable_block);
        if ($request->has('sort')) {
            OrderByUtil::set($request['sort'], $data);
        }
        if (!($model instanceof \Illuminate\Database\Query\Builder) && $request->has('extendsCount')) {
            $data?->withCount(QueryString::convertToArray($request['extendsCount']));
            QueryWith::setSum($request, $data);
        }

        if (!empty($where)) {
            Filter::applyWhereConditions($data, $where);
        }

        return $data;
    }

    /**
     * Получить одну запись по ID с фильтрацией.
     *
     * @param Request $request
     * @param Model $model
     * @param int $id
     * @param array $where
     * @return Model
     */
    public static function one(Request $request, Model $model, int $id, array $where = []): Model
    {
        $data = $model->query();

        if ($request->has('extends')) {
            $data = $model->with(QueryString::convertToArray($request['extends']));
        }
        if ($request->has('extendsCount')) {
            $data?->withCount(QueryString::convertToArray($request['extendsCount']));
            QueryWith::setSum($request, $data);
        }
        if ($request->has('doesntHave')) {
            foreach (QueryString::convertToArray($request['doesntHave']) as $doesntHaveitem) {
                $data->doesntHave($doesntHaveitem);
            }
        }

        if (!empty($where)) {
            Filter::applyWhereConditions($data, $where);
        }

        return $data->findOrFail($id);
    }

    /**
     * Применить сложные условия WHERE (включая has/doesntHave).
     *
     * @param Builder|QueryBuilder $query
     * @param array $conditions Массив условий: [column, operator, value, relation]
     * @return Builder|QueryBuilder
     */
    private static function applyWhereConditions(Builder|QueryBuilder $query, array $conditions): Builder|QueryBuilder
    {
        foreach ($conditions as $condition) {
            [$column, $operator, $value, $relation] = array_pad($condition, 4, null);

            if ($relation) {
                if (isset($condition[4]) && $condition[4] === 'doesntHave') {
                    $query->whereDoesntHave($relation, fn($q) => $q->where($column, $operator, $value));
                } else {
                    $query->whereHas($relation, fn($q) => $q->where($column, $operator, $value));
                }
            } else {
                $query->where($column, $operator, $value);
            }
        }

        return $query;
    }
}
