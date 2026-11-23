<?php

namespace Database\Seeders;

use App\Models\Caracteristica;
use Illuminate\Database\Seeder;

class CaracteristicaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Techada',
            'Portón automático',
            'Cámara de seguridad',
            'Iluminación',
            'Cargador eléctrico',
            'Acceso 24 hs',
        ] as $nombre) {
            Caracteristica::create(['nombre' => $nombre]);
        }
    }
}
