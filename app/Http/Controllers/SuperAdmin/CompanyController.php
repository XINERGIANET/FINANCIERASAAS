<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompanyController extends Controller
{
    const MODULES = [
        'sellers' => 'Asesores Comerciales',
        'contracts' => 'Contratos',
        'cobranzas' => 'Cobranzas',
        'egresos' => 'Egresos',
        'caja_y_cuentas' => 'Caja y cuentas',
        'traslados' => 'Traslados',
        'metas' => 'Metas',
    ];

    const DASHBOARD_REPORTS = [
        'reporte_cartera_dia' => 'Inicio: Cartera al Día',
        'reporte_cartera_morosa' => 'Inicio: Cartera Morosa',
    ];

    const FEATURES = [
        'contract_pdf' => 'Contratos: PDF de contrato',
    ];

    public static function allPermissionModules(): array
    {
        return array_merge(self::MODULES, self::DASHBOARD_REPORTS, self::FEATURES);
    }

    public function index()
    {
        $companies = Company::all();
        $modules = self::allPermissionModules();
        return view('superadmin.companies.index', compact('companies', 'modules'));
    }

    public function create()
    {
        $modules = self::allPermissionModules();
        return view('superadmin.companies.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'ruc' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'registry_info' => 'nullable|string|max:255',
            'insurance_amount' => 'required|numeric|min:0',
            'number_pagare' => 'required|integer|min:0',
            'client_type_config' => 'required|string|in:Ambos,Personal,Grupo',
            'logo' => 'nullable|image|max:2048',
            'permissions' => 'nullable|array',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->storeCompanyLogo($request->file('logo'));
        } else {
            $data['logo'] = 'assets/images/logo.png';
        }

        $data['permissions'] = $request->input('permissions', []);
        $data['status'] = 1;

        $company = Company::create($data);

        foreach (['Efectivo', 'YAPE'] as $methodName) {
            PaymentMethod::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'name' => $methodName,
                'active' => 1,
            ]);
        }

        $adminLogin = 'admin_' . $company->id;
        $adminPassword = 'Financiera@' . $company->id;

        User::create([
            'company_id' => $company->id,
            'document' => '10000000',
            'name' => 'Administrador ' . $company->name,
            'address' => $company->address,
            'phone' => '900000000',
            'email' => Str::slug($company->name) . '@financiera.local',
            'user' => $adminLogin,
            'password' => Hash::make($adminPassword),
            'role' => 'admin',
            'state' => 0,
            'deleted' => 0,
        ]);

        return redirect()->route('superadmin.companies.index')->with('success', 'Financiera creada con éxito. Usuario admin: ' . $adminLogin . ' / Contraseña: ' . $adminPassword);
    }

    public function edit(Company $company)
    {
        $modules = self::allPermissionModules();
        return view('superadmin.companies.edit', compact('company', 'modules'));
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'ruc' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'registry_info' => 'nullable|string|max:255',
            'insurance_amount' => 'required|numeric|min:0',
            'number_pagare' => 'required|integer|min:0',
            'client_type_config' => 'required|string|in:Ambos,Personal,Grupo',
            'logo' => 'nullable|image|max:2048',
            'permissions' => 'nullable|array',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->storeCompanyLogo($request->file('logo'));
        }

        $data['permissions'] = $request->input('permissions', []);

        $company->update($data);

        return redirect()->route('superadmin.companies.index')->with('success', 'Financiera actualizada con éxito.');
    }

    public function togglePermission(Request $request, Company $company)
    {
        $module = $request->input('module');
        if (!array_key_exists($module, self::allPermissionModules())) {
            return response()->json(['error' => 'Módulo inválido.'], 400);
        }

        $permissions = is_array($company->permissions) ? $company->permissions : [];
        if (in_array($module, $permissions)) {
            $permissions = array_diff($permissions, [$module]);
        } else {
            $permissions[] = $module;
        }

        $company->permissions = array_values($permissions);
        $company->save();

        return response()->json(['success' => true, 'permissions' => $company->permissions]);
    }

    public function toggleStatus(Company $company)
    {
        $company->status = $company->status === 1 ? 0 : 1;
        $company->save();

        return redirect()->route('superadmin.companies.index')->with('success', 'Estado de la financiera actualizado.');
    }

    private function storeCompanyLogo($file): string
    {
        $directory = public_path('assets/images/logos');

        if (!File::exists($directory)) {
            File::ensureDirectoryExists($directory, 0755, true);
        }

        if (!is_writable($directory)) {
            throw ValidationException::withMessages([
                'logo' => 'No se pudo guardar el logo porque la carpeta de destino no tiene permisos de escritura.',
            ]);
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png');
        $filename = time() . '_' . Str::slug($originalName) . '.' . $extension;

        try {
            $file->move($directory, $filename);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'logo' => 'No se pudo guardar el logo. Verifica que la carpeta public/assets/images/logos exista y sea escribible.',
            ]);
        }

        return 'assets/images/logos/' . $filename;
    }
}
