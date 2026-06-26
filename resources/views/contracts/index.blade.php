@extends('template.app')

@section('title', 'Contratos')

@section('content')
    <nav class="mb-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Contratos</li>
        </ol>
    </nav>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            <div>{{ session('success') }}</div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
    @endif

    @if (session('import_errors'))
        <div class="alert alert-danger" role="alert">
            <strong>No se completó la importación. Revise estos errores:</strong>
            <ul class="mb-0 mt-2">
                @foreach (session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header">

            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="ti ti-plus icon"></i> Crear nuevo
            </button>
            <button class="btn btn-success ms-2" id="btn-excel">
                <i class="ti ti-file-export icon"></i> Excel
            </button>
            @if (!auth()->user()->hasRole('seller'))
                <button class="btn btn-outline-secondary ms-2" id="btn-export-import-data">
                    <i class="ti ti-file-download icon"></i> Data editable
                </button>
                <a href="{{ route('contracts.import.template') }}" class="btn btn-outline-success ms-2">
                    <i class="ti ti-download icon"></i> Plantilla Excel
                </a>
                <button class="btn btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="ti ti-upload icon"></i> Importar Excel
                </button>
                <button class="btn btn-outline-danger ms-2 d-none" id="btn-bulk-delete">
                    <i class="ti ti-trash icon"></i> Eliminar seleccionados
                </button>
            @endif
        </div>
        <div class="card-body border-bottom">
            <form>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Cliente</label>
                            <input type="text" class="form-control" name="name" value="{{ request()->name }}">
                        </div>
                    </div>
                    @if (!auth()->user()->hasRole('seller'))
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Asesor comercial</label>
                                <select class="form-select" name="seller_id">
                                    <option value="">Seleccionar</option>
                                    @foreach ($sellers as $seller)
                                        <option value="{{ $seller->id }}"
                                            @if ($seller->id == request()->seller_id) selected @endif>{{ $seller->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Inicio del préstamo</label>
                            <input type="date" class="form-control" name="start_date"
                                value="{{ request()->start_date }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Fin del préstamo</label>
                            <input type="date" class="form-control" name="end_date" value="{{ request()->end_date }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Monto seguro</label>
                            <select class="form-select" name="insurance_filter">
                                <option value="">Todos</option>
                                <option value="zero" @if (request()->insurance_filter === 'zero') selected @endif>Solo seguro 0</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary">Filtrar</button>
                <a href="{{ route('contracts.index') }}" class="btn btn-danger">Limpiar</a>
            </form>
        </div>
        <div class="table-responsive" id="contractsTableWrapper">
            <table class="table card-table table-vcenter">
                <thead>
                    <tr>
                        @if (!auth()->user()->hasRole('seller'))
                            <th class="w-1">
                                <input type="checkbox" class="form-check-input" id="check-all-contracts">
                            </th>
                        @endif
                        <th>Cliente/Grupo</th>
                        <th>Asesor C.</th>
                        <th>Monto solicitado</th>
                        <th>Cuotas</th>
                        <th>% de interés</th>
                        <th>interés</th>
                        <th>Monto a pagar</th>
                        <th>Monto seguro</th>
                        <th>Fecha de préstamo</th>
                        <th>Tipo de cuotas</th>
                        <th>Estado</th>
                        <th>Aprob.</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($contracts->count() > 0)
                        @foreach ($contracts as $contract)
                            <tr>
                                @if (!auth()->user()->hasRole('seller'))
                                    <td>
                                        <input type="checkbox" class="form-check-input contract-checkbox" value="{{ $contract->id }}">
                                    </td>
                                @endif
                                <td title="{!! $contract->people() !!}" data-bs-toggle="tooltip" data-bs-html="true">
                                    {{ $contract->client_type == 'Personal' ? $contract->name : $contract->group_name }}
                                </td>
                                <td>{{ optional($contract)->seller->name }}</td>
                                <td>S/ {{ number_format($contract->requested_amount, 1) }}</td>
                                <td>{{ $contract->quotas_number }}</td>
                                <td>{{ $contract->percentage }}%</td>
                                <td>S/ {{ number_format($contract->interest, 1) }}</td>
                                <td>S/ {{ number_format($contract->payable_amount, 1) }}</td>
                                <td>S/ {{ number_format($contract->insurance_amount, 1) }}</td>
                                <td>{{ $contract->date->format('d/m/Y') }}</td>
                                <td>
                                    {{ $contract->quota_type }}
                                </td>
                                <td>
                                    @if ($contract->paid)
                                        <span class="badge bg-success"></span>
                                    @else
                                        <span class="badge bg-danger"></span>
                                    @endif
                                </td>
                                <td>
                                    @if ($contract->approved)
                                        <span class="badge bg-success text-white">SÍ</span>
                                    @else
                                        <span class="badge bg-warning text-dark">NO</span>
                                    @endif
                                </td>
                                <td>

                                    <div class="d-flex gap-2">
                                        @if (auth()->user()->company && auth()->user()->company->hasPermission('contract_pdf'))
                                            <a href="{{ route('contracts.pdfPersonal', $contract) }}" target="_blank"
                                                class="btn btn-primary btn-icon" title="PDF contrato">
                                                <i class="ti ti-file-text icon"></i>
                                            </a>
                                            <a href="{{ route('contracts.wordPersonal', $contract) }}"
                                                class="btn btn-info btn-icon text-white fw-bold" title="Word editable">
                                                W
                                            </a>
                                        @endif
                                        @if (auth()->user()->hasRole('admin', 'operations', 'seller'))
                                            @if (!$contract->approved)
                                                <button class="btn btn-icon btn-success btn-approve"
                                                    data-id="{{ $contract->id }}" title="Aprobar">
                                                    <i class="ti ti-check icon"></i>
                                                </button>
                                            @endif
                                            @php
                                                $hasPaidQuotas = $contract->quotas()->where('paid', '>', 0)->exists() || $contract->paid > 0;
                                            @endphp
                                                <button type="button" class="btn btn-icon btn-warning btn-edit" data-id="{{ $contract->id }}"
                                                title="{{ $hasPaidQuotas ? 'Editar (se validará al guardar si tiene cuotas pagadas)' : 'Editar' }}">
                                                <i class="ti ti-edit icon"></i>
                                            </button>
                                            <button class="btn btn-icon btn-danger btn-delete"
                                                data-id="{{ $contract->id }}">
                                                <i class="ti ti-x icon"></i>
                                            </button>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="{{ auth()->user()->hasRole('seller') ? 13 : 14 }}" align="center">No se han encontrado resultados</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if ($contracts->hasPages())
            <div class="card-footer d-flex align-items-center" id="contractsPagination">
                {{ $contracts->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <div class="modal modal-blur fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <form novalidate id="storeForm" method="POST">
                    <input type="hidden" id="contract_id" name="contract_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear nuevo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 mb-3">
                            <strong>Campos obligatorios:</strong> DNI, Nombre, Teléfono, Dirección y Referencia.
                        </div>
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Tipo de cliente</label>
                                    <select class="form-select" name="client_type" id="client_type">
                                        @php
                                            $configType = auth()->user()->company->client_type_config ?? 'Ambos';
                                        @endphp
                                        @if($configType == 'Ambos' || $configType == 'Personal')
                                            <option value="Personal">Personal</option>
                                        @endif
                                        @if($configType == 'Ambos' || $configType == 'Grupo')
                                            <option value="Grupo">Grupo</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divGroupName" style="display: none">
                                <div class="mb-3">
                                    <label class="form-label required">Nombre de grupo</label>
                                    <input type="text" class="form-control" name="group_name" id="group_name"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divQuantity" style="display: none">
                                <div class="mb-3">
                                    <label class="form-label required">Cantidad</label>
                                    <div class="w-100 btn-group">
                                        <button type="button" class="btn btn-primary w-50"
                                            id="btn-add">Agregar</button>
                                        <button type="button" class="btn btn-danger w-50"
                                            id="btn-remove">Quitar</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divDocument">
                                <div class="mb-3">
                                    <label class="form-label required">DNI</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control ts-document" name="document"
                                            id="document" autocomplete="off">
                                        <button type="button" class="btn btn-primary btn-icon" id="btn-search">
                                            <i class="ti ti-search icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divName">
                                <div class="mb-3">
                                    <label class="form-label required">Nombre</label>
                                    <input type="text" class="form-control" name="name" id="name"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divPhone">
                                <div class="mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" name="phone" id="phone"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divAddress">
                                <div class="mb-3">
                                    <label class="form-label">Dirección</label>
                                    <input type="text" class="form-control" name="address" id="address"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divDepartment">
                                <div class="mb-3">
                                    <label class="form-label">Departamento</label>
                                    <select class="form-select" name="department_id" id="department_id">
                                        <option value="">Seleccionar</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divProvince">
                                <div class="mb-3">
                                    <label class="form-label">Provincia</label>
                                    <select class="form-select" name="province_id" id="province_id">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divDistrict">
                                <div class="mb-3">
                                    <label class="form-label">Distrito</label>
                                    <select class="form-select" name="district_id" id="district_id">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divReference">
                                <div class="mb-3">
                                    <label class="form-label">Referencia</label>
                                    <input type="text" class="form-control" name="reference" id="reference"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divHomeType">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de vivienda</label>
                                    <select class="form-select" name="home_type" id="home_type">
                                        <option value="">Seleccionar</option>
                                        <option value="Propia">Propia</option>
                                        <option value="Alquilada">Alquilada</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divBusinessLine">
                                <div class="mb-3">
                                    <label class="form-label">Rubro de negocio</label>
                                    <input type="text" class="form-control" name="business_line" id="business_line"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divBusinessAddress">
                                <div class="mb-3">
                                    <label class="form-label">Dirección de negocio</label>
                                    <input type="text" class="form-control" name="business_address"
                                        id="business_address" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divBusinessStartDate">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de inicio de negocio</label>
                                    <input type="date" class="form-control" name="business_start_date"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divCivilStatus">
                                <div class="mb-3">
                                    <label class="form-label">Estado civil</label>
                                    <select class="form-select" name="civil_status" id="civil_status">
                                        <option value="">Seleccionar</option>
                                        <option value="Soltero">Soltero</option>
                                        <option value="Casado">Casado</option>
                                        <option value="Divorciado">Divorciado</option>
                                        <option value="Viudo">Viudo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divHusbandName" style="display: none">
                                <div class="mb-3">
                                    <label class="form-label">Nombre de esposo (a)</label>
                                    <input type="text" class="form-control" name="husband_name" id="husband_name"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divHusbandDocument" style="display: none">
                                <div class="mb-3">
                                    <label class="form-label">DNI de esposo (a)</label>
                                    <input type="text" class="form-control" name="husband_document"
                                        id="husband_document" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div id="divGroup" style="display:none">
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label required">DNI</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control ts-document" name="documents[]"
                                                autocomplete="off">
                                            <button type="button" class="btn btn-primary btn-icon btn-group-search">
                                                <i class="ti ti-search icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label required">Nombre</label>
                                        <input type="text" class="form-control" name="names[]" autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label">Dirección</label>
                                        <input type="text" class="form-control" name="addresses[]"
                                            autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label required">DNI</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control ts-document" name="documents[]"
                                                autocomplete="off">
                                            <button type="button" class="btn btn-primary btn-icon btn-group-search">
                                                <i class="ti ti-search icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label required">Nombre</label>
                                        <input type="text" class="form-control" name="names[]" autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label">Dirección</label>
                                        <input type="text" class="form-control" name="addresses[]"
                                            autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Asesor comercial</label>
                                    <select class="form-select" name="seller_id">
                                        <option value="">Seleccionar</option>
                                        @foreach ($sellers as $seller)
                                            <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Monto solicitado</label>
                                    <input type="text" class="form-control" name="requested_amount"
                                        id="requested_amount" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="mb-3">
                                    <label class="form-label required">Número de cuotas</label>
                                    <input type="number" class="form-control" name="months_number" id="months_number"
                                        autocomplete="off" step="1" min="1">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="mb-3">
                                    <label class="form-label required">Tipo de Cuota</label>
                                    <select class="form-select" name="type_quota">
                                        <option value="1">Semanal</option>
                                        <option value="2">Quincenal</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha de préstamo</label>
                                    @if (auth()->user()->hasRole('operations'))
                                        <input type="date" class="form-control" id="date_display" name="date_disabled"
                                            value="{{ now()->format('Y-m-d') }}" autocomplete="off" disabled>
                                        {{-- input hidden para asegurar que la fecha se envíe en el formulario aun cuando el campo esté disabled --}}
                                        <input type="hidden" id="date" name="date" value="{{ now()->format('Y-m-d') }}">
                                    @else
                                        <input type="date" class="form-control" id="date" name="date"
                                            value="{{ now()->format('Y-m-d') }}" autocomplete="off">
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Tasa de interés (%)</label>
                                    <input type="text" class="form-control" name="interest" id="interest"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Monto seguro</label>
                                    <input type="text" class="form-control" name="insurance_cost" id="insurance_cost"
                                        value="0" autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i>
                            Cerrar</button>
                        <button type="submit" class="btn btn-primary" id="btn-save"><i
                                class="ti ti-device-floppy icon"></i> Guardar</button>
                    </div>
                </form>
            </div>
            <!-- Edit Modal was removed, using createModal instead -->  </div>
    </div>

    @if (!auth()->user()->hasRole('seller'))
        <div class="modal modal-blur fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form method="POST" action="{{ route('contracts.import.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Importar Excel de contratos</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <strong>Plantilla soportada:</strong> CLIENTES, CONTRATOS, INTEGRANTES, CUOTAS y PAGOS.
                                Descargue primero la plantilla para respetar los encabezados obligatorios/opcionales.
                            </div>
                            <div class="alert alert-warning">
                                <strong>Edicion segura:</strong> descargue primero la <em>Data editable</em>, corrija el archivo,
                                elimine masivamente los contratos malos y luego vuelva a importarlo.
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Archivo Excel</label>
                                <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls" required>
                                @error('file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <ul class="small text-muted mb-0 ps-3">
                                <li><strong>CONTRATOS</strong> es obligatorio.</li>
                                <li><strong>INTEGRANTES</strong> es obligatorio para contratos tipo Grupo.</li>
                                <li><strong>CUOTAS</strong> y <strong>PAGOS</strong> son opcionales si el sistema puede generarlos.</li>
                            </ul>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn me-auto" data-bs-dismiss="modal">
                                <i class="ti ti-x icon"></i> Cerrar
                            </button>
                            <a href="{{ route('contracts.import.template') }}" class="btn btn-outline-success">
                                <i class="ti ti-download icon"></i> Descargar plantilla
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-upload icon"></i> Importar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="modal modal-blur fade" id="quotasModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cuotas pendientes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Contrato</th>
                                <th>Número</th>
                                <th>Monto</th>
                                <th>Saldo</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody id="tbl-quotas"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="confirmDerivedModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Aviso importante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>El cliente tiene una deuda pendiente de: S/<b id="past-debt"></b></p>
                    <p>El nuevo contrato cuenta con una deuda total de : S/<b id="contract-debt"></b></p>
                    <p>El monto entregado deberá ser de : S/<b id="difference"></b></p>
                    <p class="text-danger" id="warning"></p>
                </div>
                <div class="modal-footer">

                    <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i>
                        Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btn-confirm">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        var totalDebt = 0;
        var isHydratingContractForm = false;

        function normalizeDataValue(v) {
            if (v === undefined || v === null) return '';
            if (typeof v !== 'string') return v;
            v = v.trim();
            if (v === '') return '';
            var low = v.toLowerCase();
            if (low === 'null' || low === 'undefined') return '';
            return v;
        }

        function getBootstrapModal(modalSelector) {
            var modalElement = document.querySelector(modalSelector);
            if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                return null;
            }

            return bootstrap.Modal.getOrCreateInstance(modalElement);
        }

        function openModal(modalSelector) {
            var modalInstance = getBootstrapModal(modalSelector);
            if (modalInstance) {
                modalInstance.show();
                return;
            }

            $(modalSelector).modal('show');
        }

        function closeModal(modalSelector) {
            var modalInstance = getBootstrapModal(modalSelector);
            if (modalInstance) {
                modalInstance.hide();
                return;
            }

            $(modalSelector).modal('hide');
        }

        function setContractDate(dateValue) {
            var normalizedDate = dateValue || '{{ now()->format('Y-m-d') }}';
            $('#date').val(normalizedDate);
            $('#date_display').val(normalizedDate);
        }

        function loadProvinceOptions(departmentId, selectedProvinceId, selectedDistrictId) {
            var $provinceSelect = $('#province_id');
            var $districtSelect = $('#district_id');

            $provinceSelect.html('<option value="">Seleccionar</option>');
            $districtSelect.html('<option value="">Seleccionar</option>');

            if (!departmentId) {
                return;
            }

            $.ajax({
                url: '{{ route('api.provinces') }}',
                method: 'GET',
                data: {
                    department_id: departmentId
                },
                success: function(data) {
                    $.each(data, function(index, province) {
                        $provinceSelect.append('<option value="' + province.id + '">' + province.name + '</option>');
                    });

                    if (selectedProvinceId) {
                        $provinceSelect.val(selectedProvinceId);
                        loadDistrictOptions(selectedProvinceId, selectedDistrictId);
                    }
                },
                error: function() {
                    ToastError.fire({
                        text: 'OcurriÃ³ un error al cargar las provincias'
                    });
                }
            });
        }

        function loadDistrictOptions(provinceId, selectedDistrictId) {
            var $districtSelect = $('#district_id');

            $districtSelect.html('<option value="">Seleccionar</option>');

            if (!provinceId) {
                return;
            }

            $.ajax({
                url: '{{ route('api.districts') }}',
                method: 'GET',
                data: {
                    province_id: provinceId
                },
                success: function(data) {
                    $.each(data, function(index, district) {
                        $districtSelect.append('<option value="' + district.id + '">' + district.name + '</option>');
                    });

                    if (selectedDistrictId) {
                        $districtSelect.val(selectedDistrictId);
                    }
                },
                error: function() {
                    ToastError.fire({
                        text: 'OcurriÃ³ un error al cargar los distritos'
                    });
                }
            });
        }

        function fillPersonalClientFields(data) {
            data = data || {};

            $('#name').prop('readonly', false).val(normalizeDataValue(data.name));
            $('#phone').val(normalizeDataValue(data.phone));
            $('#address').val(normalizeDataValue(data.address));
            $('#reference').val(normalizeDataValue(data.reference));
            $('#business_line').val(normalizeDataValue(data.business_line));
            $('#business_address').val(normalizeDataValue(data.business_address));
            $('[name="business_start_date"]').val(data.business_start_date ? String(data.business_start_date).split('T')[0] : '');
            $('#home_type').val(normalizeDataValue(data.home_type));
            $('#civil_status').val(normalizeDataValue(data.civil_status)).trigger('change');
            $('#husband_name').val(normalizeDataValue(data.husband_name));
            $('#husband_document').val(normalizeDataValue(data.husband_document));

            if (data.district && data.district.province) {
                var province = data.district.province;
                var departmentId = province.department_id || data.district.department_id || '';
                $('#department_id').val(departmentId);
                loadProvinceOptions(departmentId, province.id, data.district.id);
            } else {
                $('#department_id').val('');
                $('#province_id').html('<option value="">Seleccionar</option>');
                $('#district_id').html('<option value="">Seleccionar</option>');
            }
        }

        $(document).ready(function() {
            var queryString = window.location.search;
            var parametros = new URLSearchParams(queryString);

            $('#divPhone label, #divAddress label, #divReference label').addClass('required');

            if (parametros.get('modal') == 'create') {
                openModal('#createModal');
            }

            // Limpiar selects cuando se abre el modal para crear nuevo
            $('#createModal').on('show.bs.modal', function(e) {
                if (e.relatedTarget && !$(e.relatedTarget).hasClass('btn-edit')) {
                    $('#storeForm')[0].reset();
                    $('#contract_id').val('');
                    $('#createModal .modal-title').text('Crear nuevo');
                    $('#client_type').trigger('change');
                    $('#province_id').html('<option value="">Seleccionar</option>');
                    $('#district_id').html('<option value="">Seleccionar</option>');
                    setContractDate();
                }
            });



            // Helper: inicializa TomSelect en un input concreto (evita duplicados)
            function initTomSelect($input) {
                if (!$input || $input.data('ts-initialized')) return;
                var el = $input[0];
                var isMain = $input.is('#document');

                new TomSelect(el, {
                    create: true,
                    maxItems: 1,
                    valueField: 'document',
                    labelField: ['name', 'document'],
                    searchField: ['name', 'document'],
                    copyClassesToDropdown: false,
                    dropdownClass: 'dropdown-menu ts-dropdown',
                    optionClass: 'dropdown-item',
                    hideSelected: true,
                    load: function(query, callback) {
                        $.ajax({
                            url: '{{ route('clients.api') }}?q=' + encodeURIComponent(query),
                            method: 'GET',
                            success: function(data) {
                                callback(data.items);
                            },
                            error: function(err) {
                                console.log(err);
                            }
                        })
                    },
                    render: {
                        item: function(data, escape) {
                            return `<div data-client='${escape(JSON.stringify(data))}'>${escape(data.document)}</div>`;
                        },
                        option: function(data, escape) {
                            return `<div>${escape(data.document)} - ${ data.name ? escape(data.name) : ''}</div>`;
                        },
                        no_results: function(data, escape) {
                            return '<div class="no-results">No se encontraron resultados</div>'
                        },
                        option_create: function(data, escape) {
                            return '<div class="create">Agregar <strong>' + escape(data.input) +
                                '</strong>&hellip;</div>';
                        }
                    },
                    onItemAdd: function(value, item) {
                        var dataset = item.dataset || {};
                        var clientData = {};

                        if (dataset.client) {
                            try {
                                clientData = JSON.parse(dataset.client);
                            } catch (e) {
                                clientData = {};
                            }
                        }

                        if (isMain) {
                            fillPersonalClientFields(clientData);
                        } else {
                            // si es un DNI de grupo, rellenar solo los campos de la fila correspondiente
                            var $row = $($input).closest('.row');
                            $row.find('input[name="names[]"]').prop('readonly', false).val(normalizeDataValue(clientData.name));
                            $row.find('input[name="addresses[]"]').val(normalizeDataValue(clientData.address));
                        }

                        if (!isMain || isHydratingContractForm) {
                            return;
                        }

                        // comprobar cuotas pendientes para el documento seleccionado (solo para principal)
                        totalDebt = 0;
                        $.ajax({
                            url: '{{ route('clients.check') }}',
                            method: 'GET',
                            data: {
                                document: value
                            },
                            success: function(data) {
                                if (data.status) {
                                    ToastMessage.fire({
                                        text: 'El cliente no tiene cuotas pendientes'
                                    });
                                    totalDebt = 0;
                                } else {
                                    var html = '';
                                    data.quotas.forEach(function(quota) {
                                        totalDebt += parseFloat(String(quota.debt)
                                            .replace(',', '.'));
                                        html += `
										<tr>
											<td>${quota.contract_id}</td>
											<td>${quota.number}</td>
											<td>${quota.amount}</td>
											<td>${quota.debt}</td>
											<td>${quota.date}</td>
										</tr>
									`;
                                    });
                                    $('#tbl-quotas').html(html);
                                    openModal('#quotasModal');
                                }
                            }
                        });

                    }
                });

                $input.data('ts-initialized', true);
            }

            // Inicializar TomSelect en los inputs existentes de DNI (principal y grupo).
            $('.ts-document, #divGroup input[name="documents[]"]').each(function() {
                initTomSelect($(this));
            });
            // Exponer helper para filas agregadas dinámicamente.
            window.initContractDocumentSelect = initTomSelect;
            // Fallback: si alguna fila se agrega sin clase ts-document, se inicializa al enfocar.
            $(document).on('focus', '#divGroup input[name="documents[]"]', function() {
                initTomSelect($(this));
            });

            // Validación del campo meses según tipo de cuota
            var $months = $('input[name="months_number"]');
            var $typeQuota = $('select[name="type_quota"]');

            // Cambiar atributo step según tipo de cuota
            $typeQuota.on('change', function() {
                $months.attr('step', '1');
            });

            // Inicializar step
            $months.attr('step', '1');

        });

        //Boton de buscar
        $('#btn-search').click(function() {

            var dni = $('#document').val().trim();

            if (dni.length != 8) {
                $('#name').prop('readonly', false).focus();
                return;
            }

            Swal.showLoading();

            $.ajax({
                url: '{{ route('api.reniec') }}',
                method: 'GET',
                data: {
                    dni
                },
                success: function(data) {

                    Swal.close();

                    if (data.status) {
                        $('#name').prop('readonly', false);
                        $('#name').val(data.name);
                    } else {
                        $('#name').val('');
                        $('#name').prop('readonly', false);
                        $('#name').focus();
                    }
                },
                error: function() {
                    Swal.close();
                    $('#name').prop('readonly', false).focus();
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            })
        });

        $(document).on('click', '.btn-group-search, #btn-group-search', function() {
            var $row = $(this).closest('.row');
            var $dniInput = $row.find('input[name="documents[]"]');
            var $nameInput = $row.find('input[name="names[]"]');
            var dni = $dniInput.val().trim();

            if (dni.length != 8) {
                $nameInput.val('');
                $nameInput.prop('readonly', false).focus();
                return;
            }

            Swal.showLoading();

            $.ajax({
                url: '{{ route('api.reniec') }}',
                method: 'GET',
                data: {
                    dni
                },
                success: function(data) {
                    Swal.close();
                    if (data.status) {
                        $nameInput.prop('readonly', false);
                        $nameInput.val(data.name);
                    } else {
                        $nameInput.val('');
                        $nameInput.prop('readonly', false);
                        $nameInput.focus();
                    }
                },
                error: function() {
                    Swal.close();
                    $nameInput.prop('readonly', false).focus();
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            });
        });

        // Cascada de Departamentos -> Provincias -> Distritos
        $(document).on('change', '#department_id', function() {
            var department_id = $(this).val();
            var $provinceSelect = $('#province_id');
            var $districtSelect = $('#district_id');

            // Limpiar provincias y distritos
            $provinceSelect.html('<option value="">Seleccionar</option>');
            $districtSelect.html('<option value="">Seleccionar</option>');

            if (!department_id) {
                return;
            }

            $.ajax({
                url: '{{ route('api.provinces') }}',
                method: 'GET',
                data: {
                    department_id: department_id
                },
                success: function(data) {
                    $.each(data, function(index, province) {
                        $provinceSelect.append('<option value="' + province.id + '">' + province
                            .name + '</option>');
                    });
                },
                error: function() {
                    ToastError.fire({
                        text: 'Ocurrió un error al cargar las provincias'
                    });
                }
            });
        });

        $(document).on('change', '#province_id', function() {
            var province_id = $(this).val();
            var $districtSelect = $('#district_id');

            // Limpiar distritos
            $districtSelect.html('<option value="">Seleccionar</option>');

            if (!province_id) {
                return;
            }

            $.ajax({
                url: '{{ route('api.districts') }}',
                method: 'GET',
                data: {
                    province_id: province_id
                },
                success: function(data) {
                    $.each(data, function(index, district) {
                        $districtSelect.append('<option value="' + district.id + '">' + district
                            .name + '</option>');
                    });
                },
                error: function() {
                    ToastError.fire({
                        text: 'Ocurrió un error al cargar los distritos'
                    });
                }
            });
        });

        $('#storeForm').submit(function(e) {
            $('#btn-save').prop('disabled', true);
            $('#btn-confirm').prop('disabled', false);
            $('#warning').text('');
            e.preventDefault();

            if (totalDebt > 0) {

                var base_insurance = parseFloat(String($('#insurance_cost').val()).replace(',', '.')) || 0;
                var quotas = parseFloat(String($('#months_number').val()).replace(',', '.')) || 0;
                var type_quota = parseInt($('select[name="type_quota"]').val()) || 1;

                // Calcular el número de meses según el tipo de cuota
                // 1 => semanal (4 cuotas/mes), 2 => catorcenal (2 cuotas/mes), 4 => mensual (1 cuota/mes)
                var quotasPerMonthMap = {
                    1: 4, // semanal: 4 cuotas por mes
                    2: 2, // catorcenal: 2 cuotas por mes
                    4: 1 // mensual: 1 cuota por mes
                };
                var quotasPerMonth = quotasPerMonthMap[type_quota] || 4;
                var months = quotas / quotasPerMonth;

                var insurance_cost = Math.round(base_insurance * months * 100) / 100;
                var interest_percentage = parseFloat(String($('#interest').val()).replace(',', '.')) || 0;
                var requested_amount = parseFloat(String($('#requested_amount').val()).replace(',', '.')) || 0;

                var interest = Math.round(requested_amount * (interest_percentage / 100) * 100) / 100;
                var contract_debt = Math.round((requested_amount + interest + insurance_cost) * 100) / 100;
                var difference = Math.round((contract_debt - totalDebt) * 100) / 100;

                $('#past-debt').text(totalDebt.toFixed(2));
                $('#contract-debt').text(contract_debt.toFixed(2));
                $('#difference').text(difference.toFixed(2));

                openModal('#confirmDerivedModal');

                $('#btn-save').prop('disabled', false);
                return;
            }

            var isEdit = $('#contract_id').val() != '';
            var url = isEdit ? '{{ route('contracts.index') }}/' + $('#contract_id').val() : '{{ route('contracts.store') }}';
            var method = isEdit ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: $(this).serialize(),
                success: function(data) {
                    if (data.status) {
                        closeModal('#createModal');
                        $('#storeForm')[0].reset();
                        $('#contract_id').val('');
                        // Limpiar selects de provincia y distrito
                        $('#province_id').html('<option value="">Seleccionar</option>');
                        $('#district_id').html('<option value="">Seleccionar</option>');

                        ToastMessage.fire({
                                text: 'Registro guardado'
                            })
                        .then(() => location.reload());

                    } else {
                        ToastError.fire({
                            text: data.error ? data.error : 'Ocurrió un error'
                        });
                        $('#btn-save').prop('disabled', false);
                    }
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                    $('#btn-save').prop('disabled', false);
                }
            });

        });

        $(document).on('click', '#btn-confirm', function() {

            totalDebt = 0;

            $('#storeForm').submit();
            closeModal('#confirmDerivedModal');
        });


        $(document).on('click', '.btn-edit', function() {
            var id = $(this).data('id');
            totalDebt = 0;
            closeModal('#quotasModal');

            $.ajax({
                url: '{{ route('contracts.index') }}' + '/' + id + '/edit/',
                method: 'GET',
                success: function(data) {
                    isHydratingContractForm = true;
                    $('#storeForm')[0].reset();
                    $('#contract_id').val(data.id);
                    $('#createModal .modal-title').text('Editar');

                    $('#client_type').val(data.client_type).trigger('change');

                    if(data.client_type == 'Personal') {
                        var tomSelectDoc = document.getElementById('document').tomselect;
                        if(tomSelectDoc) {
                            tomSelectDoc.addOption({document: data.document, name: data.name});
                            tomSelectDoc.setValue(data.document);
                        } else {
                            $('#document').val(data.document);
                        }

                        $('#name').val(normalizeDataValue(data.name));
                        $('#phone').val(normalizeDataValue(data.phone));
                        $('#address').val(normalizeDataValue(data.address));
                        $('#reference').val(normalizeDataValue(data.reference));
                        $('#home_type').val(normalizeDataValue(data.home_type));
                        $('#business_line').val(normalizeDataValue(data.business_line));
                        $('#business_address').val(normalizeDataValue(data.business_address));
                        if(data.business_start_date) {
                            $('[name="business_start_date"]').val(data.business_start_date.split('T')[0]);
                        }
                        $('#civil_status').val(normalizeDataValue(data.civil_status)).trigger('change');
                        $('#husband_name').val(normalizeDataValue(data.husband_name));
                        $('#husband_document').val(normalizeDataValue(data.husband_document));

                        if(data.district) {
                            $('#department_id').val(data.district.province.department_id);
                            $('#province_id').html('<option value="'+data.district.province_id+'">'+data.district.province.name+'</option>');
                            $('#district_id').html('<option value="'+data.district_id+'">'+data.district.name+'</option>');
                        } else {
                            $('#department_id').val('');
                            $('#province_id').html('<option value="">Seleccionar</option>');
                            $('#district_id').html('<option value="">Seleccionar</option>');
                        }
                    } else {
                        if (data.group_name) {
                            $('#group_name').val(data.group_name.replace(/Grupo \d+ - /, ''));
                        }
                        if(data.people) {
                            var people = JSON.parse(data.people);
                            var $baseRow = $('#divGroup .row').first();
                            $('#divGroup .row:not(:first)').remove();
                            
                            people.forEach(function(person, index) {
                                var $row = (index === 0) ? $baseRow : $baseRow.clone().appendTo('#divGroup');
                                $row.find('input[name="documents[]"]').val(person.document);
                                $row.find('input[name="names[]"]').val(person.name);
                                $row.find('input[name="addresses[]"]').val(person.address);
                            });
                        }
                    }

                    $('select[name="seller_id"]').val(data.seller_id);
                    $('select[name="advisor_id"]').val(data.advisor_id);
                    var requestedAmountValue = parseFloat(String(data.requested_amount).replace(',', '.'));
                    $('#requested_amount').val(isNaN(requestedAmountValue) ? '' : requestedAmountValue.toFixed(2));
                    var quotasNumber = parseInt(data.quotas_number, 10);
                    if (isNaN(quotasNumber) || quotasNumber <= 0) {
                        quotasNumber = parseInt(data.months_number, 10) || 0;
                    }
                    $('#months_number').val(quotasNumber > 0 ? quotasNumber : '');
                    $('select[name="type_quota"]').val(data.type_quota);
                    setContractDate(data.date ? data.date.split('T')[0] : '');
                    $('#interest').val(data.percentage);
                    
                    $('#insurance_cost').val(data.insurance_amount);

                    isHydratingContractForm = false;
                    openModal('#createModal');
                },
                error: function(err) {
                    isHydratingContractForm = false;
                    ToastError.fire({
                        text: 'Ocurrió un error al cargar los datos.'
                    });
                }
            });
        });

        $(document).on('click', '.btn-delete', function() {

            var id = $(this).data('id');

            ToastConfirm.fire({
                text: '¿Estás seguro que deseas borrar el registro?',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route('contracts.index') }}' + '/' + id,
                        method: 'DELETE',
                        success: function(data) {
                            ToastMessage.fire({
                                    text: 'Registro eliminado'
                                })
                                .then(() => location.reload());
                        },
                        error: function(err) {
                            ToastError.fire({
                                text: 'Ocurrió un error'
                            });
                        }
                    });
                }
            });

        });

        function syncBulkDeleteState() {
            const checked = $('.contract-checkbox:checked').length;
            $('#btn-bulk-delete').toggleClass('d-none', checked === 0);
        }

        $('#check-all-contracts').on('change', function() {
            $('.contract-checkbox').prop('checked', $(this).is(':checked'));
            syncBulkDeleteState();
        });

        $(document).on('change', '.contract-checkbox', function() {
            const total = $('.contract-checkbox').length;
            const checked = $('.contract-checkbox:checked').length;

            $('#check-all-contracts').prop('checked', total > 0 && total === checked);
            syncBulkDeleteState();
        });

        $('#btn-bulk-delete').on('click', function() {
            const ids = $('.contract-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (!ids.length) {
                return;
            }

            ToastConfirm.fire({
                text: 'Se eliminaran permanentemente los contratos seleccionados con sus cuotas, pagos y registros relacionados. Esta accion no se puede deshacer.',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route('contracts.bulk-destroy') }}',
                        method: 'DELETE',
                        data: {
                            contract_ids: ids
                        },
                        success: function(data) {
                            ToastMessage.fire({
                                    text: data.deleted_count + ' contrato(s) eliminado(s)'
                                })
                                .then(() => location.reload());
                        },
                        error: function(err) {
                            ToastError.fire({
                                text: err.responseJSON?.error || 'Ocurrió un error al eliminar los contratos'
                            });
                        }
                    });
                }
            });
        });

        $('#civil_status').change(function() {
            var civil_status = $(this).val();

            if (civil_status == 'Casado') {
                $('#divHusbandName').show();
                $('#divHusbandDocument').show();
            } else {
                $('#husband_name').val('');
                $('#husband_document').val('');
                $('#divHusbandName').hide();
                $('#divHusbandDocument').hide();
            }
        });

        $('#client_type').change(function() {
            var client_type = $(this).val();

            if (client_type == 'Personal') {
                if (!isHydratingContractForm) {
                    $('#storeForm')[0].reset();
                    // Limpiar selects de provincia y distrito
                    $('#province_id').html('<option value="">Seleccionar</option>');
                    $('#district_id').html('<option value="">Seleccionar</option>');
                    setContractDate();
                }

                $('#divDocument').show();
                $('#divName').show();
                $('#divPhone').show();
                $('#divAddress').show();
                $('#divReference').show();
                $('#divHomeType').show();
                $('#divBusinessLine').show();
                $('#divBusinessAddress').show();
                $('#divBusinessStartDate').show();
                $('#divCivilStatus').show();

                $('#divGroupName').hide();
                $('#divQuantity').hide();
                $('#divGroup').hide();

            } else if (client_type == 'Grupo') {

                $('#divDocument').hide();
                $('#divName').hide();
                $('#divPhone').hide();
                $('#divAddress').hide();
                $('#divDepartment').hide();
                $('#divProvince').hide();
                $('#divDistrict').hide();
                $('#divReference').hide();
                $('#divHomeType').hide();
                $('#divBusinessLine').hide();
                $('#divBusinessAddress').hide();
                $('#divBusinessStartDate').hide();
                $('#divCivilStatus').hide();

                $('#divGroupName').show();
                $('#divQuantity').show();
                $('#divGroup').show();

            }
        });
        
        // Trigger initial change based on default value
        $('#client_type').trigger('change');

        $('#btn-add').click(function() {
            var $baseRow = $('#divGroup .row').first();
            if (!$baseRow.length) return;

            // Clonar la estructura real de la primera fila para mantener layout/campos.
            var $newRow = $baseRow.clone(false, false);

            // Limpiar DOM generado por TomSelect en la fila clonada.
            $newRow.find('.ts-wrapper').remove();
            $newRow.find(
                'input[name="documents[]"], input[name="names[]"], input[name="addresses[]"], input[name="quotas[]"]'
            ).each(function() {
                $(this).val('').removeAttr('readonly');
            });

            var $dniInput = $newRow.find('input[name="documents[]"]').first();
            $dniInput
                .removeClass('tomselected ts-hidden-accessible')
                .addClass('ts-document')
                .removeAttr('id tabindex hidden')
                .removeData('ts-initialized');

            $newRow.find('.btn-group-search, #btn-group-search').removeAttr('id').addClass('btn-group-search');

            $('#divGroup').append($newRow);

            var $newInput = $('#divGroup .row').last().find('input[name="documents[]"]').first();
            if (window.initContractDocumentSelect) {
                window.initContractDocumentSelect($newInput);
            }
        });
        $('#btn-remove').click(function() {
            if ($('#divGroup').children().length > 2) {
                $('#divGroup').children().last().remove();
            } else {
                console.log('Deben haber 2 personas mínimo para grupo');
            }
        });

        $(document).on('click', '.btn-approve', function() {
            var id = $(this).data('id');
            ToastConfirm.fire({
                text: '¿Estás seguro que deseas aprobar este contrato?',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route('contracts.index') }}/' + id + '/approve',
                        method: 'PUT',
                        success: function(data) {
                            if (data.status) {
                                ToastMessage.fire({
                                        text: 'Contrato aprobado'
                                    })
                                    .then(() => location.reload());
                            } else {
                                ToastError.fire({
                                    text: 'Ocurrió un error'
                                });
                            }
                        },
                        error: function(err) {
                            ToastError.fire({
                                text: 'Ocurrió un error'
                            });
                        }
                    });
                }
            });
        });

        //Boton de excel?
        $('#btn-excel').click(function() {
            const params = new URLSearchParams(window.location.search);
            const url = `{{ route('contracts.excel') }}?${params.toString()}`;
            window.location.href = url;
        });

        $('#btn-export-import-data').click(function() {
            const params = new URLSearchParams(window.location.search);
            const url = `{{ route('contracts.import.data.export') }}?${params.toString()}`;
            window.location.href = url;
        });
    </script>
@endsection
