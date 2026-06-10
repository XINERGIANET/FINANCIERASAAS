@extends('template.app')

@section('title', 'Pago múltiple')

@section('content')
<nav class="mb-2">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
        <li class="breadcrumb-item">Cobranzas</li>
        <li class="breadcrumb-item active">Pago múltiple</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex gap-2">
            <a href="{{ route('payments.index') }}" class="btn btn-light">Volver a pagos</a>
            <a href="{{ route('payments.dues') }}" class="btn btn-outline-primary">Gestión de mora</a>
        </div>
        <div class="text-end">
            <div class="small text-muted">Saldo seleccionado</div>
            <div class="fs-2 fw-bold text-primary" id="selectedDebtLabel">S/ 0.00</div>
        </div>
    </div>

    <div class="card-body border-bottom">
        <form method="GET">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Cliente</label>
                        <input type="text" class="form-control" name="name" value="{{ request()->name }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Asesor comercial</label>
                        <select class="form-select" name="seller_id">
                            <option value="">Seleccionar</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}" @if($seller->id == request()->seller_id) selected @endif>{{ $seller->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Días desde vencimiento</label>
                        <input type="number" class="form-control" name="from_days" min="1" value="{{ request()->from_days }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Días hasta vencimiento</label>
                        <input type="number" class="form-control" name="to_days" min="1" value="{{ request()->to_days }}">
                    </div>
                </div>
            </div>
            <button class="btn btn-primary">Filtrar</button>
            <a href="{{ route('payments.multiple') }}" class="btn btn-danger">Limpiar</a>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th style="width:40px;">
                        <input type="checkbox" class="form-check-input" id="selectAll">
                    </th>
                    <th>Cliente</th>
                    <th>Asesor</th>
                    <th>Número</th>
                    <th>Monto</th>
                    <th>Saldo</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quotas as $quota)
                    <tr data-quota-row="{{ $quota->id }}" data-contract-id="{{ $quota->contract_id }}" data-debt="{{ $quota->debt }}" data-client="{{ $quota->contract->client() }}">
                        <td>
                            <input type="checkbox" class="form-check-input quota-check" value="{{ $quota->id }}" data-contract-id="{{ $quota->contract_id }}" data-debt="{{ $quota->debt }}">
                        </td>
                        <td>{{ optional($quota->contract)->client() }}</td>
                        <td>{{ optional(optional($quota->contract)->seller)->name }}</td>
                        <td>{{ $quota->number }}</td>
                        <td>{{ number_format($quota->amount, 2) }}</td>
                        <td class="quota-debt">{{ number_format($quota->debt, 2) }}</td>
                        <td>{{ $quota->date->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No se han encontrado resultados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($quotas->hasPages())
        <div class="card-footer d-flex align-items-center">
            {{ $quotas->withQueryString()->links() }}
        </div>
    @endif
</div>

<div class="card mt-3">
    <div class="card-header">
        <strong>Cuotas seleccionadas</strong>
    </div>
    <div class="card-body">
        <div class="table-responsive mb-3">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cuota</th>
                        <th>Saldo</th>
                    </tr>
                </thead>
                <tbody id="selectedQuotasBody">
                    <tr>
                        <td colspan="3" class="text-center text-muted">Sin cuotas seleccionadas</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button class="btn btn-primary" id="btnOpenPayModal" disabled data-bs-toggle="modal" data-bs-target="#payMultipleModal">
            Procesar pago múltiple
        </button>
    </div>
</div>

<div class="modal modal-blur fade" id="payMultipleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="payMultipleForm" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar pago múltiple</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Monto total recibido</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="amount" id="multiple_amount">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Método de pago</label>
                        <select class="form-select" name="payment_method_id" id="multiple_payment_method_id">
                            <option value="">Seleccionar</option>
                            @foreach($payment_methods as $payment_method)
                                <option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Fecha</label>
                        @if(auth()->user()->hasRole('admin'))
                            <input type="date" class="form-control" name="date" id="multiple_date" value="{{ now()->format('Y-m-d') }}">
                        @else
                            <input type="text" class="form-control" value="{{ now()->format('d/m/Y') }}" disabled>
                            <input type="hidden" name="date" id="multiple_date" value="{{ now()->format('Y-m-d') }}">
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Voucher / constancia</label>
                        <input type="file" class="form-control" name="image" id="multiple_image" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                    <div class="alert alert-info mb-0">
                        El monto se aplicará de la cuota más antigua a la más reciente dentro de la selección.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveMultiple">Guardar pago</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const selectedQuotas = new Map();
    let selectedContractId = null;

    function formatMoney(value) {
        return 'S/ ' + (parseFloat(value || 0).toFixed(2));
    }

    function renderSelectedQuotas() {
        let html = '';
        let total = 0;

        selectedQuotas.forEach((quota) => {
            total += parseFloat(quota.debt);
            html += `
                <tr>
                    <td>${quota.number}</td>
                    <td>${quota.label}</td>
                    <td>${formatMoney(quota.debt)}</td>
                </tr>
            `;
        });

        if (!html) {
            html = '<tr><td colspan="3" class="text-center text-muted">Sin cuotas seleccionadas</td></tr>';
        }

        $('#selectedQuotasBody').html(html);
        $('#selectedDebtLabel').text(formatMoney(total));
        $('#btnOpenPayModal').prop('disabled', selectedQuotas.size === 0);
    }

    function syncCheckboxes() {
        $('.quota-check').each(function() {
            const id = String($(this).val());
            $(this).prop('checked', selectedQuotas.has(id));
        });
    }

    $(document).on('change', '.quota-check', function() {
        const checkbox = $(this);
        const id = String(checkbox.val());
        const row = checkbox.closest('tr');
        const contractId = String(checkbox.data('contract-id'));

        if (checkbox.is(':checked')) {
            if (selectedContractId && selectedContractId !== contractId) {
                checkbox.prop('checked', false);
                ToastError.fire({ text: 'Solo puede seleccionar cuotas de un mismo cliente/contrato.' });
                return;
            }

            selectedContractId = contractId;
            selectedQuotas.set(id, {
                id,
                number: row.find('td').eq(3).text(),
                debt: parseFloat(checkbox.data('debt')),
                label: 'Cuota ' + row.find('td').eq(3).text(),
            });
        } else {
            selectedQuotas.delete(id);
            if (selectedQuotas.size === 0) {
                selectedContractId = null;
            }
        }

        renderSelectedQuotas();
    });

    $(document).on('change', '#selectAll', function() {
        const checked = $(this).is(':checked');
        $('.quota-check').each(function() {
            const checkbox = $(this);
            const rowContractId = String(checkbox.data('contract-id'));
            const id = String(checkbox.val());

            if (!checked) {
                checkbox.prop('checked', false);
                selectedQuotas.delete(id);
                return;
            }

            if (selectedContractId && selectedContractId !== rowContractId) {
                checkbox.prop('checked', false);
                return;
            }

            checkbox.prop('checked', true);
            selectedContractId = rowContractId;
            selectedQuotas.set(id, {
                id,
                number: checkbox.closest('tr').find('td').eq(3).text(),
                debt: parseFloat(checkbox.data('debt')),
                label: 'Cuota ' + checkbox.closest('tr').find('td').eq(3).text(),
            });
        });

        if (!checked && selectedQuotas.size === 0) {
            selectedContractId = null;
        }

        renderSelectedQuotas();
    });

    $(document).on('shown.bs.modal', '#payMultipleModal', function() {
        $('#multiple_amount').val($('#selectedDebtLabel').text().replace('S/ ', '').trim());
    });

    $('#payMultipleForm').on('submit', function(e) {
        e.preventDefault();

        if (selectedQuotas.size === 0) {
            ToastError.fire({ text: 'Debe seleccionar al menos una cuota.' });
            return;
        }

        $('#btnSaveMultiple').prop('disabled', true);

        const fd = new FormData(this);
        selectedQuotas.forEach((quota) => {
            fd.append('cuotas_seleccionadas_ids[]', quota.id);
        });

        $.ajax({
            url: '{{ route('payments.store') }}',
            method: 'POST',
            processData: false,
            contentType: false,
            data: fd,
            success: function(data) {
                if (data.status) {
                    $('#payMultipleModal').modal('hide');
                    ToastMessage.fire({ text: 'Pago múltiple registrado correctamente' })
                        .then(() => location.reload());
                } else {
                    ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
                    $('#btnSaveMultiple').prop('disabled', false);
                }
            },
            error: function() {
                ToastError.fire({ text: 'Ocurrió un error al procesar el pago' });
                $('#btnSaveMultiple').prop('disabled', false);
            }
        });
    });

    renderSelectedQuotas();
</script>
@endsection
