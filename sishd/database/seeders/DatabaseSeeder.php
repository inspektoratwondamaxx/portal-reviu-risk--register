<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            OpdSeeder::class,
            TahunAnggaranSeeder::class,
            UserSeeder::class,
            ReferenceDataSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
