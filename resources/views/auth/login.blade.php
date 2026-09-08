<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar — IES Nuevo Horizonte</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --ies-primary: #1B4F5C;
            --ies-primary-rgb: 27, 79, 92;
            --ies-primary-dark: #123640;
            --ies-accent: #C1652F;
        }
        body {
            font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif;
            background: linear-gradient(160deg, var(--ies-primary) 0%, var(--ies-primary-dark) 100%);
            min-height: 100vh;
        }
        .login-brand {
            color: #fff;
            font-weight: 800;
            letter-spacing: -.01em;
        }
        .login-brand small {
            display: block;
            font-weight: 500;
            font-size: .8rem;
            color: rgba(255,255,255,.7);
        }
        .card {
            border: none;
            border-radius: .75rem;
            border-top: 4px solid var(--ies-accent);
        }
        .btn-primary {
            background-color: var(--ies-primary);
            border-color: var(--ies-primary);
            font-weight: 600;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--ies-primary-dark);
            border-color: var(--ies-primary-dark);
        }
        .form-control:focus {
            border-color: var(--ies-primary);
            box-shadow: 0 0 0 .2rem rgba(var(--ies-primary-rgb), .2);
        }
    </style>
</head>
<body class="d-flex align-items-center">
<div class="container" style="max-width: 380px;">
    <div class="text-center mb-4 login-brand">
        <div class="h4 mb-0">IES Nuevo Horizonte</div>
        <small>Sistema de gestión de espacios y turnos</small>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <label class="form-label small">Email</label>
                <input type="email" name="email" class="form-control mb-3" value="{{ old('email') }}" required autofocus>

                <label class="form-label small">Contraseña</label>
                <input type="password" name="password" class="form-control mb-3" required>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="recordar" id="recordar">
                    <label class="form-check-label small" for="recordar">Recordarme</label>
                </div>

                <button class="btn btn-primary w-100" type="submit">Ingresar</button>
            </form>
        </div>
    </div>
    <p class="text-center text-white-50 small mt-3">Acceso exclusivo para docentes y administración.</p>
</div>
</body>
</html>
