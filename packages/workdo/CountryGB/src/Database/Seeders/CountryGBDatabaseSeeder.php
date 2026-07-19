<?php

namespace Workdo\CountryGB\Database\Seeders;

use Illuminate\Database\Seeder;

class CountryGBDatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            PermissionTableSeeder::class,
            UKDefaultSettingsSeeder::class,
        ]);
    }
}
