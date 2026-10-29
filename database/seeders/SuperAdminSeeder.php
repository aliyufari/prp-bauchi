<?php

namespace Database\Seeders;

use App\Enums\EducationStatus;
use App\Enums\Role;
use App\Traits\SeedsUsers;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    use SeedsUsers;

    /** Initial credentials. Change the passwords after first login. */
    private const ADMINS = [
        ['first_name' => 'Aliyu', 'last_name' => 'Sadisu', 'email' => 'aslere00005@gmail.com', 'password' => 'Aslere00005'],
        ['first_name' => 'Aliyu', 'last_name' => 'Abubakar', 'email' => 'aliyufari@gmail.com', 'password' => 'Fari@3031.'],
    ];

    public function run(): void
    {
        $this->requireRoles();

        foreach (self::ADMINS as $i => $admin) {
            $user = $this->makeUser($admin['email'], Role::SUPER_ADMIN, [
                'phone' => $this->phone(1, $i + 1),
                'occupation' => 'System Administrator',
                'education_status' => EducationStatus::MSC->value,
                'training_status' => true,
            ], [$admin['first_name'], $admin['last_name']], $admin['password'], sync: true);

            $this->command?->info("Super admin ready: {$user->email}");
        }
    }
}