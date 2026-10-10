<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Establecer nueva contraseña</title>
  <link rel="stylesheet" href="{{ asset('style_admin.css') }}" />
</head>
<body>
  @include('partials.cabecera')

  <header class="topbar">
    <div class="container topbar__inner">
      <a href="{{ route('portada') }}" class="brand" style="text-decoration:none;">
        <span class="brand__mark">S</span>
        <span class="brand__name">Solicítalo</span>
      </a>

      <a href="{{ route('login') }}" class="nav__sitio">
        <i class="bi bi-house-door"></i> Ir al sitio
      </a>
    </div>
  </header>

  <main class="main container">
    <div class="auth-wrap">
      <div class="auth-card">
        <div class="auth-card__head">
          <h1>Nueva contraseña</h1>
          <p>Solicítalo · Sistema de Solicitudes Estudiantiles</p>
        </div>
        <div class="auth-card__body">

          @error('email')
            <div class="alert alert--error">{{ $message }}</div>
          @enderror

          @error('password')
            <div class="alert alert--error">{{ $message }}</div>
          @enderror

          <form action="{{ route('password.update') }}" method="post" class="form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}" />

            <div class="field">
              <label for="email">Correo electrónico</label>
              <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required />
            </div>

            <div class="field">
              <label for="password">Nueva contraseña</label>
              <input type="password" id="password" name="password" required />
              <span class="field__hint">Mínimo 8 caracteres.</span>
            </div>

            <div class="field">
              <label for="password_confirmation">Repite la contraseña</label>
              <input type="password" id="password_confirmation" name="password_confirmation" required />
            </div>

            <button type="submit" class="btn btn--primary btn--block">Guardar contraseña</button>
          </form>
        </div>
        <div class="auth-card__foot">
          <a href="{{ route('login') }}">Volver al inicio de sesión</a>
        </div>
      </div>
    </div>
  </main>

  <footer class="footer">
    <div class="container">
      <p>&copy; {{ date('Y') }} Sistema de Solicitudes Estudiantiles — UPTP "Juan de Jesús Montilla" · Sede Portuguesa</p>
    </div>
  </footer>
</body>
</html>