<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CompanyPermission
{
    public function handle(Request $request, Closure $next, string $module)
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Superadmins can access everything
            if ($user->hasRole('superadmin')) {
                return $next($request);
            }

            // Check if user has a company
            $company = $user->company;
            if (!$company) {
                auth()->logout();
                return redirect()->route('auth.login')->with('message', 'Tu usuario no está asociado a ninguna financiera.');
            }

            // Check company status
            if ($company->status !== 1) {
                auth()->logout();
                return redirect()->route('auth.login')->with('message', 'Tu financiera se encuentra inactiva. Contacta al administrador.');
            }

            // Check module permission
            if (!$company->hasPermission($module)) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => 'Módulo no contratado.'], 403);
                }
                abort(403, 'Esta financiera no tiene contratado este módulo.');
            }
        }

        return $next($request);
    }
}
