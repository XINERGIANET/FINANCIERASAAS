<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}" />
	<title>Xinergia</title>
	<link rel="stylesheet" href="{{ asset('assets/css/tabler.min.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/css/tabler-vendors.min.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/css/tabler-icons.min.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
	<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/favicon-16x16.png') }}?v={{ filemtime(public_path('assets/images/favicon-16x16.png')) }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favicon-32x32.png') }}?v={{ filemtime(public_path('assets/images/favicon-32x32.png')) }}">
	<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/images/apple-touch-icon.png') }}?v={{ filemtime(public_path('assets/images/apple-touch-icon.png')) }}">
	<link rel="manifest" href="{{ asset('site.webmanifest') }}">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="Crece Conmigo">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="theme-color" content="#01114b">
	<style>
		:root {
			--login-blue: #2f73ca;
			--login-blue-dark: #1e4f8f;
			--login-link: #2563d8;
			--login-text: #061631;
			--login-muted: #657188;
			--login-border: #d9dee8;
		}

		html,
		body {
			min-height: 100%;
		}

		body {
			margin: 0;
			color: var(--login-text);
			background: #ffffff;
			font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
		}

		.login-page {
			display: grid;
			grid-template-columns: minmax(360px, 38%) 1fr;
			min-height: 100vh;
			background: #ffffff;
		}

		.login-panel {
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 42px 48px;
		}

		.login-form-wrap {
			width: min(100%, 480px);
		}

		.login-title {
			margin: 0 0 28px;
			font-size: 24px;
			font-weight: 700;
			line-height: 1.25;
			color: var(--login-text);
		}

		.login-subtitle {
			margin: -18px 0 28px;
			color: var(--login-muted);
			font-size: 16px;
			line-height: 1.5;
		}

		.login-form .form-label {
			margin-bottom: 10px;
			color: #07162f;
			font-size: 19px;
			font-weight: 500;
		}

		.login-form .form-control {
			height: 46px;
			border: 1px solid var(--login-border);
			border-radius: 4px;
			color: #101828;
			font-size: 18px;
			box-shadow: none;
		}

		.login-form .form-control::placeholder {
			color: #9aa4b5;
		}

		.login-form .form-control:focus {
			border-color: #9dbcf0;
			box-shadow: 0 0 0 3px rgba(47, 115, 202, 0.12);
		}

		.password-label {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 16px;
		}

		.password-label a,
		.login-footer a {
			color: var(--login-link);
			text-decoration: none;
		}

		.password-label a:hover,
		.login-footer a:hover {
			text-decoration: underline;
		}

		.forgot-link {
			font-size: 18px;
			font-weight: 400;
			white-space: nowrap;
		}

		.login-button {
			height: 44px;
			margin-top: 22px;
			border: 0;
			border-radius: 4px;
			background: var(--login-blue);
			font-size: 18px;
			font-weight: 700;
		}

		.login-button:hover,
		.login-button:focus {
			background: #2767ba;
		}

		.login-footer {
			margin-top: 24px;
			color: var(--login-muted);
			font-size: 15px;
			text-align: left;
		}

		.brand-panel {
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 48px 64px;
			background: linear-gradient(145deg, #f4f8fd 0%, #e8f0fa 45%, #dce9f7 100%);
			border-left: 1px solid #e2e8f0;
		}

		.brand-content {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			width: min(100%, 560px);
			text-align: center;
		}

		.brand-logo-wrap {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 100%;
			padding: 32px 40px;
			border-radius: 20px;
			background: rgba(255, 255, 255, 0.85);
			box-shadow: 0 18px 48px rgba(30, 79, 143, 0.1);
		}

		.brand-logo {
			width: min(100%, 420px);
			height: auto;
			object-fit: contain;
		}

		.brand-tagline {
			margin: 28px 0 0;
			color: var(--login-blue-dark);
			font-size: 22px;
			font-weight: 700;
			line-height: 1.35;
			letter-spacing: -0.02em;
		}

		.brand-description {
			margin: 12px 0 0;
			max-width: 420px;
			color: var(--login-muted);
			font-size: 17px;
			line-height: 1.55;
		}

		.brand-badge {
			display: inline-block;
			margin-top: 28px;
			padding: 8px 18px;
			border-radius: 999px;
			background: var(--login-blue);
			color: #ffffff;
			font-size: 13px;
			font-weight: 600;
			letter-spacing: 0.04em;
			text-transform: uppercase;
		}

		@media (max-width: 991.98px) {
			.login-page {
				grid-template-columns: 1fr;
			}

			.login-panel {
				min-height: auto;
				padding: 44px 22px 28px;
			}

			.brand-panel {
				order: -1;
				padding: 36px 22px 28px;
				border-left: 0;
				border-bottom: 1px solid #e2e8f0;
			}

			.brand-logo-wrap {
				padding: 24px 28px;
			}

			.brand-logo {
				width: min(100%, 280px);
			}

			.brand-tagline {
				font-size: 19px;
			}

			.brand-description {
				font-size: 15px;
			}
		}

		@media (max-width: 575.98px) {
			.login-panel {
				align-items: flex-start;
			}

			.login-title {
				font-size: 21px;
			}

			.password-label {
				align-items: flex-start;
				flex-direction: column;
				gap: 4px;
			}

			.login-form .form-label,
			.forgot-link,
			.login-footer {
				font-size: 16px;
			}
		}
	</style>
</head>

<body>
	<main class="login-page">
		<section class="login-panel">
			<div class="login-form-wrap">
				<h1 class="login-title">Ingresa con tu cuenta</h1>
				<p class="login-subtitle">Accede a tu panel de gestión financiera.</p>

				<form id="loginForm" class="login-form" action="{{ route('auth.check') }}" method="POST" autocomplete="off">
					@csrf
					<div class="mb-3">
						<label class="form-label" for="user">Usuario</label>
						<input id="user" type="text" name="user" class="form-control @error('user') is-invalid @enderror"
							placeholder="Tu usuario" value="{{ old('user') }}" autocomplete="off">
						@error('user')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>

					<div class="mb-3">
						<label class="form-label password-label" for="password">
							<span>Contraseña</span>
							<a class="forgot-link" href="#">Olvidé mi contraseña</a>
						</label>
						<input id="password" type="password" name="password"
							class="form-control @error('password') is-invalid @enderror" placeholder="Tu contraseña"
							autocomplete="off">
						@error('password')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>

					<button id="loginBtn" type="submit" class="btn btn-primary w-100 login-button">Iniciar sesión</button>
				</form>

				<div class="login-footer">
					Elaborado por Xinergia de <a href="#">Corporacion Xpande</a>
				</div>
			</div>
		</section>

		<section class="brand-panel" aria-label="Xinergia">
			<div class="brand-content">
				<div class="brand-logo-wrap">
					<img src="{{ asset('assets/images/xinergia.png') }}" class="brand-logo" alt="Xinergia">
				</div>
				<p class="brand-tagline">Software de gestión para financieras</p>
				<p class="brand-description">Plataforma integral para administrar clientes, contratos, cobranzas y operaciones de tu empresa.</p>
				<span class="brand-badge">Producto Xinergia</span>
			</div>
		</section>
	</main>

	<script src="{{ asset('assets/js/tabler.min.js') }}"></script>
	<script src="{{ asset('assets/js/theme.min.js') }}"></script>
	<script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
	<script>
		$(document).ready(function() {
			$('#loginForm').submit(function() {
				$('#loginBtn').prop('disabled', true).text('Iniciando sesión...');
			});
		});
	</script>
</body>

</html>
