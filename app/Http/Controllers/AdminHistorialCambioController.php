<?php

namespace App\Http\Controllers;

use App\Services\HistorialCambiosService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bitacora de cambios: que se toco en el sistema, quien lo toco y con que
 * valores antes y despues.
 *
 * Es un modulo aparte del historial de solicitudes a proposito. El historial
 * cuenta las solicitudes y su recorrido por los estados; la bitacora cuenta
 * TODO lo que se modifica en el sistema, incluidos los tramites, los
 * requisitos, las preguntas frecuentes y los chats. Juntarlos en una sola
 * pantalla haria que ninguna se entendiera.
 *
 * El acceso lo garantiza el middleware 'admin' del grupo /admin, asi que aqui
 * no se repite la comprobacion de rol.
 */
class AdminHistorialCambioController extends Controller
{
    public function __construct(protected HistorialCambiosService $cambios) {}

    /**
     * Listado de la bitacora con sus filtros.
     */
    public function index(Request $request): View
    {
        $filtros = self::filtrosDesde($request);

        return view('admin.cambios.index', [
            'cambios' => $this->cambios->paginar($filtros),
            'resumen' => $this->cambios->resumen(),
            'filtros' => $filtros,
            'entidades' => $this->cambios->entidadesRegistradas(),
            'autores' => $this->cambios->autoresRegistrados(),
            'rangoInvertido' => HistorialCambiosService::rangoInvertido($filtros),
        ]);
    }

    /**
     * Exporta el listado filtrado a CSV para revisarlo en Excel o adjuntarlo a
     * un informe.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $filtros = self::filtrosDesde($request);

        return response()->streamDownload(function () use ($filtros): void {
            $salida = fopen('php://output', 'wb');

            // BOM para que Excel reconozca el UTF-8 y muestre bien los acentos.
            fwrite($salida, "\xEF\xBB\xBF");

            // Membrete institucional: la primera fila identifica de dónde sale
            // el reporte, útil al imprimirlo o adjuntarlo a un expediente.
            fputcsv($salida, ['UPTP "Juan de Jesús Montilla" · Sede Portuguesa']);
            fputcsv($salida, ['Bitácora de cambios — generado el '.now()->format('d/m/Y H:i')]);
            fputcsv($salida, []);

            fputcsv($salida, ['Fecha', 'Autor', 'Rol', 'Entidad', 'Registro', 'Acción', 'Resumen', 'Campo', 'Valor anterior', 'Valor nuevo', 'Origen', 'IP']);
            fputcsv($salida, []);

            foreach ($this->cambios->paraExportar($filtros) as $cambio) {
                $diferencias = $cambio->diferencias;

                // Un cambio sin diferencias (por ejemplo un alta de un registro
                // sin campos visibles) ocupa una fila con las celdas de valor
                // vacias, para que quede constancia igual.
                if ($diferencias === []) {
                    fputcsv($salida, [
                        $cambio->hcm_fecha->format('d/m/Y H:i'),
                        $cambio->autor_nombre,
                        $cambio->autor?->usu_rol ?? 'sistema',
                        $cambio->entidad_etiqueta,
                        $cambio->hcm_entidad_id,
                        $cambio->accion_etiqueta,
                        $cambio->hcm_resumen,
                        '', '', '',
                        $cambio->hcm_ruta,
                        $cambio->hcm_ip,
                    ]);

                    continue;
                }

                // Un cambio con varias diferencias genera una fila por campo: en
                // una hoja de calculo es la unica forma de no perder nada.
                foreach ($diferencias as $diferencia) {
                    fputcsv($salida, [
                        $cambio->hcm_fecha->format('d/m/Y H:i'),
                        $cambio->autor_nombre,
                        $cambio->autor?->usu_rol ?? 'sistema',
                        $cambio->entidad_etiqueta,
                        $cambio->hcm_entidad_id,
                        $cambio->accion_etiqueta,
                        $cambio->hcm_resumen,
                        $diferencia['campo'],
                        $diferencia['anterior'],
                        $diferencia['nuevo'],
                        $cambio->hcm_ruta,
                        $cambio->hcm_ip,
                    ]);
                }
            }

            fclose($salida);
        }, 'historial-cambios-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Sanea los parametros del listado una sola vez, para que la pagina y el
     * CSV filtren exactamente por lo mismo.
     *
     * @return array{q: string, entidad: string, accion: string, autor: int|null, desde: string|null, hasta: string|null}
     */
    protected static function filtrosDesde(Request $request): array
    {
        return HistorialCambiosService::sanearFiltros($request->query());
    }
}
