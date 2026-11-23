<?php

namespace Database\Seeders;

use App\Models\Caracteristica;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración. Direcciones y contactos son inventados.
 * Coordenadas repartidas alrededor del centro de Neuquén capital
 * (cambiar CENTRO_* para demostrar en otra ciudad).
 * Las cocheras se reparten entre los dos usuarios de UserSeeder (alternadas).
 */
class GarageSeeder extends Seeder
{
    private const CENTRO_LAT = -38.9516;
    private const CENTRO_LNG = -68.0591;

    public function run(): void
    {
        $caracteristicas = Caracteristica::pluck('id', 'nombre');
        $usuarios = User::orderBy('id')->get();

        // [titulo, direccion, dLat, dLng, precio, [caracteristicas]]
        $ejemplos = [
            ['Cochera techada en el centro', 'Calle de ejemplo 100', 0.000, 0.000, 95000, ['Techada', 'Portón automático']],
            ['Garage con cámara', 'Calle de ejemplo 250', 0.006, -0.004, 80000, ['Cámara de seguridad', 'Iluminación']],
            ['Estacionamiento al aire libre', 'Calle de ejemplo 380', -0.008, 0.007, 45000, ['Iluminación']],
            ['Cochera doble 24 hs', 'Calle de ejemplo 510', 0.012, 0.010, 130000, ['Techada', 'Acceso 24 hs', 'Cámara de seguridad']],
            ['Cochera con cargador eléctrico', 'Calle de ejemplo 640', -0.014, -0.009, 150000, ['Techada', 'Cargador eléctrico']],
            ['Garage barrio residencial', 'Calle de ejemplo 770', 0.020, -0.015, 60000, ['Portón automático']],
            ['Estacionamiento cerca de la facultad', 'Calle de ejemplo 890', -0.004, -0.018, 70000, ['Iluminación', 'Acceso 24 hs']],
            ['Cochera lejana (fuera de radio corto)', 'Calle de ejemplo 920', 0.045, 0.040, 40000, ['Techada']],
        ];

        foreach ($ejemplos as $i => [$titulo, $direccion, $dLat, $dLng, $precio, $extras]) {
            $dueno = $usuarios[$i % $usuarios->count()];

            $garage = new Garage([
                'titulo'            => $titulo,
                'descripcion'       => 'Publicación de ejemplo para la demostración.',
                'direccion'         => $direccion,
                'latitud'           => self::CENTRO_LAT + $dLat,
                'longitud'          => self::CENTRO_LNG + $dLng,
                'precio'            => $precio,
                'contacto_nombre'   => $dueno->name,
                'contacto_telefono' => '299-0000000',
                'disponible'        => true,
            ]);
            $garage->user_id = $dueno->id; // fuera de $fillable a propósito
            $garage->save();

            $garage->caracteristicas()->sync(
                collect($extras)->map(fn ($n) => $caracteristicas[$n])->all()
            );
        }
    }
}
