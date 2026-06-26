@extends('template.app')

@section('title', 'Crear Financiera')

@section('content')
<nav class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.companies.index') }}">SaaS Admin</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.companies.index') }}">Financieras</a></li>
        <li class="breadcrumb-item active">Crear</li>
    </ol>
</nav>

<div class="card mb-4">
    <div class="card-header">
        <h4 class="card-title">Registrar Nueva Financiera</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('superadmin.companies.store') }}" enctype="multipart/form-data">
            @csrf
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label required">Nombre de la Financiera</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required placeholder="E.g., CREDYFACIL SOLUCIONES S.A.C">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">RUC</label>
                    <input type="text" class="form-control @error('ruc') is-invalid @enderror" name="ruc" value="{{ old('ruc') }}" placeholder="E.g., 20615044394">
                    @error('ruc')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Dirección Fiscal / Legal</label>
                    <input type="text" class="form-control @error('address') is-invalid @enderror" name="address" value="{{ old('address') }}" placeholder="Dirección comercial">
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Ciudad / Departamento</label>
                    <input type="text" class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city') }}" placeholder="E.g., Piura">
                    @error('city')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Información Registral (Para pie de página y contratos PDF)</label>
                <input type="text" class="form-control @error('registry_info') is-invalid @enderror" name="registry_info" value="{{ old('registry_info') }}" placeholder="E.g., Partida Electrónica N° 11325302 del Registro de Personas Jurídicas de Piura">
                @error('registry_info')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label required">Monto de Seguro Default (S/)</label>
                    <input type="number" step="0.01" class="form-control @error('insurance_amount') is-invalid @enderror" name="insurance_amount" value="{{ old('insurance_amount', '0.00') }}" required>
                    @error('insurance_amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label required">Número Inicial del Pagaré</label>
                    <input type="number" class="form-control @error('number_pagare') is-invalid @enderror" name="number_pagare" value="{{ old('number_pagare', '1') }}" required>
                    @error('number_pagare')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label required">Tipo de cliente por defecto en Contratos</label>
                <select class="form-select @error('client_type_config') is-invalid @enderror" name="client_type_config" required>
                    <option value="Ambos" {{ old('client_type_config') == 'Ambos' ? 'selected' : '' }}>Permitir Ambos (Personal y Grupo)</option>
                    <option value="Personal" {{ old('client_type_config') == 'Personal' ? 'selected' : '' }}>Solo Personal</option>
                    <option value="Grupo" {{ old('client_type_config') == 'Grupo' ? 'selected' : '' }}>Solo Grupo</option>
                </select>
                @error('client_type_config')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label required">Formato de contrato PDF/Word</label>
                <select class="form-select @error('contract_format') is-invalid @enderror" name="contract_format" required>
                    <option value="sv" {{ old('contract_format', 'sv') == 'sv' ? 'selected' : '' }}>Formato SV</option>
                    <option value="credypaita" {{ old('contract_format') == 'credypaita' ? 'selected' : '' }}>Formato CREDYPAITA</option>
                </select>
                @error('contract_format')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Logotipo de la Financiera</label>
                <input type="file" class="form-control @error('logo') is-invalid @enderror" name="logo" accept="image/*">
                <small class="text-muted">Recomendado: imagen PNG con fondo transparente.</small>
                @error('logo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label">Módulos Iniciales Contratados</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($modules as $key => $name)
                        <label class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}" {{ $key === 'seller_contract_delete' ? '' : 'checked' }}>
                            <span class="form-check-label">{{ $name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy icon"></i> Guardar Financiera
                </button>
                <a href="{{ route('superadmin.companies.index') }}" class="btn btn-link text-muted">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
