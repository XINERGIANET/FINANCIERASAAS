@extends('template.app')

@section('title', 'Usuarios Globales (SaaS)')

@section('content')
<nav class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.companies.index') }}">SaaS Admin</a></li>
        <li class="breadcrumb-item active">Usuarios</li>
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
        <h4 class="card-title">Listado de Usuarios</h4>
        <a href="{{ route('superadmin.users.create') }}" class="btn btn-primary">
            <i class="ti ti-plus icon"></i> Crear Nuevo Usuario
        </a>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter text-nowrap datatable">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Documento</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Financiera (Asignada)</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>
                            <div class="font-weight-medium text-dark">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->email ?: 'Sin correo' }}</div>
                        </td>
                        <td><code>{{ $user->user }}</code></td>
                        <td>{{ $user->document }}</td>
                        <td>{{ $user->phone ?: '-' }}</td>
                        <td>
                            @if($user->role === 'superadmin')
                                <span class="badge bg-purple-lt">Super Admin</span>
                            @elseif($user->role === 'admin')
                                <span class="badge bg-blue-lt">Admin Financiera</span>
                            @elseif($user->role === 'seller')
                                <span class="badge bg-green-lt">Asesor</span>
                            @else
                                <span class="badge bg-secondary-lt">{{ ucfirst($user->role) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($user->company)
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar avatar-xs bg-white border">
                                        <img src="{{ asset($user->company->logo ?: 'assets/images/logo.png') }}" alt="" style="max-height: 100%; object-fit: contain;">
                                    </div>
                                    <span>{{ $user->company->name }}</span>
                                </div>
                            @else
                                <span class="text-muted small">Global (Sin financiera)</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $user->state === 0 ? 'success' : 'danger' }}-lt">
                                {{ $user->state === 0 ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('superadmin.users.edit', $user->id) }}" class="btn btn-icon btn-primary" title="Editar Usuario">
                                    <i class="ti ti-pencil icon"></i>
                                </a>
                                <form method="POST" action="{{ route('superadmin.users.toggle-status', $user->id) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    @if($user->state === 0)
                                        <button type="submit" class="btn btn-icon btn-warning" title="Desactivar Usuario">
                                            <i class="ti ti-user-minus icon"></i>
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-icon btn-success" title="Activar Usuario">
                                            <i class="ti ti-user-plus icon"></i>
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
