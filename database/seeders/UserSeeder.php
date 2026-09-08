<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@iesnuevohorizonte.com'],
            [
                'name' => 'Administración IES',
                'password' => Hash::make('Admin123!'),
                'rol' => 'administrador',
                'dni' => '00000000',
            ]
        );

        $carreraUno = Carrera::where('nombre', 'Tecnicatura Superior en Desarrollo de Software')->first();
        $carreraDos = Carrera::where('nombre', 'Enfermería')->first();

        User::updateOrCreate(
            ['email' => 'marcela.sosa@iesnuevohorizonte.com'],
            [
                'name' => 'Marcela Sosa',
                'password' => Hash::make('Docente123!'),
                'rol' => 'docente',
                'dni' => '30111222',
                'telefono' => '3884000001',
                'carrera_id' => $carreraUno?->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'julian.torres@iesnuevohorizonte.com'],
            [
                'name' => 'Julián Torres',
                'password' => Hash::make('Docente123!'),
                'rol' => 'docente',
                'dni' => '30333444',
                'telefono' => '3884000002',
                'carrera_id' => $carreraDos?->id,
            ]
        );
    }
}
