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
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratorio_id')->constrained('laboratorios')->onDelete('cascade');
            $table->foreignId('docente_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('materia_id')->constrained('materias')->onDelete('restrict');
            // ➕ ADICIÓN: Campo indispensable para paralelos de una misma materia
            $table->foreignId('grupo_id')->constrained('grupos')->onDelete('restrict');
            // Lógica de Recurrencia Semestral (Adiós a la fecha fija de "cita")
            $table->integer('dia_semana'); // ISO-8601: 1 = Lunes, 2 = Martes, ..., 7 = Domingo
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->date('fecha_inicio'); // Cuándo inicia el semestre (Ej: 2026-02-02)
            $table->date('fecha_fin');    // Cuándo termina el semestre (Ej: 2026-06-25)

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
