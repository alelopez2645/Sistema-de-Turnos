<?php

namespace App\Livewire;

use App\Enums\RolUsuario;
use App\Models\Carrera;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GestionUsuarios extends Component
{
    use WithPagination;

    public ?int $editandoId = null;

    public $name = '';
    public $email = '';
    public $password = '';
    public $rol = 'docente';
    public $dni = '';
    public $telefono = '';
    public $carrera_id = '';

    public string $busqueda = '';
    public ?string $mensaje = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->esAdministrador(), 403);
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->editandoId),
            ],
            'password' => [$this->editandoId ? 'nullable' : 'required', 'min:6'],
            'rol' => ['required', Rule::in(array_column(RolUsuario::cases(), 'value'))],
            'dni' => ['nullable', 'string', 'max:20', Rule::unique('users', 'dni')->ignore($this->editandoId)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'carrera_id' => [
                $this->rol === RolUsuario::DOCENTE->value ? 'required' : 'nullable',
                'nullable',
                'exists:carreras,id',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.unique' => 'Ya existe un usuario con ese correo electrónico.',
            'dni.unique' => 'Ya existe un usuario con ese DNI.',
            'carrera_id.required' => 'Elegí la carrera del docente.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }

    public function editar(int $id): void
    {
        $usuario = User::findOrFail($id);

        $this->editandoId = $usuario->id;
        $this->name = $usuario->name;
        $this->email = $usuario->email;
        $this->password = '';
        $this->rol = $usuario->rol->value;
        $this->dni = $usuario->dni;
        $this->telefono = $usuario->telefono;
        $this->carrera_id = $usuario->carrera_id ?? '';
        $this->mensaje = null;
    }

    public function cancelarEdicion(): void
    {
        $this->reset(['editandoId', 'name', 'email', 'password', 'dni', 'telefono', 'carrera_id']);
        $this->rol = 'docente';
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        if (empty($datos['password'])) {
            unset($datos['password']);
        } else {
            $datos['password'] = Hash::make($datos['password']);
        }

        if ($datos['rol'] !== RolUsuario::DOCENTE->value) {
            $datos['carrera_id'] = null;
        }

        if ($this->editandoId) {
            User::findOrFail($this->editandoId)->update($datos);
            $this->mensaje = 'Usuario actualizado.';
        } else {
            $datos['activo'] = true;
            User::create($datos);
            $this->mensaje = 'Usuario creado.';
        }

        $this->cancelarEdicion();
    }

    public function darDeBaja(int $id): void
    {
        if ($id === auth()->id()) {
            $this->mensaje = 'No podés darte de baja a vos mismo.';

            return;
        }

        $usuario = User::findOrFail($id);

        if ($usuario->esAdministrador() && User::where('rol', RolUsuario::ADMINISTRADOR->value)->where('activo', true)->count() <= 1) {
            $this->mensaje = 'No podés dar de baja al único administrador activo del sistema.';

            return;
        }

        $usuario->update(['activo' => false]);
        $this->mensaje = 'Usuario dado de baja. Ya no puede iniciar sesión, pero se conserva su historial de turnos.';
    }

    public function reactivar(int $id): void
    {
        User::findOrFail($id)->update(['activo' => true]);
        $this->mensaje = 'Usuario reactivado.';
    }

    public function render()
    {
        $usuarios = User::with('carrera')
            ->when($this->busqueda, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->busqueda}%")
                        ->orWhere('email', 'like', "%{$this->busqueda}%");
                });
            })
            ->orderByDesc('activo')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.gestion-usuarios', [
            'usuarios' => $usuarios,
            'carreras' => Carrera::orderBy('nombre')->get(),
        ]);
    }
}
