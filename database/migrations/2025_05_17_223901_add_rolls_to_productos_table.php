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
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedInteger('rolls_total')->default(0);
            $table->unsignedInteger('rolls_envueltos')->default(0);
            $table->unsignedInteger('rolls_fritos')->default(0);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['rolls_total', 'rolls_envueltos', 'rolls_fritos']);
        });
    }
};
