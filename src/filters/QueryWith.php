<?php

namespace Rostislav\LaravelFilters\Filters;

use Illuminate\Database\Eloquent\builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

class QueryWith
{
    // public static function set($request, Model|builder|QueryBuilder $data)
    // {
    //     // $extendsCountQuery = [];
    //     // $extendsCountQueryStrings =  [];
    //     $extendsCount = QueryString::convertToArray($request->extendsCount);
    //     // foreach ($extendsCount as $item) {
    //     //     if (str_contains($item, '.')) {
    //     //         $extendsCountQuery[substr($item, 0, strrpos($item, '.'))] = function ($query) use ($item) {
    //     //             return $query->withCount(substr($item, strrpos($item, '.') + 1));
    //     //         };
    //     //     } else {
    //     //         $extendsCountQueryStrings[] = substr($item, strrpos($item, '.'));
    //     //     }
    //     // }

    //     // $data = $data?->with([...QueryString::convertToArray($request->extends), ...$extendsCountQuery]);
    //     $data = $data?->withCount($extendsCount);

    //     return $data;
    // }

    public static function setSum($request, builder|QueryBuilder $data)
    {
        foreach (QueryString::convertSumToArray($request->extendsSum) as $item) {
            $data = $data?->withSum($item[0], $item[1]);
        }

        return $data;
    }
}
