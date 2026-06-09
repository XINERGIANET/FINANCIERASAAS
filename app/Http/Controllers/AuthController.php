<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(){
        return view('auth.login');
    }

    public function check(Request $request){
        $credentials = $request->validate([
            'user' => 'required',
            'password' => 'required'
        ]);

        if(Auth::attempt($credentials)){
            // Si el usuario autenticado está inactivo (state == 1) no permitir acceso
            $user = Auth::user();
            if ($user && $user->state == 1) {
                Auth::logout();
                return back()->withErrors([
                    'user' => 'Usuario inactivo'
                ]);
            }

            if ($user->hasRole('superadmin')) {
                $request->session()->regenerate();
                return redirect()->route('superadmin.companies.index');
            }

            if ($user->company && $user->company->status != 1) {
                Auth::logout();
                return back()->withErrors([
                    'user' => 'Tu financiera se encuentra inactiva. Contacta al administrador.'
                ]);
            }

            $request->session()->regenerate();

            return redirect()->intended('/');
        }

        return back()->withErrors([
            'user' => 'Usuario o contraseña incorrecta'
        ]);
    }

    public function logout(Request $request){
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
