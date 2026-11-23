<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Garage extends Model
{
    // Solo estos campos pueden asignarse masivamente
    // `user_id` NO está: el dueño lo fija el servidor, nunca el formulario.
    protected $fillable = [
        'titulo', 'descripcion', 'direccion', 'latitud', 'longitud',
        'precio', 'contacto_nombre', 'contacto_telefono', 'disponible',
    ];

    protected function casts(): array
    {
        return [
            'latitud'    => 'float',
            'longitud'   => 'float',
            'precio'     => 'integer',
            'disponible' => 'boolean',
        ];
    }

    // Relaciones

    // Dueño de la cochera (N:1)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(Foto::class);
    }

    public function caracteristicas(): BelongsToMany
    {
        return $this->belongsToMany(Caracteristica::class);
    }

    // Accessors

    protected function precioFormateado(): Attribute
    {
        return Attribute::make(
            get: fn () => '$ ' . number_format($this->precio, 0, ',', '.') . ' / mes'
        );
    }

    // Scopes

    #[Scope]
    protected function disponibles(Builder $query): void
    {
        $query->where('disponible', true);
    }

    /*
      Cocheras dentro de un radio (km) alrededor de un punto, ordenadas por distancia.
      Fórmula de Haversine en sql. Los valores van como parámetros
      vinculados (?), nunca concatenados: evita SQL Injection
     
      Agrega la columna calculada `distancia_km` a cada resultado. */
    #[Scope]
    protected function cercanas(Builder $query, float $lat, float $lng, float $radioKm): void
    {
        $haversine = '(6371 * acos(least(1, '
            . 'cos(radians(?)) * cos(radians(latitud)) * cos(radians(longitud) - radians(?)) '
            . '+ sin(radians(?)) * sin(radians(latitud)))))';

        $query->select('garages.*')
            ->selectRaw("$haversine AS distancia_km", [$lat, $lng, $lat])
            ->whereRaw("$haversine <= ?", [$lat, $lng, $lat, $radioKm])
            ->orderBy('distancia_km');
    }

    //Esto es lo de wsp

    public function getWhatsappUrlAttribute(): ?string
    {  
     $n = preg_replace('/\D/', '', $this->contacto_telefono);   // solo dígitos
     if (str_starts_with($n, '54')) $n = substr($n, 2);         // quita el 54 
     $n = ltrim($n, '0');                                       // quita el 0 del codigo de area
     $n = preg_replace('/^(\d{2,4})15/', '$1', $n);             // quita el 15 de los celus
        if (!str_starts_with($n, '9')) $n = '9' . $n;              // pone 9 poara celus

        if (strlen($n) < 10) return null;                          // número inválido: sin botón

     $texto = "Hola {$this->contacto_nombre}, vi tu cochera \"{$this->titulo}\" en GarageMap.";
     return 'https://wa.me/54' . $n . '?text=' . rawurlencode($texto);
    }
}


