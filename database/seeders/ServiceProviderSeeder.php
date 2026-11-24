<?php

namespace Database\Seeders;

use App\Models\PatupaaCatalogItem;
use App\Models\ServiceOrder;
use App\Models\ServiceProvider;
use Illuminate\Database\Seeder;

class ServiceProviderSeeder extends Seeder
{
    public function run(): void
    {
        $default = ServiceProvider::ensureDefault();

        PatupaaCatalogItem::query()
            ->whereNull('provider_id')
            ->update(['provider_id' => $default->id]);

        ServiceOrder::query()
            ->whereNull('provider_id')
            ->update(['provider_id' => $default->id]);
    }
}
