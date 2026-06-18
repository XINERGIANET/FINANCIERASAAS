@extends('template.app')

@section('title', 'Editar Financiera')

@section('content')
<nav class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.companies.index') }}">SaaS Admin</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.companies.index') }}">Financieras</a></li>
        <li class="breadcrumb-item active">Editar</li>
    </ol>
</nav>

<div class="card mb-4">
    <div class="card-header">
        <h4 class="card-title">Editar Financiera: {{ $company->name }}</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('superadmin.companies.update', $company->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label required">Nombre de la Financiera</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $company->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">RUC</label>
                    <input type="text" class="form-control @error('ruc') is-invalid @enderror" name="ruc" value="{{ old('ruc', $company->ruc) }}">
                    @error('ruc')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Dirección Fiscal / Legal</label>
                    <input type="text" class="form-control @error('address') is-invalid @enderror" name="address" value="{{ old('address', $company->address) }}">
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Ciudad / Departamento</label>
                    <input type="text" class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city', $company->city) }}">
                    @error('city')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Información Registral (Para pie de página y contratos PDF)</label>
                <input type="text" class="form-control @error('registry_info') is-invalid @enderror" name="registry_info" value="{{ old('registry_info', $company->registry_info) }}">
                @error('registry_info')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label required">Monto de Seguro Default (S/)</label>
                    <input type="number" step="0.01" class="form-control @error('insurance_amount') is-invalid @enderror" name="insurance_amount" value="{{ old('insurance_amount', $company->insurance_amount) }}" required>
                    @error('insurance_amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label required">Número Actual de Pagaré</label>
                    <input type="number" class="form-control @error('number_pagare') is-invalid @enderror" name="number_pagare" value="{{ old('number_pagare', $company->number_pagare) }}" required>
                    @error('number_pagare')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label required">Tipo de cliente por defecto en Contratos</label>
                <select class="form-select @error('client_type_config') is-invalid @enderror" name="client_type_config" required>
                    <option value="Ambos" {{ old('client_type_config', $company->client_type_config) == 'Ambos' ? 'selected' : '' }}>Permitir Ambos (Personal y Grupo)</option>
                    <option value="Personal" {{ old('client_type_config', $company->client_type_config) == 'Personal' ? 'selected' : '' }}>Solo Personal</option>
                    <option value="Grupo" {{ old('client_type_config', $company->client_type_config) == 'Grupo' ? 'selected' : '' }}>Solo Grupo</option>
                </select>
                @error('client_type_config')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label required">Formato de contrato PDF/Word</label>
                <select class="form-select @error('contract_format') is-invalid @enderror" name="contract_format" required>
                    <option value="sv" {{ old('contract_format', $company->contract_format ?? 'sv') == 'sv' ? 'selected' : '' }}>Formato SV</option>
                    <option value="credypaita" {{ old('contract_format', $company->contract_format ?? 'sv') == 'credypaita' ? 'selected' : '' }}>Formato CREDYPAITA</option>
                </select>
                @error('contract_format')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Logotipo de la Financiera</label>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="avatar avatar-lg bg-white border">
                        <img src="{{ asset($company->logo ?: 'assets/images/logo.png') }}" alt="logo" style="max-height: 100%; object-fit: contain;">
                    </div>
                    <span class="text-muted small">Logotipo actual</span>
                </div>
                <input type="file" class="form-control @error('logo') is-invalid @enderror" name="logo" accept="image/*">
                <small class="text-muted">Subir una nueva imagen reemplazará la actual. Recomendado: PNG transparente.</small>
                @error('logo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label">Módulos Contratados</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($modules as $key => $name)
                        @php
                            $hasPerm = $company->hasPermission($key);
                        @endphp
                        <label class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}" {{ $hasPerm ? 'checked' : '' }}>
                            <span class="form-check-label">{{ $name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy icon"></i> Guardar Cambios
                </button>
                <a href="{{ route('superadmin.companies.index') }}" class="btn btn-link text-muted">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
