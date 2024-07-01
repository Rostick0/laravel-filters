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

class Filter
{
    public static function all($request, Model|QueryBuilder $model, array $fillable_block = [], array $where = [], array $q_request = []): Paginator
    {
        $data = null;

        // значение по нескольким столбам
        if ($q_request) {
            $data = FilterQResuestUtil::setParam($request->filterQ, Filter::query($request, $model, $fillable_block, $where), $q_request[0]);

            foreach (array_slice($q_request, 1) as $param) {
                $data->union(FilterQResuestUtil::setParam($request->filterQ, Filter::query($request, $model, $fillable_block, $where), $param));
            }
        } else {
            $data = Filter::query($request, $model, $fillable_block, $where);
        }

        $data = $data->paginate($request->limit);

        return $data;
    }

    public static function query($request, Model|QueryBuilder $model, array $fillable_block = [], array $where = [])
    {
        $data = null;
        if (get_class($model) === 'Illuminate\Database\Query\Builder') {
            $data = $model;
        } else {
            $data = $model->query();
            $data = $data->with(QueryString::convertToArray($request->extends));
        }

        $data = FilterRequestUtil::all($request, $data, $fillable_block);
        $data = FilterHasRequestUtil::all($request, $data, $fillable_block);
        $data = FilterHasUtil::all($request, $data, $fillable_block);
        $data = FilterSomeRequestUtil::all($request, $data, $fillable_block);
        if ($request->has('sort')) $data = OrderByUtil::set($request->sort, $data);
        if (get_class($model) !== 'Illuminate\Database\Query\Builder') {
            $data = $data?->withCount(QueryString::convertToArray($request->extendsCount));
            $data = QueryWith::setSum($request, $data);
        }

        if ($where) $data = Filter::where($data, $where);

        return $data;
    }

    public static function one($request, Model $model, int $id, array $where = [])
    {
        $data = $model->with(QueryString::convertToArray($request->extends));
        // получение количества
        $data = $data?->withCount(QueryString::convertToArray($request->extendsCount));
        // получение суммы
        $data = QueryWith::setSum($request, $data);
        $data = Filter::where($data, $where);
        $data = $data->findOrFail($id);

        return $data;
    }

    private static function where($data, $where)
    {
        // where для вложенных данных
        foreach ($where as $dataWhere) {
            // 0 - название колонки, 1 - оператор (=, LIKE и прочее), 2 - значение, 3 - название связи 
            if (!empty($dataWhere[3])) {
                $data->whereHas($dataWhere[3], function ($query) use ($dataWhere) {
                    $query->where($dataWhere[0], $dataWhere[1], $dataWhere[2]);
                });

                continue;
            }

            $data->where([$dataWhere]);
        }

        return $data;
    }
}
