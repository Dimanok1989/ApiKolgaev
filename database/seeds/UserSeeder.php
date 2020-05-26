<?php

use Illuminate\Database\Seeder;

use App\User;
use App\UserRole;
use App\UserPermission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $developer = UserRole::where('slug','web-developer')->first();
        $manager = UserRole::where('slug', 'project-manager')->first();
        $createTasks = UserPermission::where('slug','create-tasks')->first();
        $manageUsers = UserPermission::where('slug','manage-users')->first();

        $user1 = new User();
        $user1->name = 'Jhon Deo';
        $user1->email = 'jhon@deo.com';
        $user1->password = bcrypt('secret');
        $user1->save();
        $user1->roles()->attach($developer);
        $user1->permissions()->attach($createTasks);

        $user2 = new User();
        $user2->name = 'Mike Thomas';
        $user2->email = 'mike@thomas.com';
        $user2->password = bcrypt('secret');
        $user2->save();
        $user2->roles()->attach($manager);
        $user2->permissions()->attach($manageUsers);

    }
}
