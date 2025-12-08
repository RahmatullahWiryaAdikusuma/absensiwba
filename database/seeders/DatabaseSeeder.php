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
  
        $roleGM       = Role::firstOrCreate(['name' => 'general_manager']);
        $roleKeuangan = Role::firstOrCreate(['name' => 'keuangan']);
        $roleAdmin    = Role::firstOrCreate(['name' => 'admin']);
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
 
        $gm = User::firstOrCreate(
            ['email' => 'gm@kantor.com'],
            [
                'name' => 'Bapak GM',
                'password' => Hash::make('password123'),
            ]
        );
        $gm->syncRoles([$roleGM]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@kantor.com'],
            [
                'name' => 'Admin Sistem',
                'password' => Hash::make('password123'),
            ]
        );
        $admin->syncRoles([$roleAdmin]);

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