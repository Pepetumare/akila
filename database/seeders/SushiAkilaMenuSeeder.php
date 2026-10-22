<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SushiAkilaMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Add future batches here in photo order. Later batches win on shared slugs.
        DB::transaction(fn () => $this->call([
            SushiAkilaMenuBatch01Seeder::class,
            SushiAkilaMenuBatch02Seeder::class,
            SushiAkilaMenuBatch03Seeder::class,
        ]));
    }
}
