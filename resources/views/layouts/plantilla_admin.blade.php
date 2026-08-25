<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Solicítalo — @yield('title', 'Panel Admin')</title>
  
  <link rel="stylesheet" href="{{ asset('style_admin.css') }}" />
</head>
<body>
  <header class="topbar">
    <div class="container topbar__inner">
      <a href="{{ route('admin.dashboard') }}" class="brand">
        <span class="brand__mark">S</span>
        <span class="brand__name">Solicítalo <span style="font-size: 0.8rem; background: var(--red); padding: 2px 8px; border-radius: 4px;">ADMIN</span></span>
      </a>
      
      <nav class="nav">
        <a href="{{ route('admin.dashboard') }}">Gestión Solicitudes</a>
        <a href="{{ route('admin.requisitos.index') }}" class="btn btn--primary btn--sm">Gestionar Requisitos</a>
        
        {{-- Solo diseño: Nombre estático por ahora --}}
        <span class="nav__user">Admin: Nombre de Prueba</span>
        
        {{-- Solo diseño: Botón estático que no hace peticiones POST todavía --}}
        <a href="#" class="btn btn--ghost">Salir</a>
      </nav>
    </div>
  </header>
  
  <main>
      @yield("content")
  </main>
  
  <footer class="footer">
    <div class="container">
      <p>&copy; {{ now()->year }} Solicítalo — Administrador</p>
    </div>
  </footer>
</body>
</html>