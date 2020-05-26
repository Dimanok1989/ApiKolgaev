<?php

use Illuminate\Database\Seeder;
use App\UserPermission;

class UserPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $manageUser = new UserPermission();
        $manageUser->name = 'Manage users';
        $manageUser->slug = 'manage-users';
        $manageUser->save();

        $createTasks = new UserPermission();
        $createTasks->name = 'Create Tasks';
        $createTasks->slug = 'create-tasks';
        $createTasks->save();

    }
}
