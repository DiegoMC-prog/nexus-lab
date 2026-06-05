<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('laboratorios', function (Blueprint $table) {
            $table->unsignedSmallInteger('capacidad')->default(30)->after('activo')
                ->comment('Número máximo de PCs/estaciones que puede tener el laboratorio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratorios', function (Blueprint $table) {
            $table->dropColumn('capacidad');
        });
    }
};
