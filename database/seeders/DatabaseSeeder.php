<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Document;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            StatusSeeder::class,
        ]);

        // $roles = Role::all();

        // User::factory()
        //     ->count(10)
        //     ->has(Document::factory()->count(5))
        //     ->create()
        //     ->each(function (User $user) use($roles) {
        //         $user->assignRole($roles->random());
        //     });

        if (User::exists()) {
            $this->command->info('Users already exist. Skipping seeding.');
            return;
        }

        //create a admin user
        $admin = User::firstOrCreate([
            'first_name' => 'Docusphere',
            'last_name' => 'Admin',
            'email' => 'docusphere@admin.com',
            'password' => bcrypt('password'),
            'office' => 'Admin Office',
            'designation' => 'Admin',
            'department' => null,
        ]);
        $admin->email_verified_at = now();
        $admin->save();
        $admin->assignRole('admin');

        // create a records system user
        $records = User::firstOrCreate([
            'first_name' => 'Records',
            'last_name' => 'Office',
            'email' => 'docusphere@records.com',
            'password' => bcrypt('password'),
            'office' => 'Records Office',
            'designation' => 'Records Officer',
            'department' => null,
        ]);
        $records->email_verified_at = now();
        $records->save();
        $records->assignRole('records');

        // create demo accounts for other roles
        $sds = User::firstOrCreate([
            'first_name' => 'SDS',
            'last_name' => 'User',
            'email' => 'docusphere@sds.com',
            'password' => bcrypt('password'),
            'office' => 'SDS Office',
            'designation' => 'Schools Division Superintendent',
            'department' => null,
            'email_verified_at' => now(),
        ]);
        $sds->assignRole('sds');

        $chief = User::firstOrCreate([
            'first_name' => 'Chief',
            'last_name' => 'User',
            'email' => 'docusphere@chief.com',
            'password' => bcrypt('password'),
            'office' => 'Chief Office',
            'designation' => 'Chief Education Program Supervisor',
            'department' => 'Education Program',
            'email_verified_at' => now(),
        ]);
        $chief->assignRole('chief');

        $staff = User::firstOrCreate([
            'first_name' => 'Staff',
            'last_name' => 'User',
            'email' => 'docusphere@staff.com',
            'password' => bcrypt('password'),
            'office' => 'Staff Office',
            'designation' => 'Education Program Specialist',
            'department' => 'Education Program',
            'email_verified_at' => now(),
        ]);
        $staff->assignRole('staff');
        



        // $this->call([
        //     DocumentFileSeeder::class,
        //     DocResponseFileSeeder::class,
        //     DocumentAssignmentSeeder::class,
        //     DocumentTrackingSeeder::class,
        //     DocumentVersionSeeder::class,
        //     DocumentCommentSeeder::class,
        //     AuditLogSeeder::class,
        //     NotificationSeeder::class,
        // ]);
    }
}
