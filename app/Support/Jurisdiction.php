<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Log;

final class Jurisdiction
{
    private function __construct(
        public readonly ?string $level,       // 'state'|'zone'|'lga'|'ward'|'pu'|null
        public readonly ?string $id,
        public readonly bool $unrestricted,   // super_admin / admin
        private readonly array $chain = [],   // state_id..pu_id of the user's own node
    ) {}

    public static function for(User $user): self
    {
        $cache = app('jurisdiction.cache'); // request-scoped ArrayObject

        return $cache[$user->getKey()] ??= self::resolve($user);
    }

    public static function flush(): void
    {
        app('jurisdiction.cache')->exchangeArray([]);
    }

    private static function resolve(User $user): self
    {
        if ($user->hasAnyRole([Role::SUPER_ADMIN->value, Role::ADMIN->value])) {
            return new self(null, null, true);
        }

        $profile = $user->profile;

        // Order matters if a user holds several roles: first match wins.
        $level = match (true) {
            $user->hasRole(Role::COORDINATOR->value) => self::levelOrWarn($user->coordinator?->type?->value, $user),
            $user->hasRole(Role::SUPERVISORY_AGENT->value) => self::levelOrWarn($user->supervisoryAgent?->type?->value, $user),
            $user->hasRole(Role::RETURNING_OFFICER->value) => 'lga',
            $user->hasRole(Role::PARTY_AGENT->value) => $profile?->pu_id ? 'pu' : 'ward',
            default => null,
        };

        $id = ($level && $profile) ? $profile->{"{$level}_id"} : null;

        // Fail closed: no level or no location means no access.
        if (!$level || !$id) {
            return new self(null, null, false);
        }

        return new self($level, $id, false, LocationChain::complete(["{$level}_id" => $id]));
    }

    public static function levelFor(?string $type): ?string
    {
        $t = strtoupper((string) $type);

        return match (true) {
            str_contains($t, 'STATE') => 'state',
            str_contains($t, 'ZON') => 'zone',
            str_contains($t, 'LGA') => 'lga',
            str_contains($t, 'WARD') => 'ward',
            default => null,
        };
    }

    private static function levelOrWarn(?string $type, User $user): ?string
    {
        $level = self::levelFor($type);

        if (!$level) {
            Log::warning('Unrecognised jurisdiction type; access denied.', ['user' => $user->id, 'type' => $type]);
        }

        return $level;
    }

    public function hasAccess(): bool
    {
        return $this->unrestricted || $this->level !== null;
    }

    public function column(): string
    {
        return "{$this->level}_id";
    }

    public function name(): ?string
    {
        return $this->level ? LocationChain::name($this->level, $this->id) : null;
    }

    public function childLevel(): ?string
    {
        if ($this->unrestricted) {
            return 'state';
        }

        return $this->level ? LocationChain::childOf($this->level) : null;
    }

    /** Does this jurisdiction contain the location? Completes the chain by default. */
    public function covers(array $location, bool $complete = true): bool
    {
        if ($this->unrestricted) {
            return true;
        }
        if (!$this->level) {
            return false;
        }

        $loc = $complete ? LocationChain::complete($location) : $location;

        return ($loc[$this->column()] ?? null) === $this->id;
    }

    public function coversUser(User $target): bool
    {
        if ($this->unrestricted) {
            return true;
        }

        $profile = $target->profile;

        return $profile
            ? $this->covers($profile->only(array_map(fn ($l) => "{$l}_id", LocationChain::LEVELS)), false)
            : false;
    }

    /** Levels fixed for this user (state down to their own level), as ['state_id' => id, ...]. */
    public function lockedIds(): array
    {
        if ($this->unrestricted || !$this->level) {
            return [];
        }

        $out = [];
        foreach (array_slice(LocationChain::LEVELS, 0, LocationChain::index($this->level) + 1) as $l) {
            $out["{$l}_id"] = $this->chain["{$l}_id"] ?? null;
        }

        return array_filter($out);
    }

    /** Same, shaped for the frontend: ['state' => ['id' => .., 'name' => ..], ...] */
    public function locked(): array
    {
        $out = [];
        foreach ($this->lockedIds() as $key => $id) {
            $level = str_replace('_id', '', $key);
            $out[$level] = ['id' => $id, 'name' => LocationChain::name($level, $id)];
        }

        return $out;
    }

    /**
     * Restrict a location table (states/zones/lgas/wards/pus) to what this user may see:
     * their own ancestors, their own node, and everything below it.
     */
    public function constrainTable(string $type, QueryBuilder $query): QueryBuilder
    {
        if ($this->unrestricted) {
            return $query;
        }
        if (!$this->level) {
            return $query->whereRaw('1 = 0');
        }

        $table = LocationChain::TABLES[$type];

        if (LocationChain::index($type) <= LocationChain::index($this->level)) {
            return $query->where("{$table}.id", $this->chain["{$type}_id"] ?? null);
        }

        return $query->whereIn("{$table}.id", LocationChain::within($type, $this->level, $this->id));
    }
}