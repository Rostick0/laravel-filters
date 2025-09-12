<?php

namespace Rostislav\LaravelFilters\Filters;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

// Утилита для сортировки
class OrderByUtil
{
    /**
     * Проверяет наличие минуса
     * @param string $name
     * @return bool
     */
    private static function checkMinus(string $name): bool
    {
        return $name[0] == '-';
    }

    /**
     * Тип сортировки
     * @param string $name
     * @return string
     */
    private static function type(string $name): string
    {
        if (self::checkMinus($name)) return 'ASC';

        return 'DESC';
    }

    /**
     * Удаляет минус
     * @param string $name
     * @return string
     */
    private static function removeMinus(string $name): string
    {
        if (self::checkMinus($name)) return substr($name, 1);

        return $name;
    }

    /**
     * Создает сортировку для одного запроса
     * @param string|null $name
     * @param Builder $builder
     * @param string|null $id_name
     * @return Builder
     */
    public static function one(?string $name, Builder $builder, ?string $id_name = 'id'): Builder
    {
        if (!$name) return $builder;

        $table = $builder->getModel()->getTable();
        $builder->select($table . '.*');
        $sort_name = '';

        $name_array = explode('.', $name);
        $my_relat = $builder;

        if (count($name_array) > 1) {
            foreach ((array_slice($name_array, 1)) as $key => $value) {
                $relat = $my_relat->getRelation(self::removeMinus($name_array[$key]));
                $relat_table = $relat->getModel()->getTable();

                $relat_parent = null;
                $relat_child = null;

                try {
                    $relat_parent = $relat->getOwnerKeyName();
                    $relat_child = $relat->getForeignKeyName();
                } catch (Exception $e) {
                    $relat_child = $relat->getLocalKeyName();
                    $relat_parent = $relat->getForeignKeyName();
                }

                $builder->leftJoin(
                    $relat_table,
                    function ($join) use ($my_relat, $relat_child, $relat_table, $relat_parent, $relat) {
                        $join->on($my_relat->getModel()->getTable() . '.' . $relat_child, '=', $relat_table . '.' . $relat_parent);

                        $query = $relat->getQuery()->toBase();
                        foreach ($query->wheres ?? [] as $where) {
                            foreach ($where['query']->wheres ?? [] as $where) {

                                if ($where['type'] === 'Basic') {
                                    $join->where($relat_table . '.' . $where['column'], $where['operator'], $where['value']);
                                }
                            }
                        }
                    }
                );

                $sort_name = $relat_table . '.';
                $my_relat = $relat;
            }
        }

        $sort_name .= end($name_array);

        return $sort_name ?
            $builder->orderBy(
                self::removeMinus(
                    $sort_name
                ),
                self::type($name)
            ) : $builder;
    }

    /**
     * Сортирует по всем запросам
     * @param string|null $name
     * @param Builder $builder
     * @return Builder
     */
    public static function set(?string $name, Builder $builder): Builder
    {
        if (!$name) return $builder;

        $data = explode(',', $name);

        foreach ($data as $item) {
            $builder = self::one($item, $builder);
        }

        return $builder;
    }
}
