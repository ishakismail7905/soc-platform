<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Only fictional demo data. No default "test@example.com / password" account. */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}