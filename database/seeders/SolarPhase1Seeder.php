<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Lead;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SolarPhase1Seeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DealerSeeder::class,
            UserSeeder::class,
            CustomerSeeder::class,
            ProductCategorySeeder::class,
        ]);

        $customers = Customer::query()->get();
        $dealers = Dealer::query()->get();
        $provinces = Province::query()
            ->whereNull('parent_id')
            ->pluck('name')
            ->values();

        if ($customers->isEmpty() || $dealers->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 20; $i++) {
            Lead::query()->updateOrCreate(
                ['code' => 'L' . date('Ymd') . strtoupper(Str::random(4))],
                [
                    'customer_id' => $customers->random()->id,
                    'dealer_id' => $dealers->random()->id,
                    'status' => ['new', 'contacting', 'won', 'lost'][rand(0, 3)],
                    'source' => 'Facebook Ads',
                    'province_name' => $provinces->isNotEmpty()
                        ? $provinces[$i % $provinces->count()]
                        : null,
                    'estimated_value' => rand(50000000, 200000000),
                    'assigned_at' => now()->subDays(rand(1, 10)),
                ]
            );
        }
    }
}
