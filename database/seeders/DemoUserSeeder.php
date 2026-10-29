<?php

namespace Database\Seeders;

use App\Enums\AspirantOffice;
use App\Enums\ConstituencyType;
use App\Enums\CoordinatorType;
use App\Enums\EducationStatus;
use App\Enums\PartyAgentType;
use App\Enums\Role;
use App\Enums\StakeholderType;
use App\Enums\SupervisoryAgentType;
use App\Models\Constituency;
use App\Support\Jurisdiction;
use App\Support\LocationChain;
use App\Traits\SeedsUsers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoUserSeeder extends Seeder
{
    use SeedsUsers;

    private const PER_ROLE = 10;

    private const JOBS = ['Teacher', 'Trader', 'Farmer', 'Civil Servant', 'Driver', 'Tailor', 'Nurse', 'Mechanic', 'Student', 'Business Owner'];

    private const ORGANISATIONS = ['Emirate Council', 'Bauchi Youth Forum', 'Women Development Initiative', 'Chamber of Commerce', 'Council of Imams'];

    private const INSTITUTIONS = ['INEC', 'INEC', 'State Electoral Office', 'INEC', 'Local Government Electoral Committee'];

    /** @var array<string, array<int, Constituency>> constituencies grouped by type */
    private array $constituencies = [];

    public function run(): void
    {
        $this->requireRoles();
        $this->loadPools();
        $this->loadConstituencies();
        $this->warnAboutBrokenHierarchy();

        $this->seedAdmin();
        $this->seedMembers();
        $this->seedCoordinators();
        $this->seedPartyAgents();
        $this->seedReturningOfficers();
        $this->seedAspirants();
        $this->seedStakeholders();
        $this->seedSupervisoryAgents();
    }

    private function seedAdmin(): void
    {
        $this->makeUser('admin@prp.test', Role::ADMIN, [
            'phone' => $this->phone(2, 1),
            'occupation' => 'Administrator',
            'education_status' => EducationStatus::BSC->value,
            'training_status' => true,
        ]);
    }

    private function seedMembers(): void
    {
        for ($i = 1; $i <= self::PER_ROLE; $i++) {
            $this->makeUser("member{$i}@prp.test", Role::MEMBER, [
                'phone' => $this->phone(3, $i),
                'gender' => $this->gender($i),
                'occupation' => self::JOBS[$i - 1],
                'education_status' => EducationStatus::BSC->value,
                'training_status' => $i % 2 === 0,
                'mentor_status' => $i % 3 === 0,
                'mentor_name' => $i % 3 === 0 ? 'Ibrahim Musa' : null,
                'application_id' => sprintf('PRP%06d', $i),
            ] + $this->at('ward', $i));
        }
    }

    /** Each coordinator holds only the location node of their own level. */
    private function seedCoordinators(): void
    {
        $types = $this->leveledTypes(CoordinatorType::class);

        for ($i = 1; $i <= self::PER_ROLE; $i++) {
            $t = $types[($i - 1) % count($types)];

            $user = $this->makeUser("coordinator{$i}@prp.test", Role::COORDINATOR, [
                'phone' => $this->phone(4, $i),
                'gender' => $this->gender($i),
                'occupation' => 'Civil Servant',
                'education_status' => EducationStatus::BSC->value,
                'training_status' => true,
            ] + $this->at($t['level'], $i));

            $user->coordinator()->updateOrCreate(['user_id' => $user->id], ['type' => $t['type']]);
        }
    }

    /** Even-numbered agents are deployed to a polling unit; the rest sit at ward level. */
    private function seedPartyAgents(): void
    {
        $types = PartyAgentType::cases();

        for ($i = 1; $i <= self::PER_ROLE; $i++) {
            $deployed = $i % 2 === 0 && ! empty($this->pools['pu']);

            $user = $this->makeUser("agent{$i}@prp.test", Role::PARTY_AGENT, [
                'phone' => $this->phone(5, $i),
                'gender' => $this->gender($i),
                'education_status' => EducationStatus::SECONDARY->value,
                'training_status' => $i % 3 !== 0,
            ] + $this->at($deployed ? 'pu' : 'ward', $i));

            $user->partyAgent()->updateOrCreate(['user_id' => $user->id], ['type' => $types[($i - 1) % count($types)]->value]);
        }
    }

    /** Access is scoped to the LGA, so each officer is placed at an LGA and posted there. */
    private function seedReturningOfficers(): void
    {
        for ($i = 1; $i <= self::PER_ROLE; $i++) {
            $lga = $this->spread('lga', $i);

            $user = $this->makeUser("officer{$i}@prp.test", Role::RETURNING_OFFICER, [
                'phone' => $this->phone(6, $i),
                'gender' => $this->gender($i),
                'education_status' => EducationStatus::MSC->value,
            ] + $this->place('lga', $lga));

            $user->returningOfficer()->updateOrCreate(['user_id' => $user->id], [
                'institution' => self::INSTITUTIONS[($i - 1) % count(self::INSTITUTIONS)],
                'posting_location' => $lga?->name ?? 'Bauchi',
            ]);
        }
    }

    /** Aspirant #1 is the governorship candidate; the rest spread across other offices. */
    private function seedAspirants(): void
    {
        if (! $this->constituencies) {
            $this->command?->warn('Skipped aspirants: no constituencies. Run ConstituencySeeder first.');

            return;
        }

        $offices = array_values(array_filter(AspirantOffice::cases(), fn ($c) => $c !== AspirantOffice::GOVERNOR));

        for ($i = 1; $i <= self::PER_ROLE; $i++) {
            $isGovernor = $i === 1;
            $office = $isGovernor ? AspirantOffice::GOVERNOR : $offices[($i - 2) % count($offices)];

            $user = $this->makeUser("aspirant{$i}@prp.test", Role::ASPIRANT, [
                'phone' => $this->phone(7, $i),
                'gender' => $this->gender($i),
                'education_status' => EducationStatus::MSC->value,
            ] + $this->at('state', $i), $isGovernor ? ['Governorship', 'Candidate'] : null);

            $user->aspirant()->updateOrCreate(['user_id' => $user->id], [
                'constituency_id' => $this->constituencyFor($office, $i)->id,
                'office' => $office->value,
                'title' => $isGovernor ? 'Governorship Candidate, Bauchi State' : null,
                'vision' => $isGovernor ? 'A prosperous, secure, and inclusive Bauchi State for all.' : null,
                'mission' => $isGovernor ? 'To deliver transparent governance, economic growth, and equal opportunity across all 20 LGAs.' : null,
                'overall_progress' => $isGovernor ? 62 : 0,
                'total_supporters' => $isGovernor ? 48210 : 0,
                'supporters_growth_this_week' => $isGovernor ? 1340 : 0,
            ]);
        }
    }

    private function seedStakeholders(): void
    {
        $types = StakeholderType::cases();

        for ($i = 1; $i <= self::PER_ROLE; $i++) {
            $user = $this->makeUser("stakeholder{$i}@prp.test", Role::STAKEHOLDER, [
                'phone' => $this->phone(8, $i),
                'gender' => $this->gender($i),
                'education_status' => EducationStatus::BSC->value,
            ] + $this->at('ward', $i));

            $user->stakeholder()->updateOrCreate(['user_id' => $user->id], [
                'type' => $types[($i - 1) % count($types)]->value,
                'organization' => self::ORGANISATIONS[($i - 1) % count(self::ORGANISATIONS)],
                'designation' => 'Chairman',
            ]);
        }
    }

    private function seedSupervisoryAgents(): void
    {
        $types = $this->leveledTypes(SupervisoryAgentType::class);

        for ($i = 1; $i <= self::PER_ROLE; $i++) {
            $t = $types[($i - 1) % count($types)];

            $user = $this->makeUser("supervisor{$i}@prp.test", Role::SUPERVISORY_AGENT, [
                'phone' => $this->phone(9, $i),
                'gender' => $this->gender($i),
                'education_status' => EducationStatus::BSC->value,
            ] + $this->at($t['level'], $i));

            $user->supervisoryAgent()->updateOrCreate(['user_id' => $user->id], [
                'type' => $t['type'],
                'agents_supervised' => 3 + ($i * 5) % 13,
            ]);
        }
    }

    /* ------------------------------------------------------------------ */

    /**
     * Enum cases that grant access (their value names a location level), highest level first,
     * so the first coordinator or supervisor is the state-level one.
     *
     * @return array<int, array{type: string, level: ?string}>
     */
    private function leveledTypes(string $enum): array
    {
        $types = collect($enum::cases())
            ->map(fn ($c) => ['type' => $c->value, 'level' => Jurisdiction::levelFor($c->value)])
            ->filter(fn ($t) => $t['level'])
            ->sortBy(fn ($t) => LocationChain::index($t['level']))
            ->values()
            ->all();

        if (! $types) {
            $this->command?->warn("No {$enum} case contains STATE, ZONE, LGA or WARD, so those users will have no access.");

            return [['type' => $enum::cases()[0]->value, 'level' => null]];
        }

        return $types;
    }

    private function loadConstituencies(): void
    {
        $this->constituencies = Constituency::all()
            ->groupBy(fn ($c) => $c->type instanceof \BackedEnum ? $c->type->value : (string) $c->type)
            ->map(fn ($group) => $group->values()->all())
            ->all();
    }

    /** Matches an aspirant's office to a constituency of the right type. */
    private function constituencyFor(AspirantOffice $office, int $i): Constituency
    {
        $type = match ($office) {
            AspirantOffice::SENATE => ConstituencyType::SENATORIAL,
            AspirantOffice::HOUSE_OF_REPS => ConstituencyType::HOUSE_OF_REPRESENTATIVES,
            AspirantOffice::HOUSE_OF_ASSEMBLY => ConstituencyType::STATE_ASSEMBLY,
            AspirantOffice::CHAIRMAN => ConstituencyType::CHAIRMANSHIP,
            AspirantOffice::COUNCILLOR => ConstituencyType::COUNCILLOR,
            default => ConstituencyType::GOVERNORSHIP,
        };

        $list = $this->constituencies[$type->value] ?? array_merge(...array_values($this->constituencies));

        return $list[$i % count($list)];
    }

    /** The observer can only fill parents that exist, so warn about unlinked location tables. */
    private function warnAboutBrokenHierarchy(): void
    {
        foreach ([['zones', 'state_id'], ['lgas', 'zone_id'], ['wards', 'lga_id'], ['pus', 'ward_id']] as [$table, $column]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (! Schema::hasColumn($table, $column)) {
                $this->command?->warn("{$table}.{$column} does not exist: run the parent-link migration.");
            } elseif (DB::table($table)->whereNull($column)->exists()) {
                $this->command?->warn("Some {$table} rows have no {$column}: jurisdiction filtering will miss them.");
            }
        }
    }
}