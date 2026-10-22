<?php

namespace Database\Seeders;

use Database\Seeders\SushiAkila\Batch01Data;
use Database\Seeders\SushiAkila\MenuBatchImporter;
use Illuminate\Database\Seeder;

class SushiAkilaMenuBatch01Seeder extends Seeder
{
    public function run(): void
    {
        app(MenuBatchImporter::class)->run(Batch01Data::products(), 'batch01');
    }
}
