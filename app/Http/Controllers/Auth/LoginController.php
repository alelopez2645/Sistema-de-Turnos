<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function mostrarFormulario()
    {
        return view('auth.login');
    }

    public function iniciarSesion(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credenciales, $request->boolean('recordar'))) {
            return back()
                ->withErrors(['email' => 'Las credenciales ingresadas no son correctas.'])
                ->onlyInput('email');
        }

        if (!Auth::user()->activo) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Tu usuario fue dado de baja. Comunicate con administración.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function cerrarSesion(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
