<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Role::exists()) {
            return;
        }

        $roles = ['admin', 'records', 'sds', 'chief', 'staff'];

        foreach($roles as $role){
            Role::firstOrCreate(['name' => $role]);
        }

        // an array of permission per each role key value pair
        $permissions = [
            'admin' => [
                'create accounts',
                'read accounts',
                'update accounts',
                'delete accounts',
                'create documents',
                'read documents',
                'delete documents',
                'assign documents',
            ],
            'records' => [
                'create documents',
                'read documents',
                'delete documents',
                'assign documents',
            ],
            'sds' => [
                'read documents',
                'assign documents',
            ],
            'chief' => [
                'read documents',
                'assign documents',
            ],
            'staff' => [
                'read documents',
            ]
        ];

        $allPermissions = collect($permissions)->flatten()->unique();

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach($permissions as $role => $arr){
            $role = Role::findByName($role);
            $role->syncPermissions($arr); 
        }
    }
}
