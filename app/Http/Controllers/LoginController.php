<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    // Muestra la vista del formulario
    public function mostrarFormulario()
    {
        // Si ya está logueado, lo mandamos a su panel correspondiente[cite: 4]
        if (Auth::check()) {
            return $this->redireccionarSegunRol(Auth::user());
        }
        return view('auth.login');
    }

    // Procesa los datos del POST
    public function procesarLogin(Request $request)
    {
        // Validamos que los campos no vengan vacíos[cite: 4]
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Mapeamos los datos del formulario con las columnas de tu migración[cite: 5]
        $credenciales = [
            'usu_correo_electronico' => $request->email,
            'password' => $request->password 
        ];

        // Auth::attempt verifica el hash de bcrypt automáticamente[cite: 4]
        if (Auth::attempt($credenciales)) {
            $request->session()->regenerate(); // Evita ataques de fijación de sesión
            return $this->redireccionarSegunRol(Auth::user());
        }

        // Si falla, devolvemos a la vista con el error[cite: 4]
        return back()->withErrors([
            'email' => 'Credenciales incorrectas.',
        ])->onlyInput('email');
    }

    public function cerrarSesion(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    // Lógica de redirección inteligente según el rol[cite: 4]
    private function redireccionarSegunRol($usuario)
    {
        if ($usuario->usu_rol === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('dashboard');
    }
}
