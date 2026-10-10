<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar Sesión</title>
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

      <a href="{{ route('portada') }}" class="nav__sitio">
        <i class="bi bi-house-door"></i> Ir al sitio
      </a>
    </div>
  </header>
  
  <main class="main container">
    <div class="auth-wrap">
      <div class="auth-card">
        <div class="auth-card__head">
          <h1>Bienvenido</h1>
          <p>Solicítalo · Sistema de Solicitudes Estudiantiles</p>
        </div>
        <div class="auth-card__body">
          
<!-- Manejo de errores de Laravel -->
          @error('identificador')
            <div class="alert alert--error">{{ $message }}</div>
          @enderror

          <form action="{{ route('login.post') }}" method="post" class="form">
            @csrf <!-- Token de seguridad obligatorio en Laravel -->
            
            <div class="field">
              <label for="identificador">Correo electrónico o número de documento</label>
              <!-- Mantenemos el valor si hubo un error al escribir la clave -->
              <input type="text" id="identificador" name="identificador" value="{{ old('identificador') }}" required autofocus />
            </div>
            
            <div class="field">
              <label for="password">Contraseña</label>
              <input type="password" id="password" name="password" required />
            </div>

            <div style="text-align:right;margin-top:-8px;">
              <a href="{{ route('password.request') }}" style="font-size:0.9rem;">¿Olvidaste tu contraseña?</a>
            </div>

            <label style="display:flex;align-items:center;gap:8px;font-size:0.9rem;color:var(--gray-700);cursor:pointer;">
              <input type="checkbox" name="remember" value="1" style="width:16px;height:16px;accent-color:var(--red);" />
              Mantener sesión iniciada
            </label>
            
            <button type="submit" class="btn btn--primary btn--block">Entrar</button>
          </form>
        </div>
        <div class="auth-card__foot">
          ¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate aquí</a>
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