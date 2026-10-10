<?php

namespace App\Http\Controllers;

use App\Models\DatosPrecargaConfig;
use App\Services\RegistroCambios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Datos de precarga del estudiante.
 *
 * El estudiante llena sus datos una sola vez y el sistema los adjunta a sus
 * trámites, para que no tenga que volver a escribirlos en cada solicitud.
 *
 * Qué campos se piden —y cuáles son obligatorios o cada cuánto vencen— lo
 * decide el administrador en DatosPrecargaConfig; este controlador solo arma
 * el formulario con esa configuración y guarda lo que ella permite.
 */
class UserDatosPerfilController extends Controller
{
    /**
     * Reglas de validación de cada campo de precarga, sin la parte de
     * obligatoriedad: esa la aporta la configuración del admin.
     *
     * @var array<string, string>
     */
    private const REGLAS_PRECARGA = [
        'usu_numero_telefono' => 'string|max:20',
        'usu_direccion' => 'string|max:255',
        'usu_lugar_nacimiento' => 'string|max:100',
        // La fecha de nacimiento no puede ser futura: un dato "actual" es el
        // primer filtro de calidad antes de que llegue a un trámite.
        'usu_fecha_nacimiento' => 'date|before_or_equal:today',
        'usu_lapso_academico_previo' => 'string|max:50',
        'usu_pnf' => 'string|max:50',
        'usu_trayecto' => 'string|max:20',
    ];

    /**
     * Formulario con los datos de la cuenta y los de precarga configurados.
     */
    public function index(): View
    {
        $usuario = Auth::user();

        $campos = DatosPrecargaConfig::camposActivos();

        $vencidos = $campos
            ->filter(fn ($config) => ! $config->estaVigente($usuario->usu_datos_ultima_actualizacion))
            ->values();

        return view('user.datos-perfil', [
            'usuario' => $usuario,
            'campos' => $campos,
            'vencidos' => $vencidos,
            'ultimaActualizacion' => $usuario->usu_datos_ultima_actualizacion,
        ]);
    }

    /**
     * Guarda los datos del estudiante y deja constancia en la bitácora.
     */
    public function update(Request $request)
    {
        $usuario = Auth::user();

        $datosValidos = $request->validate(
            array_merge($this->reglasCuenta(), $this->reglasPrecarga())
        );

        // Diferencias contra los valores actuales, para la bitácora y para
        // saber si en realidad se tocó algo.
        $diferencias = $this->diferencias($usuario, $datosValidos);

        if ($diferencias === []) {
            return back()->with('info', 'No hubo cambios que guardar: los datos ya estaban así.');
        }

        foreach ($datosValidos as $columna => $valor) {
            $usuario->{$columna} = $valor;
        }

        $usuario->usu_datos_ultima_actualizacion = now();
        $usuario->save();

        // Auditoría: queda reflejado quién actualizó sus datos, cuándo y qué
        // cambió, igual que cualquier otra entidad del sistema.
        RegistroCambios::registrar(
            $usuario,
            'actualizo',
            $diferencias,
            'Actualizó sus datos personales (precarga)',
        );

        return back()->with('success', '¡Tus datos se guardaron! Se precargarán en tus próximos trámites.');
    }

    /**
     * Reglas de los datos básicos de la cuenta: nombre, apellidos y correo.
     *
     * @return array<string, string|array<int, string>>
     */
    private function reglasCuenta(): array
    {
        return [
            'usu_primer_nombre' => 'required|string|max:50',
            'usu_segundo_nombre' => 'nullable|string|max:50',
            'usu_primer_apellido' => 'required|string|max:50',
            'usu_segundo_apellido' => 'nullable|string|max:50',
            'usu_correo_electronico' => [
                'required',
                'string',
                'email',
                'max:100',
                'unique:usuarios,usu_correo_electronico,'.Auth::id().',usu_id',
            ],
        ];
    }

    /**
     * Reglas de los campos de precarga según la configuración del admin: los
     * deshabilitados no se validan ni se guardan, y los obligatorios exigen
     * un valor.
     *
     * @return array<string, string>
     */
    private function reglasPrecarga(): array
    {
        $reglas = [];

        foreach (DatosPrecargaConfig::camposActivos() as $config) {
            $base = self::REGLAS_PRECARGA[$config->dpc_campo] ?? 'string|max:255';
            $reglas[$config->dpc_campo] = ($config->dpc_obligatorio ? 'required|' : 'nullable|').$base;
        }

        return $reglas;
    }

    /**
     * Qué cambió de verdad entre lo que tenía el usuario y lo que envió.
     *
     * @param  array<string, mixed>  $datosValidos
     * @return array<int, array{campo: string, anterior: string, nuevo: string}>
     */
    private function diferencias($usuario, array $datosValidos): array
    {
        $etiquetas = $this->etiquetasPrecarga();

        $diferencias = [];

        foreach ($datosValidos as $columna => $nuevo) {
            $anterior = $usuario->{$columna};

            $nuevoTexto = trim((string) $nuevo);
            $anteriorTexto = trim((string) ($anterior ?? ''));

            if ($nuevoTexto === $anteriorTexto) {
                continue;
            }

            $diferencias[] = [
                'campo' => $etiquetas[$columna] ?? $columna,
                'anterior' => $anteriorTexto === '' ? '—' : mb_substr($anteriorTexto, 0, 200),
                'nuevo' => $nuevoTexto === '' ? '—' : mb_substr($nuevoTexto, 0, 200),
            ];
        }

        return $diferencias;
    }

    /**
     * Etiqueta legible de cada columna que puede tocar el estudiante, para la
     * bitácora.
     *
     * @return array<string, string>
     */
    private function etiquetasPrecarga(): array
    {
        $etiquetas = [
            'usu_primer_nombre' => 'Primer nombre',
            'usu_segundo_nombre' => 'Segundo nombre',
            'usu_primer_apellido' => 'Primer apellido',
            'usu_segundo_apellido' => 'Segundo apellido',
            'usu_correo_electronico' => 'Correo electrónico',
        ];

        foreach (DatosPrecargaConfig::todos() as $config) {
            $etiquetas[$config->dpc_campo] = $config->dpc_etiqueta;
        }

        return $etiquetas;
    }
}
