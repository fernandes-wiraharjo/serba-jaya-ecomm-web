<?php

namespace App\Docs;

use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Path;
use Dedoc\Scramble\Support\Generator\Tag;
use Illuminate\Support\Str;

class RouteDocumentOrder
{
    /**
     * List documented endpoints in the order their routes were registered.
     *
     * Scramble otherwise groups endpoints by controller name. The docs UI
     * follows the OpenAPI tags array, so that array is rebuilt from the
     * route order.
     */
    public static function apply(OpenApi $openApi): void
    {
        $openApi->paths(self::pathsInRouteOrder($openApi->paths));

        $tags = collect($openApi->tags)->keyBy(fn (Tag $tag): string => $tag->name);
        $ordered = [];

        foreach ($openApi->paths as $path) {
            foreach ($path->operations as $operation) {
                $name = self::tagName($operation, $path);
                $ordered[$name] ??= $tags->get($name) ?? new Tag($name);
            }
        }

        $openApi->tags = array_values($ordered);
    }

    /**
     * @param  array<int, Path>  $paths
     * @return array<int, Path>
     */
    private static function pathsInRouteOrder(array $paths): array
    {
        return collect($paths)
            ->sortBy(function (Path $path): int {
                $indexes = collect($path->operations)
                    ->map(fn (Operation $operation): int => (int) $operation->getAttribute('index', PHP_INT_MAX));

                return $indexes->min() ?? PHP_INT_MAX;
            })
            ->values()
            ->all();
    }

    private static function tagName(Operation $operation, Path $path): string
    {
        if ($operation->tags !== []) {
            return $operation->tags[0];
        }

        $segment = Str::of($path->path)->trim('/')->before('/')->toString();

        $operation->tags = [Str::studly($segment !== '' ? $segment : 'endpoints')];

        return $operation->tags[0];
    }
}
