@extends('template.app')

@section('title', 'Clientes')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Clientes</li>
  </ol>
</nav>

<div class="card">
	@if (auth()->user()->hasRole('admin'))
	<div class="card-header d-flex flex-wrap align-items-center gap-2">
		<a class="btn btn-success" href="{{ route('clients.excel', request()->all()) }}" target="_blank">
			<i class="ti ti-file-export icon"></i> Excel
		</a>
	</div>
	@endif
	@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit'))
	<div class="card-body border-bottom">
		<form>
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
						<label class="form-label">Tipo de cliente</label>
						<select class="form-select" name="recurrence">
							<option value="">Todos</option>
							<option value="nuevo" @if(request()->recurrence === 'nuevo') selected @endif>Nuevo</option>
							<option value="recurrente" @if(request()->recurrence === 'recurrente') selected @endif>Recurrente</option>
						</select>
					</div>
				</div>
			</div>
			<button class="btn btn-primary">Filtrar</button>
			<a href="{{ route('clients.index') }}" class="btn btn-danger">Limpiar</a>
		</form>
	</div>
	@endif
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead>
				<tr>
					<th>Cliente/Grupo</th>
					<th>Tipo de cliente</th>
					<th>Acción</th>
				</tr>
			</thead>
			<tbody>
				@if($clients->count() > 0)
				@foreach($clients as $client)
				<tr>
					<td title="{!! $client->people() !!}" data-bs-toggle="tooltip" data-bs-html="true">{{ $client->client_type == 'Personal' ? $client->name : $client->group_name }}</td>
					<td>{{ $client->type() }}</td>
					<td>
						<div class="d-flex gap-2">
							<div class="d-flex gap-2">
								@if($client->client_type == 'Personal')
								<button class="btn btn-primary btn-icon btn-details" data-document="{{ $client->document }}" title="Detalles">
									<i class="ti ti-search icon"></i>
								</button>
								@endif
								<button class="btn btn-primary btn-icon btn-contracts" data-client-type="{{ $client->client_type }}" data-document="{{ $client->document }}" data-group-name="{{ $client->group_name }}" title="Contratos">
									<i class="ti ti-list icon"></i>
								</button>
								<button class="btn btn-secondary btn-icon btn-images" data-client-type="{{ $client->client_type }}" data-document="{{ $client->document }}" data-group-name="{{ $client->group_name }}" data-name="{{ $client->client_type == 'Personal' ? $client->name : $client->group_name }}" title="Imágenes">
									<i class="ti ti-photo icon"></i>
								</button>
							</div>
						</div>
					</td>		
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="9" align="center">No se han encontrado resultados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($clients->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $clients->withQueryString()->links() }}
	</div>
	@endif
</div>

<div class="modal modal-blur fade" id="detailsModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<div class="modal-header">
  			<h5 class="modal-title">Detalles</h5>
  			 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  		</div>
			<div class="table-responsive">
  			<table class="table card-table table-vcenter">
  				<thead>
  					<tr>
  						<th>DNI</th>
  						<th>Nombre</th>
  						<th>Teléfono</th>
  						<th>Dirección</th>
  						<th>Estado civil</th>
  					</tr>
  				</thead>
  				<tbody id="tbl-details"></tbody>
  			</table>
			</div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="contractsModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<div class="modal-header">
  			<h5 class="modal-title">Contratos</h5>
  			 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  		</div>
			<div class="table-responsive">
  			<table class="table card-table table-vcenter">
  				<thead>
  					<tr>
  						<th>Monto solicitado</th>
  						<th>Cuotas</th>
  						<th>Interes</th>
  						<th>Monto a pagar</th>
  						<th>Fecha de prestamo</th>
  						<th></th>
  						<th>Acción</th>
  					</tr>
  				</thead>
  				<tbody id="tbl-contracts"></tbody>
  			</table>
			</div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="quotasModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<div class="modal-header">
  			<h5 class="modal-title">Cuotas</h5>
  			 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  		</div>
			<div class="table-responsive">
  			<table class="table card-table table-vcenter">
  				<thead>
  					<tr>
  						<th>Número</th>
  						<th>Monto</th>
  						<th>Saldo</th>
  						<th>Fecha</th>
  						<th></th>
  					</tr>
  				</thead>
  				<tbody id="tbl-quotas"></tbody>
  			</table>
			</div>
    </div>
  </div>
</div>
<div class="modal modal-blur fade" id="imagesModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Imágenes — <span id="imgClientName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formUploadImage" enctype="multipart/form-data">
          @csrf
          <input type="hidden" id="imgDocument" name="document">
          <input type="hidden" id="imgGroupName" name="group_name">
          <div class="d-flex align-items-end gap-2 mb-3">
            <div class="flex-grow-1">
              <label class="form-label">Subir imagen</label>
              <input type="file" class="form-control" id="imageFile" name="image" accept="image/*,.heic,.heif,.avif,.tiff,.tif,.webp,.bmp,.svg">
            </div>
            <button type="submit" class="btn btn-primary" id="btnUploadImage" disabled>
              <i class="ti ti-plus"></i> Subir
            </button>
          </div>
        </form>
        <hr class="mt-0">
        <div id="gallery-images"
             style="display:grid; grid-template-columns: repeat(2, 1fr); gap: 12px; max-height: 420px; overflow-y: auto; padding-right: 4px;">
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>

	$(document).on('click', '.btn-details', function(){

		var doc = $(this).data('document');

		$.ajax({
			url: '{{ route('clients.details') }}',
			method: 'GET',
			data: { document: doc },
			success: function(data){
				
				var html = `
					<tr>
						<td>${data.document}</td>
						<td>${data.name}</td>
						<td>${data.phone}</td>
						<td>${data.address}</td>
						<td>${data.civil_status}</td>
					</tr>
				`;

				$('#tbl-details').html(html);
				$('#detailsModal').modal('show');
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

	$(document).on('click', '.btn-contracts', function(){

		var client_type = $(this).data('client-type');
		var doc = $(this).data('document');
		var group_name = $(this).data('group-name');

		$.ajax({
			url: '{{ route('clients.contracts') }}',
			method: 'GET',
			data: { client_type, document: doc, group_name },
			success: function(data){
				var html = '';

				data.forEach(function(contract){

					html += `
						<tr>
							<td>${contract.requested_amount}</td>
							<td>${contract.quotas_number}</td>
							<td>${contract.interest}</td>
							<td>${contract.payable_amount}</td>
							<td>${contract.date}</td>
							<td>${ contract.paid ? '<span class="badge bg-success"></span>' : '<span class="badge bg-danger"></span>' }</td>
							<td>
								<button class="btn btn-primary btn-icon btn-quotas" data-contract="${contract.id}" title="Quotas">
									<i class="ti ti-list icon"></i>
								</button>
							</td>
						</tr>
					`;
				
				});

				$('#tbl-contracts').html(html);
				$('#contractsModal').modal('show');
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

	$(document).on('click', '.btn-quotas', function(){

		var contract_id = $(this).data('contract');

		$.ajax({
			url: '{{ route('clients.quotas') }}',
			method: 'GET',
			data: { contract_id },
			success: function(data){
				var html = '';

				data.forEach(function(quota){

					html += `
						<tr>
							<td>${quota.number}</td>
							<td>${quota.amount}</td>
							<td>${quota.debt}</td>
							<td>${quota.date}</td>
							<td>${ quota.paid ? '<span class="badge bg-success"></span>' : '<span class="badge bg-danger"></span>' }</td>
						</tr>
					`;

				});

				$('#tbl-quotas').html(html);
				$('#quotasModal').modal('show');
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

	$('#imageFile').on('change', function() {
		$('#btnUploadImage').prop('disabled', !this.files.length);
	});

	$('#imagesModal').on('hidden.bs.modal', function() {
		$('#imageFile').val('');
		$('#btnUploadImage').prop('disabled', true);
	});

	var currentImgDocument = '';
	var currentImgGroupName = '';

	function loadClientImages() {
		$.ajax({
			url: '{{ route('clients.images') }}',
			method: 'GET',
			data: { document: currentImgDocument, group_name: currentImgGroupName },
			success: function(data) {
				var html = '';
				if (data.length === 0) {
					html = '<div style="grid-column:1/-1; text-align:center; color:#aaa; padding:24px 0;">Sin imágenes aún</div>';
				} else {
					data.forEach(function(img) {
						html += `
							<div id="img-block-${img.id}" style="position:relative; border-radius:8px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.12);">
								<a href="${img.url}" target="_blank" style="display:block; padding-top:100%; position:relative;">
									<img src="${img.url}" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover;">
								</a>
								<button class="btn btn-danger btn-sm btn-delete-image" data-id="${img.id}"
									style="position:absolute; top:6px; right:6px; width:32px; height:32px; padding:0; display:flex; align-items:center; justify-content:center; border-radius:6px;">
									<i class="ti ti-trash" style="font-size:16px;"></i>
								</button>
							</div>
						`;
					});
				}
				$('#gallery-images').html(html);
			}
		});
	}

	$(document).on('click', '.btn-images', function() {
		currentImgDocument  = $(this).data('document') || '';
		currentImgGroupName = $(this).data('group-name') || '';
		var name = $(this).data('name');
		$('#imgClientName').text(name);
		$('#imgDocument').val(currentImgDocument);
		$('#imgGroupName').val(currentImgGroupName);
		$('#imageFile').val('');
		loadClientImages();
		$('#imagesModal').modal('show');
	});

	$('#formUploadImage').on('submit', function(e) {
		e.preventDefault();
		var formData = new FormData(this);
		$.ajax({
			url: '{{ route('clients.uploadImage') }}',
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(data) {
				if (data.status) {
					$('#imageFile').val('');
					$('#btnUploadImage').prop('disabled', true);
					loadClientImages();
				} else {
					ToastError.fire({ text: data.error || 'Error al subir imagen' });
				}
			},
			error: function() {
				ToastError.fire({ text: 'Ocurrió un error al subir la imagen' });
			}
		});
	});

	$(document).on('click', '.btn-delete-image', function() {
		var id = $(this).data('id');
		Swal.fire({
			title: '¿Eliminar imagen?',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: 'Sí, eliminar',
			cancelButtonText: 'Cancelar'
		}).then(function(result) {
			if (result.isConfirmed) {
				$.ajax({
					url: '/clients/images/' + id,
					method: 'POST',
					data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
					success: function(data) {
						if (data.status) {
							$('#img-block-' + id).remove();
							if ($('#gallery-images [id^="img-block-"]').length === 0) {
								$('#gallery-images').html('<div style="grid-column:1/-1; text-align:center; color:#aaa; padding:24px 0;">Sin imágenes aún</div>');
							}
						}
					}
				});
			}
		});
	});

</script>
@endsection