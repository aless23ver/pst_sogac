<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registro de Cuenta | Solicítalo</title>
  <link rel="stylesheet" href="{{ asset('style_admin.css') }}" />
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
    <div class="auth-wrap auth-wrap--wide">
      <div class="auth-card">
        <div class="auth-card__head">
          <h1>Crear Cuenta Estudiantil</h1>
          <p>Ingresa tus datos personales y académicos para gestionar tus solicitudes en la UPTP</p>
        </div>

        <div class="auth-card__body">
          
          {{-- Alerta general de errores --}}
          @if ($errors->any())
            <div class="alert alert--error" role="alert">
              <strong>Por favor, corrige los errores en el formulario:</strong>
              <ul>
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form action="{{ route('register.post') }}" method="POST" class="form" novalidate>
            @csrf
            
            {{-- Sección: Datos Personales --}}
            <fieldset class="form__section">
              <legend>Información Personal</legend>

              <div class="form-row">
                <div class="field @error('primer_nombre') field--invalid @enderror">
                  <label for="primer_nombre">Primer Nombre</label>
                  <input type="text" id="primer_nombre" name="primer_nombre" value="{{ old('primer_nombre') }}" autocomplete="given-name" required autofocus />
                  @error('primer_nombre') <span class="field__error">{{ $message }}</span> @enderror
                </div>

                <div class="field @error('segundo_nombre') field--invalid @enderror">
                  <label for="segundo_nombre">Segundo Nombre <span class="field__hint">(Opcional)</span></label>
                  <input type="text" id="segundo_nombre" name="segundo_nombre" value="{{ old('segundo_nombre') }}" autocomplete="additional-name" />
                  @error('segundo_nombre') <span class="field__error">{{ $message }}</span> @enderror
                </div>
              </div>

              <div class="form-row">
                <div class="field @error('primer_apellido') field--invalid @enderror">
                  <label for="primer_apellido">Primer Apellido</label>
                  <input type="text" id="primer_apellido" name="primer_apellido" value="{{ old('primer_apellido') }}" autocomplete="family-name" required />
                  @error('primer_apellido') <span class="field__error">{{ $message }}</span> @enderror
                </div>

                <div class="field @error('segundo_apellido') field--invalid @enderror">
                  <label for="segundo_apellido">Segundo Apellido <span class="field__hint">(Opcional)</span></label>
                  <input type="text" id="segundo_apellido" name="segundo_apellido" value="{{ old('segundo_apellido') }}" autocomplete="additional-name" />
                  @error('segundo_apellido') <span class="field__error">{{ $message }}</span> @enderror
                </div>
              </div>

              <div class="form-row">
                <div class="field field--combo @error('cedula') field--invalid @enderror">
                  <label for="cedula">Cédula de Identidad</label>
                  <div class="input-group">
                    <select name="nacionalidad" id="nacionalidad" class="input-select--short" aria-label="Nacionalidad">
                      <option value="V" {{ old('nacionalidad', 'V') == 'V' ? 'selected' : '' }}>V-</option>
                      <option value="E" {{ old('nacionalidad') == 'E' ? 'selected' : '' }}>E-</option>
                    </select>
                    <input type="text" id="cedula" name="cedula" value="{{ old('cedula') }}" pattern="[0-9]{6,8}" maxlength="8" inputmode="numeric" required />
                  </div>
                  @error('cedula') <span class="field__error">{{ $message }}</span> @enderror
                </div>

                <div class="field @error('telefono') field--invalid @enderror">
                  <label for="telefono">Teléfono Móvil / WhatsApp</label>
                  <input type="tel" id="telefono" name="telefono" value="{{ old('telefono') }}" pattern="[0-9]{11}" maxlength="11" inputmode="tel" autocomplete="tel" required />
                  @error('telefono') <span class="field__error">{{ $message }}</span> @enderror
                </div>
              </div>
            </fieldset>

            {{-- Sección: Información Académica --}}
            <fieldset class="form__section">
              <legend>Información Académica (UPTP)</legend>

              <div class="form-row">
                <div class="field @error('pnf') field--invalid @enderror">
                  <label for="pnf">Programa Nacional de Formación (PNF)</label>
                  <select name="pnf" id="pnf" required>
                    <option value="" disabled {{ old('pnf') ? '' : 'selected' }}>Selecciona tu PNF...</option>
                    <option value="Informatica" {{ old('pnf') == 'Informatica' ? 'selected' : '' }}>PNF Informática</option>
                    <option value="Agroalimentacion" {{ old('pnf') == 'Agroalimentacion' ? 'selected' : '' }}>PNF Agroalimentación</option>
                    <option value="Administracion" {{ old('pnf') == 'Administracion' ? 'selected' : '' }}>PNF Administración</option>
                    <option value="Mantenimiento" {{ old('pnf') == 'Mantenimiento' ? 'selected' : '' }}>PNF Mantenimiento</option>
                    <option value="Electricidad" {{ old('pnf') == 'Electricidad' ? 'selected' : '' }}>PNF Electricidad</option>
                  </select>
                  @error('pnf') <span class="field__error">{{ $message }}</span> @enderror
                </div>

                <div class="field @error('trayecto') field--invalid @enderror">
                  <label for="trayecto">Trayecto Actual</label>
                  <select name="trayecto" id="trayecto" required>
                    <option value="" disabled {{ old('trayecto') ? '' : 'selected' }}>Selecciona...</option>
                    <option value="Inicial" {{ old('trayecto') == 'Inicial' ? 'selected' : '' }}>Trayecto Inicial</option>
                    <option value="1" {{ old('trayecto') == '1' ? 'selected' : '' }}>Trayecto I</option>
                    <option value="2" {{ old('trayecto') == '2' ? 'selected' : '' }}>Trayecto II</option>
                    <option value="3" {{ old('trayecto') == '3' ? 'selected' : '' }}>Trayecto III</option>
                    <option value="4" {{ old('trayecto') == '4' ? 'selected' : '' }}>Trayecto IV</option>
                  </select>
                  @error('trayecto') <span class="field__error">{{ $message }}</span> @enderror
                </div>
              </div>
            </fieldset>

            {{-- Sección: Credenciales de Acceso --}}
            <fieldset class="form__section" x-data="{ showPass: false }">
              <legend>Acceso al Sistema</legend>

              <div class="field @error('email') field--invalid @enderror">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required />
                @error('email') <span class="field__error">{{ $message }}</span> @enderror
              </div>

              <div class="form-row">
                <div class="field @error('password') field--invalid @enderror">
                  <label for="password">Contraseña</label>
                  <div class="input-toggle">
                    <input :type="showPass ? 'text' : 'password'" id="password" name="password" autocomplete="new-password" required minlength="8" />
                    <button type="button" class="btn-toggle-pass" @click="showPass = !showPass" :aria-label="showPass ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                      <svg x-show="!showPass" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-pass"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12c.074-.154.19-.328.312-.483C3.211 10.25 6.031 6 12 6c5.969 0 8.789 4.25 9.652 5.517.122.155.238.329.312.483.074.154.074.328 0 .483-.074.154-.19.328-.312.483C20.789 13.75 17.969 18 12 18c-5.969 0-8.789-4.25-9.652-5.517a1.05 1.05 0 0 1-.312-.483Zm10.964-2.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z" /></svg>
                      <svg x-show="showPass" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-pass"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228A3 3 0 0 1 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    </button>
                  </div>
                  @error('password') <span class="field__error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                  <label for="password_confirmation">Confirmar Contraseña</label>
                  <div class="input-toggle">
                    <input :type="showPass ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required minlength="8" />
                  </div>
                </div>
              </div>
            </fieldset>

            <button type="submit" class="btn btn--primary btn--block btn--lg">Crear Cuenta e Ingresar</button>
          </form>
        </div>

        <div class="auth-card__foot">
          ¿Ya tienes una cuenta registrada? <a href="{{ route('login') }}">Inicia sesión aquí</a>
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
```[cite: 1]
