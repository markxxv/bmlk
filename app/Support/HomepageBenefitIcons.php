<?php

namespace App\Support;

use Composer\InstalledVersions;

final class HomepageBenefitIcons
{
    public const DEFAULTS = [
        'feature_one' => 'leaf',
        'feature_two' => 'globe-2',
        'feature_three' => 'graduation-cap',
    ];

    private static function directory(): string
    {
        return InstalledVersions::getInstallPath('mallardduck/blade-lucide-icons').'/resources/svg/icons';
    }

    public static function names(): array
    {
        static $names;

        return $names ??= array_values(array_filter(array_map(
            fn (string $path) => basename($path, '.svg'),
            glob(self::directory().'/*.svg') ?: [],
        ), fn (string $name) => preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $name)));
    }

    public static function normalize(mixed $stored): array
    {
        $icons = self::DEFAULTS;

        foreach ($icons as $key => $default) {
            $name = is_array($stored) ? ($stored[$key] ?? null) : null;
            if (is_string($name) && in_array($name, self::names(), true)) {
                $icons[$key] = $name;
            }
        }

        return $icons;
    }

    private static function asset(string $name): array
    {
        // Only names from the installed Lucide set may reach this method.
        return ['name' => $name, 'svg' => file_get_contents(self::directory().'/'.$name.'.svg')];
    }

    public static function selected(mixed $stored): array
    {
        return array_map(self::asset(...), array_values(array_unique(self::normalize($stored))));
    }

    public static function search(string $query, int $page): array
    {
        $query = strtolower(trim($query));
        $query = preg_replace('/\s+/', '-', $query);
        $names = self::names();

        if ($query !== '') {
            $names = array_values(array_filter($names, fn (string $name) => str_contains($name, $query)));
            usort($names, fn (string $a, string $b) => [($a === $query ? 0 : (str_starts_with($a, $query) ? 1 : 2)), $a] <=> [($b === $query ? 0 : (str_starts_with($b, $query) ? 1 : 2)), $b]);
        } else {
            $featured = array_values(array_intersect(['leaf', 'globe-2', 'graduation-cap', 'shield-check', 'sparkles', 'heart', 'award', 'star', 'gem', 'flower-2', 'sprout', 'badge-check', 'hand-heart', 'check-circle', 'package', 'truck', 'clock', 'droplets'], $names));
            $names = array_merge($featured, array_diff($names, $featured));
        }

        $perPage = 36;
        $total = count($names);
        $page = min($page, max(1, (int) ceil($total / $perPage)));

        return [
            'icons' => array_map(self::asset(...), array_slice($names, ($page - 1) * $perPage, $perPage)),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }
}
