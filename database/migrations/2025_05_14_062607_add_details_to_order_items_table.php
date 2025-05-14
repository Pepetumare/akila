<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Usamos product_id en lugar de producto_id
            $table->string('nombre')
                  ->after('product_id');
            $table->integer('unidades')
                  ->default(1)
                  ->after('nombre');
            $table->decimal('precio_base', 10, 2)
                  ->after('unidades');
            $table->decimal('subtotal', 10, 2)
                  ->after('precio_base');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            //
        });
    }
};
