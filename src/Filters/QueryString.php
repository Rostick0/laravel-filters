<?php

namespace Rostislav\LaravelFilters\Filters;

class QueryString
{
    /**
     * Конверитурет строковые значение через запятую в массив
     * @param string|null $string
     * @return array
     */
    public static function convertToArray(string|null $string): array
    {
        if (empty($string)) return [];

        return explode(',', $string);
    }

    /**
     * Конверитурет строковые значение суммы в массив
     * @param string|null $string
     * @return array
     */
    public static function convertSumToArray(string|null $string): array
    {
        $data = self::convertToArray($string);
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
