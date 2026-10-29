<?php

namespace Database\Seeders;

use App\Enums\AspirantOffice;
use App\Enums\Role;
use App\Models\Aspirant;
use App\Models\CampaignTeam;
use App\Models\CampaignTeamMember;
use App\Traits\SeedsUsers;
use Illuminate\Database\Seeder;

class CampaignTeamSeeder extends Seeder
{
    use SeedsUsers;

    /** Team name => [description, roles]. The first role in each list is the team lead. */
    private const TEAMS = [
        'Ground Operations' => ['Door-to-door mobilisation and rally logistics', ['Team Lead', 'Field Officer', 'Field Officer', 'Volunteer Coordinator']],
        'Media & Publicity' => ['Press, content and social media', ['Team Lead', 'Media Officer', 'Content Producer', 'Social Media Officer']],
        'Finance & Logistics' => ['Budgets, procurement and transport', ['Team Lead', 'Finance Officer', 'Logistics Officer', 'Procurement Officer']],
    ];

    public function run(): void
    {
        $this->requireRoles();

        $governor = Aspirant::where('office', AspirantOffice::GOVERNOR->value)->first();

        if (! $governor) {
            $this->command?->warn('No governorship aspirant found. Run DemoUserSeeder first.');

            return;
        }

        $n = 0;

        foreach (self::TEAMS as $name => [$description, $roles]) {
            $team = CampaignTeam::updateOrCreate(
                ['aspirant_id' => $governor->id, 'name' => $name],
                ['description' => $description, 'is_active' => true],
            );

            foreach ($roles as $position => $role) {
                $n++;

                $user = $this->makeUser("campaign{$n}@prp.test", Role::CAMPAIGN_TEAM_MEMBER, [
                    'phone' => $this->phone(10, $n),
                    'gender' => $this->gender($n),
                ]);

                CampaignTeamMember::updateOrCreate(['user_id' => $user->id], [
                    'campaign_team_id' => $team->id,
                    'role_in_team' => $role,
                    'is_active' => true,
                    'joined_date' => now()->subDays(10 + $n * 3),
                ]);

                if ($position === 0) {
                    $team->update(['team_lead_id' => $user->id]);
                }
            }
        }
    }
}