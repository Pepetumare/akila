<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetalleToOrderItemsTable extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'detalle')) {
                // usa JSON si tu versión de MySQL lo soporta, de lo contrario TEXT
                $table->json('detalle')->after('total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('detalle');
        });
    }
}
