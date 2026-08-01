<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  {{-- Puedes hacer el título dinámico, dejando uno por defecto --}}
  <title>Solicítalo — @yield('title', 'Configurar Requisitos')</title>
  
  {{-- Usamos asset() para apuntar correctamente a la carpeta public/ --}}
  <link rel="stylesheet" href="{{ asset('style_admin.css') }}" />
</head>
<body>
  <header class="topbar">
    <div class="container topbar__inner">
      {{-- Usamos route() en lugar de enlaces a archivos .php --}}
      <a href="{{ route('admin.dashboard') }}" class="brand">
        <span class="brand__mark">S</span>
        <span class="brand__name">Solicítalo <span style="font-size: 0.8rem; background: var(--red); padding: 2px 8px; border-radius: 4px;">ADMIN</span></span>
      </a>
      
      <nav class="nav">
        <a href="{{ route('admin.dashboard') }}">Gestión Solicitudes</a>
        {{-- Suponiendo que nombras a esta ruta 'admin.requisitos' --}}
        <a href="{{ route('admin.requisitos') }}" class="btn btn--primary btn--sm">Gestionar Requisitos</a>
        
        {{-- Reemplazamos el echo de PHP por las llaves de Blade y auth() --}}
        <span class="nav__user">Admin: {{ auth()->user()->nombre }}</span>
        
        {{-- El logout en Laravel debe ser por POST por seguridad, no por GET --}}
        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn--ghost">Salir</button>
        </form>
      </nav>
    </div>
  </header>
  
  {{-- Contenedor principal donde se insertará el código de otras vistas --}}
  <main>
      @yield("content")
  </main>
  
  <footer class="footer">
    <div class="container">
      {{-- Usamos la función now() de Laravel para el año --}}
      <p>&copy; {{ now()->year }} Solicítalo — Configurar Requisitos</p>
    </div>
  </footer>
</body>
</html>