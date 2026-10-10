<?php

namespace App\Http\Controllers;

use App\Models\DatosPrecargaConfig;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Configuración de los datos de precarga del estudiante.
 *
 * Aquí el administrador define la "versión" de los datos que los estudiantes
 * pueden precargar: qué campos están habilitados, cuáles son obligatorios y
 * cada cuántos meses pierden vigencia. El formulario del estudiante se arma
 * solo con esta configuración, así que los cambios aplican de inmediato.
 *
 * Cada ajuste queda en la bitácora de cambios: el modelo usa el trait
 * RegistraCambios, que registra las modificaciones sin que este controlador
 * tenga que acordarse de hacerlo.
 */
class AdminDatosPrecargaController extends Controller
{
    /**
     * Listado de los campos configurados, activos e inactivos.
     */
    public function index(): View
    {
        return view('admin.datos-precarga.index', [
            'campos' => DatosPrecargaConfig::todos(),
        ]);
    }

    /**
     * Formulario de alta de un campo de precarga, en su propia pantalla:
     * el listado queda solo para revisar y ajustar lo que ya existe.
     */
    public function create(): View
    {
        return view('admin.datos-precarga.create', [
            'columnasDisponibles' => DatosPrecargaConfig::columnasDisponibles(),
        ]);
    }

    /**
     * Agrega un campo de precarga nuevo.
     *
     * El dato se guarda en una columna real de la tabla usuarios, así que la
     * columna se elige de una lista cerrada: las ofertables que todavía no
     * están configuradas. No se puede inventar una columna desde la pantalla
     * porque no habría dónde guardar el valor.
     */
    public function store(Request $request)
    {
        $disponibles = implode(',', array_keys(DatosPrecargaConfig::columnasDisponibles()));

        $datos = $request->validate([
            'dpc_campo' => 'required|string|in:'.$disponibles,
            'dpc_etiqueta' => 'required|string|max:100',
            'dpc_obligatorio' => 'required|boolean',
            'dpc_activo' => 'required|boolean',
            'dpc_vigencia_meses' => 'nullable|integer|min:1|max:120',
        ], [
            'dpc_campo.in' => 'Ese dato no está disponible para precarga o ya fue configurado.',
            'dpc_vigencia_meses.integer' => 'La vigencia debe ser un número de meses (o vacía para que no venza).',
            'dpc_vigencia_meses.min' => 'La vigencia mínima es de 1 mes.',
        ]);

        // El select envía '' cuando se elige "no vence".
        $datos['dpc_vigencia_meses'] = $datos['dpc_vigencia_meses'] !== null
            ? (int) $datos['dpc_vigencia_meses']
            : null;
        $datos['dpc_obligatorio'] = (bool) $datos['dpc_obligatorio'];
        $datos['dpc_activo'] = (bool) $datos['dpc_activo'];

        $campo = DatosPrecargaConfig::create($datos);

        return redirect()
            ->route('admin.datos-precarga.index')
            ->with(
                'success',
                "Campo «{$campo->dpc_etiqueta}» agregado. El formulario del estudiante ya lo refleja."
            );
    }

    /**
     * Guarda el ajuste de un campo.
     *
     * Solo se tocan los atributos de control (etiqueta, obligatorio,
     * habilitado, vigencia): el nombre de la columna de la base no se edita
     * desde la pantalla porque es lo que conecta la configuración con los
     * datos reales de cada estudiante.
     */
    public function update(Request $request, DatosPrecargaConfig $config)
    {
        $datos = $request->validate([
            'dpc_etiqueta' => 'required|string|max:100',
            'dpc_obligatorio' => 'required|boolean',
            'dpc_activo' => 'required|boolean',
            'dpc_vigencia_meses' => 'nullable|integer|min:1|max:120',
        ], [
            'dpc_vigencia_meses.integer' => 'La vigencia debe ser un número de meses (o vacía para que no venza).',
            'dpc_vigencia_meses.min' => 'La vigencia mínima es de 1 mes.',
        ]);

        // El select envía '' cuando se elige "no vence".
        $datos['dpc_vigencia_meses'] = $datos['dpc_vigencia_meses'] !== null
            ? (int) $datos['dpc_vigencia_meses']
            : null;
        $datos['dpc_obligatorio'] = (bool) $datos['dpc_obligatorio'];
        $datos['dpc_activo'] = (bool) $datos['dpc_activo'];

        $config->update($datos);

        return back()->with(
            'success',
            "Campo «{$config->dpc_etiqueta}» actualizado. El formulario del estudiante ya lo refleja."
        );
    }
}
