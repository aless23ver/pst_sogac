<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar Sesión</title>
  <link rel="stylesheet" href="{{ asset('style_admin.css') }}" />
</head>
<body>
  <header class="topbar">
    <div class="container topbar__inner">
      <a href="{{ route('login') }}" class="brand">
        <span class="brand__mark">S</span>
        <span class="brand__name">Solicítalo</span>
      </a>
    </div>
  </header>
  
  <main class="main container">
    <div class="auth-wrap">
      <div class="auth-card">
        <div class="auth-card__head">
          <h1>Bienvenido</h1>
          <p>Ingresa a tu cuenta estudiantil</p>
        </div>
        <div class="auth-card__body">
          
          <!-- Manejo de errores de Laravel -->
          @error('email')
            <div class="alert alert--error">{{ $message }}</div>
          @enderror

          <form action="{{ route('login.post') }}" method="post" class="form">
            @csrf <!-- Token de seguridad obligatorio en Laravel -->
            
            <div class="field">
              <label for="email">Correo electrónico</label>
              <!-- Mantenemos el correo si hubo un error al escribir la clave -->
              <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus />
            </div>
            
            <div class="field">
              <label for="password">Contraseña</label>
              <input type="password" id="password" name="password" required />
            </div>
            
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
      <p>&copy; {{ date('Y') }} Sistema de Solicitudes Estudiantiles</p>
    </div>
  </footer>
</body>
</html>