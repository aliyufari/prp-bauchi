<?php

namespace Database\Seeders;

use App\Enums\EducationStatus;
use App\Enums\Role;
use App\Traits\SeedsUsers;
use Illuminate\Database\Seeder;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    use SeedsUsers;

    public function run(): void
    {
        $this->requireRoles();

        $admins = config('admin.super_admins', []);

        foreach ($admins as $index => $admin) {
            if (blank($admin['email'] ?? null) || blank($admin['password'] ?? null)) {
                throw new RuntimeException(sprintf(
                    'Super administrator %d is not configured. Set SUPER_ADMIN_%d_EMAIL and SUPER_ADMIN_%d_PASSWORD in your environment before seeding.',
                    $index + 1,
                    $index + 1,
                    $index + 1,
                ));
            }

            $user = $this->makeUser($admin['email'], Role::SUPER_ADMIN, [
                'phone' => $this->phone(1, $index + 1),
                'occupation' => 'System Administrator',
                'education_status' => EducationStatus::MSC->value,
                'training_status' => true,
            ], [$admin['first_name'], $admin['last_name']], $admin['password'], sync: true);

            $this->command?->info("Super admin ready: {$user->email}");
        }
    }
}
