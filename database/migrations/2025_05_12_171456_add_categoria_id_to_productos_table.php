<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // 1) Añadimos la columna como FK
            $table->foreignId('categoria_id')
                  ->after('descripcion')
                  ->constrained('categorias')
                  ->onDelete('cascade');

            // 2) (Opcional) Si no la necesitas, elimina la columna 'categoria'
            $table->dropColumn('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Restaurar la columna antigua
            $table->string('categoria')->after('precio');
            // Eliminar la foreign key y columna nueva
            $table->dropForeign(['categoria_id']);
            $table->dropColumn('categoria_id');
        });
    }
};
