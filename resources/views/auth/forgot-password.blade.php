<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Recuperar contraseña</title>
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
          <h1>Recuperar contraseña</h1>
          <p>Solicítalo · Sistema de Solicitudes Estudiantiles</p>
        </div>
        <div class="auth-card__body">

          {{-- Aviso de exito: el enlace de recuperacion se genero y se envio --}}
          @if (session('status'))
            <div class="alert alert--success">{{ session('status') }}</div>
          @endif

          @error('email')
            <div class="alert alert--error">{{ $message }}</div>
          @enderror

          <form action="{{ route('password.email') }}" method="post" class="form">
            @csrf

            <div class="field">
              <label for="email">Correo electrónico</label>
              <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus />
            </div>

            <button type="submit" class="btn btn--primary btn--block">Enviar enlace de recuperación</button>
          </form>
        </div>
        <div class="auth-card__foot">
          ¿Recordaste tu contraseña? <a href="{{ route('login') }}">Inicia sesión aquí</a>
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