<?php

namespace Rostislav\LaravelFilters\Filters;

class FilterTypeUtil
{
    /**
     * Проверяет наличие связи
     *
     * @param string $key
     * @return bool
     */
    public static function check(string $key): bool
    {
        return isset(explode('.', $key)[1]);
    }
}
