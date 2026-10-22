<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglas_promocion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('codigo');
            $table->string('tipo', 30);
            $table->foreignId('ingrediente_id')->nullable()->constrained('ingredientes')->restrictOnDelete();
            $table->foreignId('opcion_id')->nullable()->constrained('opciones')->restrictOnDelete();
            $table->decimal('valor', 12, 2);
            $table->boolean('activo')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['producto_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglas_promocion');
    }
};
