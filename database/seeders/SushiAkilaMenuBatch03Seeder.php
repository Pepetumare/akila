<?php

namespace Database\Seeders;

use Database\Seeders\SushiAkila\Batch03Data;
use Database\Seeders\SushiAkila\MenuBatchImporter;
use Illuminate\Database\Seeder;

class SushiAkilaMenuBatch03Seeder extends Seeder
{
    public function run(): void
    {
        app(MenuBatchImporter::class)->run(Batch03Data::products(), 'batch03', ['Pepino' => 'vegetal'], Batch03Data::groups());
    }
}
