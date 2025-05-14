<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }

    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Recréala como decimal si necesitas revertir
            $table->decimal('price', 10, 2)->after('subtotal');
        });
    }
};
