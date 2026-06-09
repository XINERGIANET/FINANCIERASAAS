<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Exports\ImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\CompanyDataImportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DataImportController extends Controller
{
    public function show(Company $company)
    {
        return view('superadmin.companies.import', compact('company'));
    }

    public function template()
    {
        $name = 'plantilla_importacion_crececonmigo.xlsx';

        return Excel::download(new ImportTemplateExport(), $name);
    }

    public function store(Request $request, Company $company, CompanyDataImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $path = $request->file('file')->getRealPath();
        $result = $importService->import($company->id, $path);

        if (!$result['success']) {
            return back()
                ->withInput()
                ->with('import_errors', $result['errors'])
                ->with('import_stats', $result['stats']);
        }

        return redirect()
            ->route('superadmin.companies.import', $company->id)
            ->with('success', 'Importación completada: '
                . $result['stats']['clientes'] . ' clientes, '
                . $result['stats']['contratos'] . ' contratos, '
                . $result['stats']['cuotas'] . ' cuotas, '
                . $result['stats']['pagos'] . ' pagos.');
    }
}
