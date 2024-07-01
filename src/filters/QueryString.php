<?php

namespace Rostislav\LaravelFilters\Filters;

class QueryString
{
    public static function convertToArray($string)
    {
        if (empty($string)) return [];

        return explode(',', $string);
    }

    public static function convertSumToArray($string)
    {
        $data = QueryString::convertToArray($string);
        $array_data = [];

        foreach ($data as $item) {
            $convert_values = explode(':', $item);

            if (!isset($convert_values[1])) continue;

            $array_data[] = [
                $convert_values[0],
                $convert_values[1],
            ];
        }

        return $array_data;
    }
}
