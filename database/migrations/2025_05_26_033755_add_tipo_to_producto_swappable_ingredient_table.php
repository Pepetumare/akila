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
        Schema::table('producto_swappable_ingredient', function (Blueprint $table) {
            // Asegúrate de importar Blueprint arriba:
            // use Illuminate\Database\Schema\Blueprint;
            $table->string('tipo')->after('ingrediente_id');
        });
    }

    public function down()
    {
        Schema::table('producto_swappable_ingredient', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
