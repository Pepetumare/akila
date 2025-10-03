<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = Schema::getConnection()->getDriverName();

        if ($connection === 'sqlite') {
            Schema::table('order_items', function (Blueprint $table) {
                $table->decimal('precio_unit_tmp', 10, 2)->nullable()->after('unidades');
                $table->decimal('total_tmp', 10, 2)->nullable()->after('precio_unit_tmp');
            });

            DB::table('order_items')->update([
                'precio_unit_tmp' => DB::raw('precio_unit'),
                'total_tmp'       => DB::raw('total'),
            ]);

            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn(['precio_unit', 'total']);
            });

            Schema::table('order_items', function (Blueprint $table) {
                $table->renameColumn('precio_unit_tmp', 'precio_unit');
                $table->renameColumn('total_tmp', 'total');
            });

            return;
        }

        DB::statement('ALTER TABLE order_items MODIFY COLUMN precio_unit DECIMAL(10,2)');
        DB::statement('ALTER TABLE order_items MODIFY COLUMN total DECIMAL(10,2)');
    }

    public function down(): void
    {
        $connection = Schema::getConnection()->getDriverName();

        if ($connection === 'sqlite') {
            Schema::table('order_items', function (Blueprint $table) {
                $table->decimal('precio_unit_tmp', 10, 0)->nullable()->after('unidades');
                $table->decimal('total_tmp', 10, 0)->nullable()->after('precio_unit_tmp');
            });

            DB::table('order_items')->update([
                'precio_unit_tmp' => DB::raw('precio_unit'),
                'total_tmp'       => DB::raw('total'),
            ]);

            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn(['precio_unit', 'total']);
            });

            Schema::table('order_items', function (Blueprint $table) {
                $table->renameColumn('precio_unit_tmp', 'precio_unit');
                $table->renameColumn('total_tmp', 'total');
            });

            return;
        }

        DB::statement('ALTER TABLE order_items MODIFY COLUMN precio_unit DECIMAL(10,0)');
        DB::statement('ALTER TABLE order_items MODIFY COLUMN total DECIMAL(10,0)');
    }
};
