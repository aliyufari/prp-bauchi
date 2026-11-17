<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class LocationChain
{
    public const LEVELS = ['state', 'zone', 'lga', 'ward', 'pu'];

    public const TABLES = [
        'state' => 'states', 'zone' => 'zones', 'lga' => 'lgas', 'ward' => 'wards', 'pu' => 'pus',
    ];

    /** The configured operating state for this deployment. */
    public static function operatingStateId(): ?string
    {
        $stateName = trim((string) config('location.operating_state', 'Bauchi'));
        $cacheKey = 'operating-state:'.strtolower($stateName).':id';

        return Cache::remember(
            $cacheKey,
            3600,
            fn () => DB::table('states')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($stateName)])
                ->value('id'),
        );
    }

    /** @deprecated Use operatingStateId(); retained for existing application integrations. */
    public static function bauchiStateId(): ?string
    {
        return self::operatingStateId();
    }

    public static function index(string $level): ?int
    {
        $index = array_search($level, self::LEVELS, true);

        return $index === false ? null : $index;
    }

    public static function parentOf(string $level): ?string
    {
        $i = self::index($level);

        return $i > 0 ? self::LEVELS[$i - 1] : null;
    }

    public static function childOf(string $level): ?string
    {
        return self::LEVELS[self::index($level) + 1] ?? null;
    }

    /**
     * Fill every ancestor id, walking up from the LOWEST level supplied.
     * The lowest level is authoritative: stale or wrong parents are overwritten.
     */
    public static function complete(array $ids): array
    {
        $out = [];
        foreach (self::LEVELS as $l) {
            $out["{$l}_id"] = $ids["{$l}_id"] ?? null;
        }

        $cursor = null;
        foreach (array_reverse(self::LEVELS) as $l) {
            if ($out["{$l}_id"]) {
                $cursor = $l;
                break;
            }
        }

        while ($cursor && ($parent = self::parentOf($cursor))) {
            $parentId = DB::table(self::TABLES[$cursor])
                ->where('id', $out["{$cursor}_id"])
                ->value("{$parent}_id");

            if (!$parentId) {
                break;
            }

            $out["{$parent}_id"] = $parentId;
            $cursor = $parent;
        }

        return $out;
    }

    /** True when every submitted id agrees with the chain derived from the lowest one. */
    public static function isConsistent(array $submitted): bool
    {
        $derived = self::complete($submitted);

        foreach (self::LEVELS as $l) {
            $given = $submitted["{$l}_id"] ?? null;
            if ($given && $derived["{$l}_id"] !== $given) {
                return false;
            }
        }

        return true;
    }

    /** Subquery: ids of $type locations that sit at, or below, the node ($level, $id). */
    public static function within(string $type, string $level, string $id): Builder
    {
        $table = self::TABLES[$type];

        if ($type === $level) {
            return DB::table($table)->select('id')->where('id', $id);
        }

        $parent = self::parentOf($type);
        $q = DB::table($table)->select('id');

        return $parent === $level
            ? $q->where("{$parent}_id", $id)
            : $q->whereIn("{$parent}_id", self::within($parent, $level, $id));
    }

    public static function name(string $level, ?string $id): ?string
    {
        if (!$id) {
            return null;
        }

        return Cache::remember(
            "location-name:{$level}:{$id}",
            3600,
            fn () => DB::table(self::TABLES[$level])->where('id', $id)->value('name'),
        );
    }
}