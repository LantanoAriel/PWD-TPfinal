<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            // Dueño de la publicación. Si se borra el usuario, se borran sus cocheras.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('titulo', 100);
            $table->text('descripcion')->nullable();
            $table->string('direccion', 200);
            // Coordenadas: la parte "geo" de la app. Las carga el mapa (MapLibre).
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->unsignedInteger('precio');           // alquiler mensual en pesos
            $table->string('contacto_nombre', 80);
            $table->string('contacto_telefono', 30);
            $table->boolean('disponible')->default(true);
            $table->timestamps();

            $table->index(['latitud', 'longitud']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
