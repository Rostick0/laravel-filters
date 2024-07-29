<?php

namespace Rostislav\LaravelFilters\Filters;

class FilterTypeUtil
{
    public static function check(string $key): bool
    {
        return isset(explode('.', $key)[1]);
    }
}
