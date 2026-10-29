<?php

namespace App\Traits;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\User;
use App\Support\LocationChain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

trait SeedsUsers
{
    /** Password for every generated demo account. Local and staging only. */
    protected string $demoPassword = 'password';

    protected ?string $demoHash = null;

    /** @var array<string, array<int, object>> location nodes per level, in a stable order */
    protected array $pools = [];

    private int $seq = 0;

    private const FIRST_NAMES = [
        'Aliyu', 'Fatima', 'Musa', 'Zainab', 'Ibrahim', 'Amina', 'Suleiman', 'Hauwa', 'Yakubu', 'Maryam',
        'Ahmed', 'Halima', 'Isah', 'Hadiza', 'Kabiru', 'Sadiya', 'Umar', 'Ngozi', 'Chidi', 'Blessing',
        'Danladi', 'Esther', 'Farouk', 'Grace', 'Habib', 'Ifeoma', 'Junaid', 'Larai', 'Muktar', 'Peace',
    ];

    private const LAST_NAMES = [
        'Yusuf', 'Bello', 'Abdullahi', 'Sani', 'Garba', 'Usman', 'Adamu', 'Idris', 'Lawal', 'Danjuma',
        'Okafor', 'Suleman', 'Nwosu', 'Eze', 'Bawa', 'Ogundele', 'Musa', 'Haruna', 'Jibril', 'Gambo',
        'Dahiru', 'Mustapha', 'Zubairu', 'Sale', 'Tanko', 'Shehu', 'Yahaya', 'Bature', 'Nasiru', 'Abba', 'Kolo',
    ];

    /**
     * Idempotent: running a seeder twice updates instead of crashing on the unique email.
     *
     * @param  array{0:string,1:string}|null  $name  null = next generated name
     */
    protected function makeUser(
        string $email,
        Role $role,
        array $profile = [],
        ?array $name = null,
        ?string $password = null,
        bool $sync = false,
    ): User {
        [$first, $last] = $name ?? $this->nextName();

        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists || $sync) {
            $user->forceFill([
                'first_name' => $first,
                'last_name' => $last,
                'password' => $password ? Hash::make($password) : $this->demoHash(),
                'email_verified_at' => now(),
                'is_active' => true,
            ])->save();
        }

        $user->syncRoles([$role->value]);
        $user->profile()->updateOrCreate(['user_id' => $user->id], $this->profile($profile));

        return $user;
    }

    protected function requireRoles(): void
    {
        $roleClass = app(PermissionRegistrar::class)->getRoleClass();

        if (! $roleClass::where('name', Role::MEMBER->value)->exists()) {
            throw new \RuntimeException('Roles are missing. Run PermissionSeeder first.');
        }
    }

    protected function loadPools(): void
    {
        foreach (LocationChain::LEVELS as $level) {
            $this->pools[$level] = DB::table(LocationChain::TABLES[$level])
                ->orderBy('name')->orderBy('id')
                ->get(['id', 'name'])->all();
        }
    }

    /** All five location keys, null unless set, so a re-run never leaves a stale chain behind. */
    protected function blankLocation(): array
    {
        return array_fill_keys(array_map(fn ($l) => "{$l}_id", LocationChain::LEVELS), null);
    }

    protected function profile(array $attributes = []): array
    {
        return $attributes + $this->blankLocation() + [
            'gender' => Gender::MALE->value,
            'training_status' => false,
            'mentor_status' => false,
        ];
    }

    /** Only the node at $level is set. NormalizeLocationObserver fills in every parent from it. */
    protected function place(string $level, ?object $node): array
    {
        return $node ? [...$this->blankLocation(), "{$level}_id" => $node->id] : $this->blankLocation();
    }

    protected function at(?string $level, int $i): array
    {
        return $level ? $this->place($level, $this->spread($level, $i)) : $this->blankLocation();
    }

    /** Deterministic: the same record lands in the same place on every run. */
    protected function spread(string $level, int $i): ?object
    {
        $pool = $this->pools[$level] ?? [];

        return $pool ? $pool[($i * 17) % count($pool)] : null;
    }

    protected function phone(int $group, int $i): string
    {
        return sprintf('080%02d%06d', $group, $i);
    }

    protected function gender(int $i): string
    {
        return $i % 2 === 0 ? Gender::FEMALE->value : Gender::MALE->value;
    }

    private function demoHash(): string
    {
        return $this->demoHash ??= Hash::make($this->demoPassword);
    }

    /** @return array{0:string,1:string} first and last names; coprime list sizes avoid repeats */
    private function nextName(): array
    {
        $n = $this->seq++;

        return [
            self::FIRST_NAMES[$n % count(self::FIRST_NAMES)],
            self::LAST_NAMES[$n % count(self::LAST_NAMES)],
        ];
    }
}