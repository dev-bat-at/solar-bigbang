<?php

namespace App\Support\Dashboard;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class AdminDashboardMetrics
{
    public static function labels(int $days = 30, string $format = 'd/m'): array
    {
        return collect(range($days - 1, 0))
            ->map(fn (int $offset): string => now()->subDays($offset)->format($format))
            ->all();
    }

    public static function dailySeries(
        string $modelClass,
        int $days = 30,
        ?Closure $scope = null,
        string $column = 'created_at',
    ): array {
        $startDate = now()->subDays($days - 1)->startOfDay();

        $counts = static::query($modelClass, $scope)
            ->where($column, '>=', $startDate)
            ->selectRaw("DATE({$column}) as bucket, COUNT(*) as aggregate")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('aggregate', 'bucket');

        return collect(range($days - 1, 0))
            ->map(function (int $offset) use ($counts): int {
                $date = now()->subDays($offset)->toDateString();

                return (int) ($counts[$date] ?? 0);
            })
            ->all();
    }

    public static function count(string $modelClass, ?Closure $scope = null): int
    {
        return static::query($modelClass, $scope)->count();
    }

    public static function countSince(
        string $modelClass,
        int $days = 30,
        ?Closure $scope = null,
        string $column = 'created_at',
    ): int {
        return static::query($modelClass, $scope)
            ->where($column, '>=', now()->subDays($days - 1)->startOfDay())
            ->count();
    }

    public static function breakdown(
        string $modelClass,
        string $column,
        array $keys,
        ?Closure $scope = null,
    ): array {
        $counts = static::query($modelClass, $scope)
            ->selectRaw("{$column} as bucket, COUNT(*) as aggregate")
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket');

        return collect($keys)
            ->map(fn (string $key): int => (int) ($counts[$key] ?? 0))
            ->all();
    }

    public static function sumLast(array $series, int $days = 7): int
    {
        return (int) array_sum(array_slice($series, -1 * $days));
    }

    protected static function query(string $modelClass, ?Closure $scope = null): Builder
    {
        /** @var Builder $query */
        $query = $modelClass::query();

        if ($scope) {
            $scope($query);
        }

        return $query;
    }
}
