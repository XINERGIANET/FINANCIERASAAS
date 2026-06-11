@extends('template.app')

@section('title', 'Importar datos')

@section('content')
<nav class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.companies.index') }}">SaaS Admin</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.companies.index') }}">Financieras</a></li>
        <li class="breadcrumb-item active">Importar datos</li>
    </ol>
</nav>

@if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
        <div>{{ session('success') }}</div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
@endif

@if(session('import_errors'))
    <div class="alert alert-danger" role="alert">
        <strong>No se completó la importación. Revise estos errores:</strong>
        <ul class="mb-0 mt-2">
            @foreach(session('import_errors') as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0">Importar a: {{ $company->name }}</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Suba un Excel con las hojas <strong>CLIENTES</strong> (opcional), <strong>CONTRATOS</strong>, <strong>INTEGRANTES</strong>, <strong>CUOTAS</strong> y <strong>PAGOS</strong>.
                    Los datos quedarán solo en esta financiera.
                </p>

                <form method="POST" action="{{ route('superadmin.companies.import.store', $company->id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label required">Archivo Excel (.xlsx)</label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-upload icon"></i> Importar datos
                        </button>
                        <a href="{{ route('superadmin.import.template') }}" class="btn btn-success">
                            <i class="ti ti-download icon"></i> Descargar plantilla Excel
                        </a>
                        <a href="{{ route('superadmin.companies.index') }}" class="btn btn-link">Volver</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Hojas del Excel</h4>
            </div>
            <div class="card-body">
                <ol class="mb-0 ps-3">
                    <li class="mb-2"><strong>INSTRUCCIONES</strong> — guía (no se importa)</li>
                    <li class="mb-2"><strong>CLIENTES</strong> — datos del cliente (opcional)</li>
                    <li class="mb-2"><strong>CONTRATOS</strong> — préstamos (obligatorio)</li>
                    <li class="mb-2"><strong>INTEGRANTES</strong> — miembros de contratos grupales</li>
                    <li class="mb-2"><strong>CUOTAS</strong> — cronograma histórico</li>
                    <li><strong>PAGOS</strong> — cobros ya realizados</li>
                </ol>
                <hr>
                <p class="small text-muted mb-0">
                    Use el mismo <code>codigo_contrato</code> (ej. CTR-001) en contratos, cuotas y pagos.
                    El asesor debe existir como usuario en esta financiera.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
