<?php

namespace Database\Seeders;

use Database\Seeders\SushiAkila\Batch02Data;
use Database\Seeders\SushiAkila\MenuBatchImporter;
use Illuminate\Database\Seeder;

class SushiAkilaMenuBatch02Seeder extends Seeder
{
    public function run(): void
    {
        app(MenuBatchImporter::class)->run(Batch02Data::products(), 'batch02');
    }
}
