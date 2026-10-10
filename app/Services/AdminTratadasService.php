<?php

namespace App\Services;

use App\Models\EstadoSolicitud;
use App\Models\HistorialEstadoSolicitud;
use App\Models\Rol;
use App\Models\Solicitud;
use App\Models\TipoSolicitud;
use App\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Consulta de solicitudes tratadas (con historial) para administradores.
 *
 * Una solicitud es "tratada" si tiene al menos un registro en historial_estado_solicitudes.
 * Eso cubre: aprobadas, rechazadas, y también pendientes que pasaron por algún estado
 * intermedio y volvieron (p. ej. devueltas a corrección).
 */
class AdminTratadasService
{
    /** Tratadas por página. */
    public const PAGINATE = 20;

    /**
     * Filtros admitidos por el listado, ya saneados.
     *
     * @param  array{
     *     q: string,
     *     estado: string,
     *     tipo: int|null,
     *     responsable: int|null,
     *     desde: string|null,
     *     hasta: string|null,
     *     orden: string
     * }  $filtros
     */
    public function paginar(array $filtros): LengthAwarePaginator
    {
        // Partimos de solicitudes que TIENEN historial (el JOIN implícito de whereHas
        // filtra las que no tienen ninguno).
        $consulta = Solicitud::query()
            ->with(['tipoSolicitud', 'estadoActual', 'usuario', 'historialEstados.responsable'])
            ->whereHas('historialEstados');

        $termino = trim($filtros['q'] ?? '');

        if ($termino !== '') {
            $patron = '%'.$termino.'%';
            $operador = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $consulta->where(function ($query) use ($patron, $operador) {
                $query->where('sol_id_seguimiento', $operador, $patron)
                    ->orWhere('sol_motivo_detallado', $operador, $patron)
                    ->orWhereHas('usuario', function ($q) use ($patron, $operador) {
                        $q->where('usu_numero_documento', $operador, $patron)
                            ->orWhere('usu_primer_nombre', $operador, $patron)
                            ->orWhere('usu_primer_apellido', $operador, $patron);
                    });
            });
        }

        if (($filtros['estado'] ?? '') !== '') {
            $consulta->whereHas('estadoActual', fn ($q) => $q->where('eso_nombre_estado', $filtros['estado']));
        }

        if (! empty($filtros['tipo'])) {
            $consulta->where('sol_tsi_id', $filtros['tipo']);
        }

        if (! empty($filtros['responsable'])) {
            $consulta->whereHas('historialEstados', function ($q) use ($filtros) {
                $q->where('hes_usu_id_responsable', $filtros['responsable']);
            });
        }

        if (! empty($filtros['desde'])) {
            $consulta->whereHas('historialEstados', function ($q) use ($filtros) {
                $q->whereDate('hes_fecha_cambio', '>=', $filtros['desde']);
            });
        }

        if (! empty($filtros['hasta'])) {
            $consulta->whereHas('historialEstados', function ($q) use ($filtros) {
                $q->whereDate('hes_fecha_cambio', '<=', $filtros['hasta']);
            });
        }

        // Por defecto la más reciente resolución primero.
        if (($filtros['orden'] ?? '') === 'antiguas') {
            $consulta->orderBy('sol_fecha_creacion');
        } else {
            $consulta->orderByDesc('sol_fecha_resolucion');
        }

        return $consulta->paginate(self::PAGINATE)->withQueryString();
    }

    /**
     * Cifras globales de solicitudes tratadas, para las tarjetas de arriba.
     *
     * @return array{total: int, aprobada: int, rechazada: int, pendiente: int}
     */
    public function resumen(): array
    {
        $conteo = Solicitud::query()
            ->join('estado_solicitudes', 'estado_solicitudes.eso_id', '=', 'solicitudes.sol_eso_id')
            ->whereHas('historialEstados')
            ->selectRaw('lower(estado_solicitudes.eso_nombre_estado) as estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return [
            'total' => (int) $conteo->sum(),
            'aprobada' => (int) ($conteo['aprobada'] ?? 0),
            'rechazada' => (int) ($conteo['rechazada'] ?? 0),
            'pendiente' => (int) ($conteo['pendiente'] ?? 0),
        ];
    }

    /**
     * Opciones para los desplegables del filtro.
     *
     * @return array{
     *     estados: Collection<int, string>,
     *     tipos: Collection<int, TipoSolicitud>,
     *     responsables: Collection<int, Usuario>
     * }
     */
    public function opciones(): array
    {
        return [
            'estados' => EstadoSolicitud::orderBy('eso_nombre_estado')->pluck('eso_nombre_estado'),
            'tipos' => TipoSolicitud::orderBy('tsi_nombre_tipo')->get(['tsi_id', 'tsi_nombre_tipo']),
            'responsables' => Usuario::query()
                // Se listan los roles administrativos de la jerarquía actual
                // (administrador, analista y taquillero). Antes se filtraba por
                // el rol 'admin', que la migración 2026_10_04_025359 renombró a
                // 'administrador': el desplegable salía vacío y el filtro por
                // responsable se descartaba en silencio.
                ->whereIn('usu_rol', Rol::administrativos())
                ->whereIn('usu_id', HistorialEstadoSolicitud::query()->distinct()->select('hes_usu_id_responsable'))
                ->orderBy('usu_primer_nombre')
                ->orderBy('usu_primer_apellido')
                ->get(),
        ];
    }

    /**
     * Ficha completa de una solicitud tratada.
     *
     * @return array{
     *     solicitud: Solicitud,
     *     historial: Collection<int, HistorialEstadoSolicitud>,
     *     tiempoResolucion: string|null,
     *     primerResponsable: Usuario|null,
     *     ultimaObservacion: string|null,
     * }
     */
    public function ficha(Solicitud $solicitud): array
    {
        $solicitud->loadMissing([
            'tipoSolicitud',
            'estadoActual',
            'lapsoAcademico',
            'usuario',
            'documentaciones',
            'cita',
            'historialEstados' => ['estadoAnterior', 'estadoNuevo', 'responsable'],
        ]);

        $historial = $solicitud->historialEstados()
            ->with(['estadoAnterior', 'estadoNuevo', 'responsable'])
            ->orderBy('hes_fecha_cambio')
            ->get();

        // Tiempo total desde creación hasta la primera resolución (aprobada/rechazada).
        // Ojo: firstWhere() no admite closures en el valor (compara el campo contra
        // la closure y nunca coincide), por eso se filtra con first().
        $primeraResolucion = $historial->first(
            fn ($movimiento) => in_array($movimiento->estadoNuevo?->eso_nombre_estado, ['aprobada', 'rechazada'], true)
        );
        $tiempoResolucion = null;
        if ($primeraResolucion && $solicitud->sol_fecha_creacion) {
            $tiempoResolucion = $solicitud->sol_fecha_creacion->diffForHumans($primeraResolucion->hes_fecha_cambio, true);
        }

        // Primer responsable que movió la solicitud (para atribución)
        $primerResponsable = $historial->first()?->responsable;

        // Última observación escrita (la más reciente del historial)
        $ultimaObservacion = $historial->last()?->hes_observaciones_comentarios;

        return [
            'solicitud' => $solicitud,
            'historial' => $historial,
            'tiempoResolucion' => $tiempoResolucion,
            'primerResponsable' => $primerResponsable,
            'ultimaObservacion' => $ultimaObservacion,
            // Para el boton "Siguiente pendiente": revisar las fichas en
            // cadena sin volver al listado.
            'siguientePendiente' => $solicitud->siguientePendiente(),
        ];
    }

    /**
     * Sanea una fecha: solo acepta Y-m-d, descarta lo demás.
     */
    public static function sanearFecha(?string $fecha): ?string
    {
        if ($fecha === null || trim($fecha) === '') {
            return null;
        }

        return Carbon::hasFormat(trim($fecha), 'Y-m-d') ? trim($fecha) : null;
    }
}
