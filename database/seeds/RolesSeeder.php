<?php

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::firstOrCreate(['name' => 'hacker']);
        Role::firstOrCreate(['name' => 'exec']);
        Role::firstOrCreate(['name' => 'sponsor']);
        Role::firstOrCreate(['name' => 'sponsor-group-1']);
        Role::firstOrCreate(['name' => 'sponsor-group-2']);
        Role::firstOrCreate(['name' => 'sponsor-group-3']);
    }
}
