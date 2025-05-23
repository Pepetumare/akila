<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenamePrecioBaseInOrderItems extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Renombra la columna si existe…
            if (Schema::hasColumn('order_items', 'precio_base')) {
                $table->renameColumn('precio_base', 'precio_unit');
            }

            // Asegura que sea decimal(10,0) o el tipo que prefieras
            $table->decimal('precio_unit', 10, 0)->change();

            // Índice compuesto (opcional) para analytics
            $table->index(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'precio_unit')) {
                $table->renameColumn('precio_unit', 'precio_base');
            }
            $table->dropIndex(['order_id', 'product_id']);
        });
    }
}
