<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')->references('id')->on('productos')->restrictOnDelete();
        });
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->foreign('categoria_id')->references('id')->on('categorias')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->foreign('categoria_id')->references('id')->on('categorias')->cascadeOnDelete();
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')->references('id')->on('productos')->cascadeOnDelete();
        });
    }
};
