<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tabla pivote N:N. Nombre por convención: singulares en orden alfabético.
    public function up(): void
    {
        Schema::create('caracteristica_garage', function (Blueprint $table) {
            $table->foreignId('caracteristica_id')
                ->constrained('caracteristicas')
                ->cascadeOnDelete();
            $table->foreignId('garage_id')
                ->constrained('garages')
                ->cascadeOnDelete();
            $table->primary(['caracteristica_id', 'garage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caracteristica_garage');
    }
};
