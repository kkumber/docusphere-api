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

        //create a admin user
        $admin = User::create([
            'first_name' => 'Docusphere',
            'last_name' => 'Admin',
            'email' => 'docusphere@admin.com',
            'password' => bcrypt('password'),
            'office' => 'Admin Office',
        ]);
        $admin->email_verified_at = now();
        $admin->save();
        $admin->assignRole('admin');

        // create a records system user
        $records = User::create([
            'first_name' => 'Records',
            'last_name' => 'Office',
            'email' => 'docusphere@records.com',
            'password' => bcrypt('password'),
            'office' => 'Records Office',
        ]);
        $records->email_verified_at = now();
        $records->save();
        $records->assignRole('records');

        // create demo accounts for other roles
        $sds = User::create([
            'first_name' => 'SDS',
            'last_name' => 'User',
            'email' => 'docusphere@sds.com',
            'password' => bcrypt('password'),
            'office' => 'SDS Office',
            'email_verified_at' => now(),
        ]);
        $sds->assignRole('sds');

        $chief = User::create([
            'first_name' => 'Chief',
            'last_name' => 'User',
            'email' => 'docusphere@chief.com',
            'password' => bcrypt('password'),
            'office' => 'Chief Office',
            'email_verified_at' => now(),
        ]);
        $chief->assignRole('chief');

        $staff = User::create([
            'first_name' => 'Staff',
            'last_name' => 'User',
            'email' => 'docusphere@staff.com',
            'password' => bcrypt('password'),
            'office' => 'Staff Office',
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
