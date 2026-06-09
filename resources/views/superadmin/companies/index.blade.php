@extends('template.app')

@section('title', 'Financieras (SaaS)')

@section('content')
<nav class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.companies.index') }}">SaaS Admin</a></li>
        <li class="breadcrumb-item active">Financieras</li>
    </ol>
</nav>

@if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
        <div class="d-flex">
            <div>
                <i class="ti ti-check icon alert-icon"></i>
            </div>
            <div>
                {{ session('success') }}
            </div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
@endif

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title">Listado de Financieras</h4>
        <a href="{{ route('superadmin.companies.create') }}" class="btn btn-primary">
            <i class="ti ti-plus icon"></i> Crear Nueva Financiera
        </a>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter text-nowrap datatable">
            <thead>
                <tr>
                    <th>Logo</th>
                    <th>Nombre Comercial</th>
                    <th>RUC</th>
                    <th>Ciudad</th>
                    <th>Estado</th>
                    <th>Módulos y reportes del inicio (click para activar/desactivar)</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($companies as $company)
                    <tr>
                        <td>
                            <div class="avatar avatar-md bg-white border">
                                <img src="{{ asset($company->logo ?: 'assets/images/logo.png') }}" alt="logo" style="max-height: 100%; object-fit: contain;">
                            </div>
                        </td>
                        <td>
                            <div class="font-weight-medium text-dark">{{ $company->name }}</div>
                            <div class="text-muted small">ID: {{ $company->id }} | Pagos default: S/ {{ $company->insurance_amount }}</div>
                        </td>
                        <td>{{ $company->ruc ?: '-' }}</td>
                        <td>{{ $company->city ?: '-' }}</td>
                        <td>
                            <span class="badge bg-{{ $company->status === 1 ? 'success' : 'danger' }}-lt font-weight-medium">
                                {{ $company->status === 1 ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($modules as $key => $name)
                                    @php
                                        $hasPerm = $company->hasPermission($key);
                                    @endphp
                                    <label class="form-check form-check-inline m-0">
                                        <input class="form-check-input permission-checkbox" 
                                               type="checkbox" 
                                               data-company-id="{{ $company->id }}" 
                                               data-module="{{ $key }}"
                                               {{ $hasPerm ? 'checked' : '' }}>
                                        <span class="form-check-label">{{ $name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('superadmin.companies.import', $company->id) }}" class="btn btn-icon btn-info" title="Importar datos Excel">
                                    <i class="ti ti-file-import icon"></i>
                                </a>
                                <a href="{{ route('superadmin.companies.edit', $company->id) }}" class="btn btn-icon btn-primary" title="Editar Financiera">
                                    <i class="ti ti-pencil icon"></i>
                                </a>
                                <form method="POST" action="{{ route('superadmin.companies.toggle-status', $company->id) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    @if($company->status === 1)
                                        <button type="submit" class="btn btn-icon btn-warning" title="Desactivar Financiera">
                                            <i class="ti ti-lock icon"></i>
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-icon btn-success" title="Activar Financiera">
                                            <i class="ti ti-lock-open icon"></i>
                                        </button>
                                    @endif
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Setup dynamic token mapping for jQuery AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    });

    $(document).ready(function() {
        $('.permission-checkbox').on('change', function() {
            var checkbox = $(this);
            var companyId = checkbox.data('company-id');
            var moduleName = checkbox.data('module');
            
            $.ajax({
                url: '{{ route("superadmin.companies.toggle-permission", ":id") }}'.replace(':id', companyId),
                method: 'POST',
                data: {
                    module: moduleName
                },
                success: function(response) {
                    ToastMessage.fire({
                        text: 'Permiso actualizado con éxito.'
                    });
                },
                error: function(err) {
                    checkbox.prop('checked', !checkbox.prop('checked'));
                    ToastError.fire({
                        text: 'Ocurrió un error al actualizar los permisos.'
                    });
                }
            });
        });
    });
</script>
@endsection
