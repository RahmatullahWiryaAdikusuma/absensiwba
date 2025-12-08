<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
       
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $roleKaryawan = Role::firstOrCreate(['name' => 'karyawan']); 

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@kantor.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
            ]
        );
        $superAdmin->assignRole($roleSuperAdmin);

        $permissions = Permission::all();
        if ($permissions->count() > 0) {
            $roleSuperAdmin->syncPermissions($permissions);
        }

        $satpam = User::firstOrCreate(
            ['email' => 'satpam@kantor.com'],
            [
                'name' => 'Asep Satpam',
                'password' => Hash::make('password123'),
            ]
        );
        $satpam->syncRoles([$roleKaryawan]);
    }
}