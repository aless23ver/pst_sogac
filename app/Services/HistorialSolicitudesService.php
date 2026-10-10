<?php

namespace App\Services;

use App\Models\EstadoSolicitud;
use App\Models\HistorialCambio;
use App\Models\HistorialEstadoSolicitud;
use App\Models\Solicitud;
use App\Models\TipoSolicitud;
use App\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consulta del historial de solicitudes de un estudiante.
 *
 * Todas las consultas parten de sol_usu_id: el filtro por estudiante no es una
 * comodidad del listado sino la garantia de que un alumno no pueda leer los
 * tramites de otro.
 */
class HistorialSolicitudesService
{
    /** Solicitudes por pagina en el listado del estudiante. */
    public const PAGINATE = 10;

    /**
     * Filtros admitidos por el listado, ya saneados.
     *
     * @param  array{q: string, estado: string, tipo: int|null, desde: string|null, hasta: string|null, orden: string}  $filtros
     */
    public function paginar(int $usuarioId, array $filtros): LengthAwarePaginator
    {
        $consulta = Solicitud::query()
            ->with(['tipoSolicitud', 'estadoActual'])
            ->where('sol_usu_id', $usuarioId);

        $termino = trim($filtros['q'] ?? '');

        if ($termino !== '') {
            // Sin escapar los comodines: el caracter de escape de LIKE no es el
            // mismo en Postgres (barra invertida) y en SQLite (ninguno), asi que
            // escaparlos obligaria a escribir SQL en crudo por motor.
            $patron = '%'.$termino.'%';
            $operador = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $consulta->where(function ($query) use ($patron, $operador) {
                $query->where('sol_id_seguimiento', $operador, $patron)
                    ->orWhere('sol_motivo_detallado', $operador, $patron);
            });
        }

        if (($filtros['estado'] ?? '') !== '') {
            $consulta->whereHas('estadoActual', fn ($query) => $query->where('eso_nombre_estado', $filtros['estado']));
        }

        if (! empty($filtros['tipo'])) {
            $consulta->where('sol_tsi_id', $filtros['tipo']);
        }

        if (! empty($filtros['desde'])) {
            $consulta->whereDate('sol_fecha_creacion', '>=', $filtros['desde']);
        }

        if (! empty($filtros['hasta'])) {
            $consulta->whereDate('sol_fecha_creacion', '<=', $filtros['hasta']);
        }

        // Por defecto la mas reciente primero, que es lo que se busca al entrar;
        // el orden inverso sirve para repasar lo mas antiguo.
        if (($filtros['orden'] ?? '') === 'antiguas') {
            $consulta->orderBy('sol_fecha_creacion');
        } else {
            $consulta->orderByDesc('sol_fecha_creacion');
        }

        return $consulta->paginate(self::PAGINATE)->withQueryString();
    }

    /**
     * Cifras de las tarjetas de arriba.
     *
     * Se calculan sobre TODAS las solicitudes del estudiante, no sobre el
     * resultado filtrado: el resumen responde "como voy", no "que coincide con
     * el filtro". Por eso se cuenta en SQL y no sobre la coleccion paginada.
     *
     * @return array{total: int, pendiente: int, aprobada: int, rechazada: int}
     */
    public function resumen(int $usuarioId): array
    {
        $conteo = Solicitud::query()
            ->join('estado_solicitudes', 'estado_solicitudes.eso_id', '=', 'solicitudes.sol_eso_id')
            ->where('sol_usu_id', $usuarioId)
            ->selectRaw('lower(estado_solicitudes.eso_nombre_estado) as estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $total = (int) $conteo->sum();

        return [
            'total' => $total,
            'pendiente' => (int) ($conteo['pendiente'] ?? 0),
            'aprobada' => (int) ($conteo['aprobada'] ?? 0),
            'rechazada' => (int) ($conteo['rechazada'] ?? 0),
        ];
    }

    /**
     * Estados y tipos de tramite para los desplegables del filtro.
     *
     * @return array{estados: Collection<int, string>, tipos: Collection<int, TipoSolicitud>}
     */
    public function opciones(): array
    {
        return [
            'estados' => EstadoSolicitud::orderBy('eso_nombre_estado')->pluck('eso_nombre_estado'),
            'tipos' => TipoSolicitud::orderBy('tsi_nombre_tipo')->get(['tsi_id', 'tsi_nombre_tipo']),
        ];
    }

    /**
     * Solicitud con todo lo que necesita su ficha: el historial de estados, los
     * adjuntos, la cita de validacion y la bitacora de cambios.
     *
     * @return array{solicitud: Solicitud, historial: Collection<int, HistorialEstadoSolicitud>, cambios: Collection<int, HistorialCambio>, esPropia: bool}
     */
    public function ficha(Solicitud $solicitud, Usuario $usuario): array
    {
        $solicitud->loadMissing(['tipoSolicitud', 'estadoActual', 'lapsoAcademico', 'usuario', 'documentaciones', 'cita']);

        return [
            'solicitud' => $solicitud,
            // El primero de cada cambio es el estado en el que nacio la
            // solicitud, asi que la linea de tiempo se arma de mas nuevo a mas
            // viejo incluyendo el alta.
            'historial' => $solicitud->historialEstados()
                ->with(['estadoAnterior', 'estadoNuevo', 'responsable'])
                ->orderByDesc('hes_fecha_cambio')
                ->get(),
            'cambios' => $solicitud->cambios()
                ->with('autor')
                ->orderByDesc('hcm_fecha')
                ->get(),
            'esPropia' => (int) $solicitud->sol_usu_id === (int) $usuario->usu_id,
            // Para el boton "Siguiente pendiente" del personal: revisa una
            // ficha y salta a la siguiente de la cola sin volver al listado.
            'siguientePendiente' => $usuario->esAdministrativo()
                ? $solicitud->siguientePendiente()
                : null,
        ];
    }

    /**
     * Deja una fecha solo si tiene el formato que entiende la base; cualquier
     * otra cosa se descarta en vez de acabar en un error 500.
     */
    public static function sanearFecha(?string $fecha): ?string
    {
        if ($fecha === null || trim($fecha) === '') {
            return null;
        }

        $fecha = trim($fecha);

        return Carbon::hasFormat($fecha, 'Y-m-d') ? $fecha : null;
    }
}
