<?php

namespace App\Http\Controllers;

use App\Models\DatosPrecargaConfig;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Panel de datos de precarga de los estudiantes.
 *
 * Le muestra al administrador, en una sola tabla, qué datos de precarga tiene
 * cargado cada estudiante, cuántos le faltan de los habilitados y si siguen
 * vigentes según la caducidad configurada — con filtros por texto, estado de
 * completitud y vigencia, para no andar perdido entre cuentas.
 */
class AdminDatosEstudiantesController extends Controller
{
    /** Estudiantes por página. */
    private const PAGINATE = 15;

    public function index(Request $request): View
    {
        $filtros = [
            'q' => mb_substr(trim((string) $request->input('q', '')), 0, 100),
            'datos' => in_array($request->input('datos'), ['completos', 'parciales', 'sin-datos'], true)
                ? (string) $request->input('datos')
                : '',
            'vigencia' => in_array($request->input('vigencia'), ['vigentes', 'desactualizados'], true)
                ? (string) $request->input('vigencia')
                : '',
        ];

        $campos = DatosPrecargaConfig::camposActivos();

        // Cuántos meses de holgura tiene la vigencia más laxa entre los
        // campos que vencen: si la más larga ya venció, todas vencieron.
        $mesesVigencia = $campos
            ->filter(fn ($config) => $config->dpc_vigencia_meses !== null)
            ->map(fn ($config) => (int) $config->dpc_vigencia_meses)
            ->min();

        $estudiantes = $this->consultar($filtros, $campos, $mesesVigencia);

        // El resumen cuenta sobre todos los estudiantes, no sobre el
        // resultado filtrado: responde "cómo está el padrón", no "qué coincide".
        $resumen = $this->resumen($campos, $mesesVigencia);

        return view('admin.datos-estudiantes.index', [
            'estudiantes' => $estudiantes,
            'campos' => $campos,
            'filtros' => $filtros,
            'resumen' => $resumen,
            'mesesVigencia' => $mesesVigencia,
        ]);
    }

    /**
     * Expresión SQL que cuenta cuántos campos de precarga tiene cargados un
     * estudiante, para poder filtrar y paginar en la base y no en memoria.
     *
     * Las columnas de fecha no se comparan con la cadena vacía: Postgres no
     * acepta '' como fecha ("invalid input syntax for type date"), así que
     * para ellas basta con el IS NULL. El tipo se pregunta al schema para no
     * adivinarlo por el nombre del campo.
     *
     * Los nombres de columna salen de datos_precarga_config, no de la
     * petición, así que la expresión es segura.
     */
    private function expresionConteo(Collection $campos): string
    {
        return $campos
            ->map(function (DatosPrecargaConfig $config) {
                $columna = $config->dpc_campo;

                if (Schema::getColumnType('usuarios', $columna) === 'date') {
                    return sprintf('(CASE WHEN %s IS NULL THEN 0 ELSE 1 END)', $columna);
                }

                return sprintf("(CASE WHEN %s IS NULL OR %s = '' THEN 0 ELSE 1 END)", $columna, $columna);
            })
            ->implode(' + ');
    }

    /**
     * Listado filtrado de estudiantes con su conteo de datos cargados.
     *
     * El conteo se calcula en SQL (una expresión CASE por campo habilitado)
     * para que los filtros de completitud y la paginación funcionen sobre la
     * base y no sobre la página en memoria.
     *
     * @param  array{q: string, datos: string, vigencia: string}  $filtros
     * @param  Collection<int, DatosPrecargaConfig>  $campos
     */
    private function consultar(array $filtros, Collection $campos, ?int $mesesVigencia)
    {
        $expresion = $this->expresionConteo($campos);

        $total = $campos->count();

        $consulta = Usuario::query()
            ->where('usu_rol', Rol::ESTUDIANTE)
            ->selectRaw('usuarios.*, ('.($total > 0 ? $expresion : '0').') as datos_cargados');

        if ($filtros['q'] !== '') {
            $patron = '%'.$filtros['q'].'%';
            $consulta->where(function ($query) use ($patron) {
                $query->where('usu_primer_nombre', 'like', $patron)
                    ->orWhere('usu_primer_apellido', 'like', $patron)
                    ->orWhere('usu_correo_electronico', 'like', $patron)
                    ->orWhere('usu_numero_documento', 'like', $patron);
            });
        }

        if ($total > 0) {
            // El conteo se filtra con WHERE (repite la expresión CASE) porque
            // un HAVING sin GROUP BY colapsa todas las filas en un solo grupo
            // y no filtra estudiante por estudiante.
            $consulta
                ->when($filtros['datos'] === 'completos', fn ($q) => $q->whereRaw("({$expresion}) = {$total}"))
                ->when($filtros['datos'] === 'parciales', fn ($q) => $q->whereRaw("({$expresion}) > 0 AND ({$expresion}) < {$total}"))
                ->when($filtros['datos'] === 'sin-datos', fn ($q) => $q->whereRaw("({$expresion}) = 0"));
        }

        if ($filtros['vigencia'] !== '' && $mesesVigencia !== null) {
            $limite = now()->subMonths($mesesVigencia);

            $consulta->when($filtros['vigencia'] === 'desactualizados', function ($q) use ($limite) {
                $q->where(function ($sub) use ($limite) {
                    $sub->whereNull('usu_datos_ultima_actualizacion')
                        ->orWhere('usu_datos_ultima_actualizacion', '<', $limite);
                });
            })->when($filtros['vigencia'] === 'vigentes', function ($q) use ($limite) {
                $q->where('usu_datos_ultima_actualizacion', '>=', $limite);
            });
        } elseif ($filtros['vigencia'] === 'desactualizados') {
            // Sin vigencias configuradas nadie vence: el filtro no encuentra nada.
            $consulta->whereRaw('1 = 0');
        }

        return $consulta
            ->orderBy('usu_primer_apellido')
            ->orderBy('usu_primer_nombre')
            ->paginate(self::PAGINATE)
            ->withQueryString();
    }

    /**
     * Tarjetas de arriba: cómo está el padrón completo.
     *
     * @param  Collection<int, DatosPrecargaConfig>  $campos
     * @return array{total: int, completos: int, sinDatos: int, desactualizados: int}
     */
    private function resumen(Collection $campos, ?int $mesesVigencia): array
    {
        $expresion = $this->expresionConteo($campos);

        $base = Usuario::query()->where('usu_rol', Rol::ESTUDIANTE);
        $total = (clone $base)->count();

        if ($total === 0 || $campos->isEmpty()) {
            return ['total' => $total, 'completos' => 0, 'sinDatos' => 0, 'desactualizados' => 0];
        }

        $conteo = (clone $base)
            ->selectRaw("({$expresion}) as datos_cargados, usu_datos_ultima_actualizacion")
            ->get();

        $completos = $conteo->where('datos_cargados', $campos->count())->count();
        $sinDatos = $conteo->where('datos_cargados', 0)->count();

        $desactualizados = $mesesVigencia === null
            ? 0
            : $conteo->filter(fn ($fila) => $fila->usu_datos_ultima_actualizacion === null
                || $fila->usu_datos_ultima_actualizacion->lt(now()->subMonths($mesesVigencia)))
                ->count();

        return [
            'total' => $total,
            'completos' => $completos,
            'sinDatos' => $sinDatos,
            'desactualizados' => $desactualizados,
        ];
    }
}
