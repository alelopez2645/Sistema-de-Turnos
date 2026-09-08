<?php

namespace App\Livewire;

use App\Enums\RolUsuario;
use App\Imports\DocentesImport;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ImportarDocentes extends Component
{
    use WithFileUploads;

    public $archivo = null;

    /** @var array<int, array{nombre:string, email:string, estado:string, password:?string}> */
    public array $resultados = [];

    public ?string $error = null;
    public ?string $resumen = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->esAdministrador(), 403);
    }

    protected function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ];
    }

    protected function messages(): array
    {
        return [
            'archivo.mimes' => 'El archivo tiene que ser Excel (.xlsx, .xls) o CSV.',
        ];
    }

    /**
     * Lee el Excel/CSV, detecta las columnas Nombre / Apellido / Correo
     * electrónico (tolerando variaciones de mayúsculas, acentos y orden), y
     * da de alta (o actualiza el nombre de) cada docente encontrado.
     */
    public function importar(): void
    {
        $this->validate();
        $this->error = null;
        $this->resumen = null;
        $this->resultados = [];

        $import = new DocentesImport();

        try {
            Excel::import($import, $this->archivo->getRealPath());
        } catch (\Throwable $e) {
            $this->error = 'No se pudo leer el archivo. Verificá que sea un Excel o CSV válido.';

            return;
        }

        $filas = $import->filas;

        if (!$filas || $filas->isEmpty()) {
            $this->error = 'El archivo está vacío.';

            return;
        }

        $encabezados = $filas->first()->map(fn ($v) => $this->normalizar((string) $v));

        $colNombre = $this->buscarColumna($encabezados, ['nombre']);
        $colApellido = $this->buscarColumna($encabezados, ['apellido']);
        $colEmail = $this->buscarColumna($encabezados, ['correo', 'email', 'mail']);

        if (is_null($colNombre) || is_null($colApellido) || is_null($colEmail)) {
            $this->error = 'No pude identificar las columnas Nombre, Apellido y Correo electrónico en la primera fila del archivo. Revisá los encabezados.';

            return;
        }

        $creados = 0;
        $actualizados = 0;
        $omitidos = 0;

        foreach ($filas->skip(1) as $fila) {
            $nombre = trim((string) ($fila[$colNombre] ?? ''));
            $apellido = trim((string) ($fila[$colApellido] ?? ''));
            $email = trim((string) ($fila[$colEmail] ?? ''));

            if (!$nombre && !$apellido && !$email) {
                continue; // fila vacía, se ignora sin contar como omitida
            }

            $nombreCompleto = trim($nombre . ' ' . $apellido) ?: '(sin nombre)';

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $omitidos++;
                $this->resultados[] = [
                    'nombre' => $nombreCompleto,
                    'email' => $email ?: '—',
                    'estado' => 'omitido: correo inválido o vacío',
                    'password' => null,
                ];

                continue;
            }

            $existente = User::where('email', $email)->first();

            if ($existente) {
                $existente->update(['name' => $nombreCompleto]);
                $actualizados++;
                $this->resultados[] = [
                    'nombre' => $nombreCompleto,
                    'email' => $email,
                    'estado' => 'ya existía (nombre actualizado)',
                    'password' => null,
                ];

                continue;
            }

            $passwordTemporal = Str::password(10, symbols: false);

            User::create([
                'name' => $nombreCompleto,
                'email' => $email,
                'password' => Hash::make($passwordTemporal),
                'rol' => RolUsuario::DOCENTE->value,
                'activo' => true,
            ]);

            $creados++;
            $this->resultados[] = [
                'nombre' => $nombreCompleto,
                'email' => $email,
                'estado' => 'creado',
                'password' => $passwordTemporal,
            ];
        }

        $this->archivo = null;
        $this->resumen = "Creados: {$creados} · Actualizados: {$actualizados} · Omitidos: {$omitidos}";
    }

    protected function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));

        return strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        ]);
    }

    protected function buscarColumna($encabezados, array $posibles): ?int
    {
        foreach ($encabezados as $indice => $encabezado) {
            foreach ($posibles as $posible) {
                if (str_contains($encabezado, $posible)) {
                    return $indice;
                }
            }
        }

        return null;
    }

    public function render()
    {
        return view('livewire.importar-docentes');
    }
}
