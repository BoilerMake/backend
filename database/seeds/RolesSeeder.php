<?php

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
        $hacker = new App\Models\Role();
		$hacker->name         = 'hacker';
		$hacker->save();

		$sponsor = new App\Models\Role();
		$sponsor->name         = 'sponsor';
		$sponsor->save();

		$exec = new App\Models\Role();
		$exec->name         = 'exec';
		$exec->save();

		$admin = new App\Models\Role();
		$admin->name         = 'admin';
		$admin->save();
    }
}
