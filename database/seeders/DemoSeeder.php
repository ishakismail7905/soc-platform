<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Fictional demo data only (client UAT condition, Q15). Names follow the wireframes.
 * Every account must change its password at first sign-in. Running it twice changes nothing.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Demo accounts are never seeded in production.');
        }

        $password = (string) config('soc.seed_password');
        if (mb_strlen($password) < 12) {
            throw new RuntimeException('Set SEED_PASSWORD (at least 12 characters) in .env before seeding demo accounts.');
        }

        $clients = collect([['Client A', 'CLA'], ['Client B', 'CLB'], ['Client C', 'CLC']])
            ->map(fn (array $c) => Client::firstOrCreate(['code' => $c[1]], ['name' => $c[0]]));

        $people = [
            ['Demo Super Admin', 'superadmin@demo.test', Role::SuperAdmin, []],
            ['Nadia K.', 'nadia@demo.test', Role::ItManager, []],
            ['Aiman R.', 'aiman@demo.test', Role::SocAnalyst, ['CLA', 'CLC']],
            ['Farah N.', 'farah@demo.test', Role::SocAnalyst, ['CLB']],
            ['Hafiz M.', 'hafiz@demo.test', Role::SocAnalyst, ['CLA']],
            ['Client A Viewer', 'viewer.a@demo.test', Role::ClientViewer, ['CLA']],
            ['Demo Auditor', 'auditor@demo.test', Role::Auditor, []],
        ];

        foreach ($people as [$name, $email, $role, $codes]) {
            if (User::where('email', $email)->exists()) {
                continue; // never overwrite an existing account
            }

            $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->forceFill(['role' => $role, 'is_active' => true, 'must_change_password' => true])->save();
            $user->clients()->sync($clients->whereIn('code', $codes)->pluck('id')->all());

            // The shared demo password can't be kept as anyone's own password.
            DB::table('password_histories')->insert([
                'user_id' => $user->id, 'password' => $user->password, 'created_at' => now(),
            ]);
        }
    }
}
