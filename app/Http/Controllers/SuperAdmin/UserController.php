<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = [
        'admin' => 'Administrador de Financiera',
        'seller' => 'Asesor / Vendedor',
        'viewer' => 'Visualizador',
        'credit' => 'Creditos',
        'payments' => 'Cobranzas / Pagos',
        'operations' => 'Operaciones',
        'superadmin' => 'Super Administrador del SaaS',
    ];

    private const ROLE_RULE = 'admin,seller,viewer,credit,payments,operations,superadmin';

    public function index()
    {
        $users = User::withoutGlobalScopes()->with('company')->get();
        return view('superadmin.users.index', compact('users'));
    }

    public function create()
    {
        $companies = Company::all();
        $roles = self::ROLES;
        return view('superadmin.users.create_edit', compact('companies', 'roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'document' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'user' => 'required|string|max:255|unique:users,user',
            'password' => 'required|string|min:4',
            'role' => 'required|string|in:' . self::ROLE_RULE,
        ]);

        if ($data['role'] === 'superadmin') {
            $data['company_id'] = null;
        }

        $data['password'] = Hash::make($data['password']);
        $data['state'] = 0;
        $data['deleted'] = 0;

        User::create($data);

        return redirect()->route('superadmin.users.index')->with('success', 'Usuario creado con exito.');
    }

    public function edit($id)
    {
        $user = User::withoutGlobalScopes()->findOrFail($id);
        $companies = Company::all();
        $roles = self::ROLES;
        return view('superadmin.users.create_edit', compact('user', 'companies', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $user = User::withoutGlobalScopes()->findOrFail($id);

        $data = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'document' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'user' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:4',
            'role' => 'required|string|in:' . self::ROLE_RULE,
        ]);

        if ($data['role'] === 'superadmin') {
            $data['company_id'] = null;
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('superadmin.users.index')->with('success', 'Usuario actualizado con exito.');
    }

    public function toggleStatus($id)
    {
        $user = User::withoutGlobalScopes()->findOrFail($id);
        $user->state = $user->state === 0 ? 1 : 0;
        $user->save();

        return redirect()->route('superadmin.users.index')->with('success', 'Estado del usuario actualizado.');
    }
}
