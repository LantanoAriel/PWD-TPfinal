<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios de demostración (datos inventados). Contraseña de ambos: password123
 * Sirven para mostrar que cada vendedor solo puede editar y borrar SUS cocheras.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Vendedor Uno', 'vendedor1@example.com'],
            ['Vendedor Dos', 'vendedor2@example.com'],
        ] as [$nombre, $email]) {
            User::create([
                'name'     => $nombre,
                'email'    => $email,
                'password' => Hash::make('password123'),
            ]);
        }
    }
}
