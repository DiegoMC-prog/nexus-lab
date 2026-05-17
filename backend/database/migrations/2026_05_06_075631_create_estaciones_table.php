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
        Schema::create('estaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratorio_id')->constrained('laboratorios')->onDelete('cascade');
            $table->foreignId('estudiante_actual_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hostname', 255);
            $table->string('direccion_mac');
            $table->string('direccion_ip');
            $table->string('so_info');
            $table->string('estado');
            $table->string('version_agente');
            $table->dateTime('ultima_conexion');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estaciones');
    }
};
