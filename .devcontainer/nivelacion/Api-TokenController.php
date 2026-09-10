<?php

// Solucion de la sesion 5: app/Http/Controllers/Api/TokenController.php
//
// El equivalente del AuthController de la sesion 3, para quien no tiene
// navegador: en vez de abrir una sesion con cookie, entrega un token.

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    public function crear(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            // Con que nombre se guarda este token. Sirve para revocar el de un
            // telefono perdido sin tumbar los demas.
            'dispositivo' => ['required'],
        ]);

        $usuario = User::where('email', $datos['email'])->first();

        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            // 422 con el mismo formato que cualquier otra validacion.
            throw ValidationException::withMessages([
                'email' => 'Esas credenciales no coinciden con nuestros registros.',
            ]);
        }

        return [
            // plainTextToken es la UNICA vez que se ve completo: en la tabla
            // queda guardado su hash, igual que una contrasena.
            'token' => $usuario->createToken($datos['dispositivo'])->plainTextToken,
            'usuario' => $usuario->name,
            'rol' => $usuario->rol,
        ];
    }

    public function revocar(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ['mensaje' => 'Token revocado'];
    }
}
