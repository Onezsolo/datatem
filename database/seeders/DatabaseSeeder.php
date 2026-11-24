<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(ServiceProviderSeeder::class);

        if (app()->environment('local', 'testing') || filter_var(env('SEED_DEMO_CATALOG', false), FILTER_VALIDATE_BOOL)) {
            $this->call(ShopCatalogSeeder::class);
        }
    }
}
