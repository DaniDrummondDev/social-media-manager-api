<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = '00000000-0000-4000-a000-000000000101';

        $user = DB::table('users')->where('email', 'admin@demo.com')->first();

        if ($user === null) {
            return;
        }

        DB::table('platform_admins')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'id' => $adminId,
                'role' => 'super_admin',
                'permissions' => json_encode([]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
