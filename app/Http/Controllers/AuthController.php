<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

// Autenticación. Registro, ingreso y salida
class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'name'     => ['required', 'string', 'max:80'],
            'email'    => ['required', 'email', 'max:120', 'unique:users,email'],
            // `confirmed` exige un campo password_confirmation igual. 72 es el límite de bcrypt.
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);

        $user = User::create([
            'name'     => $datos['name'],
            'email'    => $datos['email'],
            'password' => Hash::make($datos['password']), // nunca se guarda en texto plano
        ]);

        Auth::login($user);
        $request->session()->regenerate(); // evita fijación de sesión

        return redirect()->route('garages.mias')->with('ok', 'Cuenta creada. Ya podés publicar tus cocheras.');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credenciales, $request->boolean('recordar'))) {
            // Mismo mensaje para email inexistente y clave incorrecta: no revela cuál falló.
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'El email o la contraseña no son correctos.']);
        }

        $request->session()->regenerate();

        // intended(): si lo manda a /login desde una página protegida, vuelve a esa página.
        return redirect()->intended(route('garages.mias'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('garages.index')->with('ok', 'Cerraste sesión.');
    }
}
