<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'comentarios')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->renameColumn('comentarios', 'cliente_comentarios');
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'cliente_direccion')) {
                $table->string('cliente_direccion')
                    ->nullable()
                    ->after('cliente_telefono');
            }

            if (! Schema::hasColumn('orders', 'metodo_entrega')) {
                $table->string('metodo_entrega', 20)
                    ->default('pickup')
                    ->after('cliente_direccion');
            }

            if (! Schema::hasColumn('orders', 'zona_delivery')) {
                $table->string('zona_delivery', 20)
                    ->nullable()
                    ->after('metodo_entrega');
            }

            if (! Schema::hasColumn('orders', 'kms_fuera')) {
                $table->unsignedSmallInteger('kms_fuera')
                    ->nullable()
                    ->after('zona_delivery');
            }

            if (! Schema::hasColumn('orders', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)
                    ->default(0)
                    ->after('kms_fuera');
            }

            if (! Schema::hasColumn('orders', 'delivery_cost')) {
                $table->decimal('delivery_cost', 10, 2)
                    ->default(0)
                    ->after('subtotal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_cost')) {
                $table->dropColumn('delivery_cost');
            }

            if (Schema::hasColumn('orders', 'subtotal')) {
                $table->dropColumn('subtotal');
            }

            if (Schema::hasColumn('orders', 'kms_fuera')) {
                $table->dropColumn('kms_fuera');
            }

            if (Schema::hasColumn('orders', 'zona_delivery')) {
                $table->dropColumn('zona_delivery');
            }

            if (Schema::hasColumn('orders', 'metodo_entrega')) {
                $table->dropColumn('metodo_entrega');
            }

            if (Schema::hasColumn('orders', 'cliente_direccion')) {
                $table->dropColumn('cliente_direccion');
            }
        });

        if (Schema::hasColumn('orders', 'cliente_comentarios')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->renameColumn('cliente_comentarios', 'comentarios');
            });
        }
    }
};
