<?php

use Illuminate\Database\Seeder;
use App\Models\Role;
class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$a = Role::firstOrCreate(['name' => 'hacker']);
    	$a = Role::firstOrCreate(['name' => 'sponsor']);
    	$a = Role::firstOrCreate(['name' => 'exec']);
    	$a = Role::firstOrCreate(['name' => 'admin']);
    }
}
