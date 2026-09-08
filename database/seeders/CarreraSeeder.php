<?php

namespace Database\Seeders;

use App\Models\Carrera;
use Illuminate\Database\Seeder;

class CarreraSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Enfermería',
            'Tecnicatura Superior en Preparación Física',
            'Tecnicatura Superior en Cocinas Regionales y Cultura Alimentaria',
            'Tecnicatura Superior en Prótesis Dental',
            'Tecnicatura Superior en Esterilización',
            'Tecnicatura Superior en Farmacia',
            'Tecnicatura Superior en Desarrollo de Software',
            'Tecnicatura Superior en Gestión Jurídica',
            'Tecnicatura Superior en Administración y Gestión de Servicios de Salud',
            'Tecnicatura Superior en Laboratorio en Análisis Clínico',
            'Tecnicatura Superior en Higiene y Seguridad en el Trabajo',
            'Tecnicatura Superior en Periodismo y Nuevas Tecnologías',
            'Tecnicatura Superior en Ciencia de Datos e Inteligencia Artificial',
            'Tecnicatura Superior en Agente Sanitario y Promotor de la Salud',
            'Tecnicatura Superior en Acompañamiento Terapéutico',
            'Tecnicatura Superior en Administración Financiera',
            'Tecnicatura Superior en Hemoterapia',
            'Tecnicatura Superior en Niñez, Adolescencia y Familia',
        ] as $nombre) {
            Carrera::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
