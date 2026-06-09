@extends('template.app')

@section('title', isset($user) ? 'Editar Usuario' : 'Crear Usuario')

@section('content')
<nav class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.companies.index') }}">SaaS Admin</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.users.index') }}">Usuarios</a></li>
        <li class="breadcrumb-item active">{{ isset($user) ? 'Editar' : 'Crear' }}</li>
    </ol>
</nav>

<div class="card mb-4">
    <div class="card-header">
        <h4 class="card-title">{{ isset($user) ? 'Editar Usuario: ' . $user->name : 'Crear Nuevo Usuario' }}</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ isset($user) ? route('superadmin.users.update', $user->id) : route('superadmin.users.store') }}">
            @csrf
            @if(isset($user))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label required">Asociar Financiera</label>
                    <select class="form-select @error('company_id') is-invalid @enderror" name="company_id" id="company_id">
                        <option value="">Global / Super Administrador (Sin financiera)</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id', isset($user) ? $user->company_id : '') == $company->id ? 'selected' : '' }}>
                                {{ $company->name }} (RUC: {{ $company->ruc ?: 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                    @error('company_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label required">Rol</label>
                    <select class="form-select @error('role') is-invalid @enderror" name="role" id="role" required>
                        @foreach($roles as $roleKey => $roleName)
                            <option value="{{ $roleKey }}" {{ old('role', isset($user) ? $user->role : 'seller') == $roleKey ? 'selected' : '' }}>
                                {{ $roleName }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label required">Nombre Completo</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', isset($user) ? $user->name : '') }}" required placeholder="E.g., Juan Pérez">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label required">Documento de Identidad (DNI/RUC)</label>
                    <input type="text" class="form-control @error('document') is-invalid @enderror" name="document" value="{{ old('document', isset($user) ? $user->document : '') }}" required placeholder="Número de DNI">
                    @error('document')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', isset($user) ? $user->phone : '') }}" placeholder="E.g., 987654321">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', isset($user) ? $user->email : '') }}" placeholder="correo@ejemplo.com">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Dirección de Domicilio</label>
                <input type="text" class="form-control @error('address') is-invalid @enderror" name="address" value="{{ old('address', isset($user) ? $user->address : '') }}" placeholder="Dirección del domicilio">
                @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label required">Nombre de Usuario (Login)</label>
                    <input type="text" class="form-control @error('user') is-invalid @enderror" name="user" value="{{ old('user', isset($user) ? $user->user : '') }}" required placeholder="E.g., jperez">
                    @error('user')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label {{ isset($user) ? '' : 'required' }}">Contraseña</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" {{ isset($user) ? '' : 'required' }} placeholder="{{ isset($user) ? 'Dejar en blanco para no cambiar' : 'Contraseña de ingreso' }}">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy icon"></i> {{ isset($user) ? 'Guardar Cambios' : 'Crear Usuario' }}
                </button>
                <a href="{{ route('superadmin.users.index') }}" class="btn btn-link text-muted">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Automatically set company_id to empty and disable choice if role is superadmin
        $('#role').on('change', function() {
            if ($(this).val() === 'superadmin') {
                $('#company_id').val('').trigger('change');
                $('#company_id').attr('disabled', true);
            } else {
                $('#company_id').attr('disabled', false);
            }
        });

        // Trigger change initially to set correct state
        $('#role').trigger('change');
    });
</script>
@endsection
