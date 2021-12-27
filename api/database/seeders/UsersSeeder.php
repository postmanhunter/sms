<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Helper\CryptHelper;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'admin',
            'email' => '2601715616@qq.com',
            'password' => CryptHelper::setPass('123456'),
            'avatar' => 'images/avatar.gif',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
