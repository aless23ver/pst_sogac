<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registro de Cuenta</title>
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
          <h1>Crear Cuenta</h1>
          <p>Regístrate en el sistema estudiantil</p>
        </div>
        <div class="auth-card__body">
          
          <!-- Errores de validación -->
          @if ($errors->any())
            <div class="alert alert--error">
              <ul>
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form action="{{ route('register.post') }}" method="post" class="form">
            @csrf
            
            <div class="field">
              <label for="nombre">Primer Nombre</label>
              <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" required autofocus />
            </div>

            <div class="field">
              <label for="apellido">Primer Apellido</label>
              <input type="text" id="apellido" name="apellido" value="{{ old('apellido') }}" required />
            </div>

            <div class="field">
              <label for="documento">Número de Documento</label>
              <input type="text" id="documento" name="documento" value="{{ old('documento') }}" required />
            </div>

            <div class="field">
              <label for="email">Correo Electrónico</label>
              <input type="email" id="email" name="email" value="{{ old('email') }}" required />
            </div>
            
            <div class="field">
              <label for="password">Contraseña</label>
              <input type="password" id="password" name="password" required />
            </div>
            
            <button type="submit" class="btn btn--primary btn--block">Registrarse</button>
          </form>
        </div>
        <div class="auth-card__foot">
          ¿Ya tienes una cuenta? <a href="{{ route('login') }}">Inicia sesión aquí</a>
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