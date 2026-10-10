{{-- Franja institucional: la misma que corona el homepage institucional.

     Vive en un partial y no en cada plantilla porque aparece en las tres
     (panel del estudiante, panel del administrador y pantalla de acceso) y
     tiene que ser exactamente igual en las tres: si cada una la escribiera
     por su cuenta, el logo o el nombre de la institucion acabarian
     divergiendo sin que nadie se diera cuenta.

     El enlace de la marca vuelve al homepage, que es la pagina principal. --}}
<div class="cinta">
  <div class="container cinta__inner">
    <a href="{{ route('portada') }}" class="cinta__marca">
      <img src="{{ asset('portada/logo.png') }}" alt="Logo de la UPTP" class="cinta__logo">
      <span class="cinta__texto">
        <strong>UPTP</strong>
        <small>Juan de Jesús Montilla</small>
        <small>Sede Portuguesa</small>
      </span>
    </a>

    <span class="cinta__insignia">Control de Estudios</span>
  </div>
</div>