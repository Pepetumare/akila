<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFreeExtrasByTypeToProductos extends Migration
{
    public function up()
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->integer('free_extras_Proteínas')->default(0)->after('free_extras');
            $table->integer('free_extras_vegetales')->default(0)->after('free_extras_Proteínas');
        });
    }

    public function down()
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['free_extras_Proteínas', 'free_extras_vegetales']);
        });
    }
}
