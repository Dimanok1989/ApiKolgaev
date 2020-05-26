<?php

use Illuminate\Database\Seeder;
use App\UserRole;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $manager = new UserRole();
        $manager->name = 'Project Manager';
        $manager->slug = 'project-manager';
        $manager->save();

        $developer = new UserRole();
        $developer->name = 'Web Developer';
        $developer->slug = 'web-developer';
        $developer->save();

    }
}
