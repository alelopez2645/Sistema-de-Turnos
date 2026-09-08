<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Importa la planilla de docentes como filas crudas (sin asumir nombres de
 * columna fijos), para poder detectar Nombre / Apellido / Correo de forma
 * flexible en ImportarDocentes::buscarColumna().
 */
class DocentesImport implements ToCollection
{
    public $filas;

    public function collection($rows)
    {
        $this->filas = $rows;
    }
}
