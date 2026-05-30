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
        Schema::create('restricciones_aplicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratorio_id')->nullable()->constrained('laboratorios')->onDelete('cascade');
            $table->string('nombre_aplicacion');
            $table->string('nombre_proceso');
            $table->string('tipo_restriccion')->default('bloqueo_total');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restricciones_aplicaciones');
    }
};
