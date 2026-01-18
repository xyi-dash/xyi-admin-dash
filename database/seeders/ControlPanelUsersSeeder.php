<?php

namespace Database\Seeders;

use App\Models\ControlPanelUser;
use Illuminate\Database\Seeder;

class ControlPanelUsersSeeder extends Seeder
{
    public function run(): void
    {
        // hakurei shrine residents
        $rootUsers = [
            ['nickname' => 'WhiteCat', 'server' => 'one', 'created_by' => 'reimu'],
            ['nickname' => 'Exfil_Chidori', 'server' => 'one', 'created_by' => 'reimu'],
        ];

        foreach ($rootUsers as $user) {
            ControlPanelUser::firstOrCreate(
                ['nickname' => $user['nickname'], 'server' => $user['server']],
                ['created_by' => $user['created_by']]
            );
        }
    }
}
