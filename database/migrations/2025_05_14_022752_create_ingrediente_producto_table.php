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
        Schema::create('ingrediente_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->foreignId('ingrediente_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('cantidad_permitida')->nullable(); // 👈 Aquí limitamos repeticiones
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingrediente_producto');
    }
};
