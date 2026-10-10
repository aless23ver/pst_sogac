<?php

namespace App\Http\Controllers;

use App\Services\SolicitudesEstadisticasService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminEstadisticasController extends Controller
{
    public function __construct(
        protected SolicitudesEstadisticasService $estadisticas,
    ) {
        // La protección por rol la aplica el middleware 'admin' del grupo de rutas.
    }

    /**
     * Panel estadístico del proceso de solicitudes.
     *
     * El rango de la serie temporal (7 / 30 / 90 días) se puede cambiar con ?dias=.
     */
    public function index(Request $request)
    {
        $diasSerie = (int) $request->input('dias', 30);

        if (! in_array($diasSerie, [7, 30, 90], true)) {
            $diasSerie = 30;
        }

        $metricas = $this->estadisticas->metrics($diasSerie);

        return view('admin.estadisticas', compact('metricas', 'diasSerie'));
    }

    /**
     * Exporta las mismas métricas a un CSV, para reportes o para revisarlos en Excel.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $diasSerie = (int) $request->input('dias', 30);

        if (! in_array($diasSerie, [7, 30, 90], true)) {
            $diasSerie = 30;
        }

        $metricas = $this->estadisticas->metrics($diasSerie);
        $nombreArchivo = 'estadisticas-solicitudes-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($metricas) {
            $salida = fopen('php://output', 'wb');

            // BOM para que Excel reconozca el UTF-8 y muestre bien los acentos.
            fwrite($salida, "\xEF\xBB\xBF");

            // Membrete institucional en la primera fila del reporte.
            fputcsv($salida, ['UPTP "Juan de Jesús Montilla" · Sede Portuguesa']);
            fputcsv($salida, ['Estadísticas de solicitudes — generado el '.now()->format('d/m/Y H:i')]);
            fputcsv($salida, []);

            $seccion = function (string $titulo, array $filas) use ($salida): void {
                fputcsv($salida, [$titulo]);
                foreach ($filas as $fila) {
                    fputcsv($salida, $fila);
                }
                fputcsv($salida, []);
            };

            $resumen = $metricas['resumen'];

            $seccion('Resumen general', [
                ['Indicador', 'Valor'],
                ['Total de solicitudes', $resumen['total']],
                ['Pendientes', $resumen['pendientes']],
                ['Aprobadas', $resumen['aprobadas']],
                ['Rechazadas', $resumen['rechazadas']],
                ['Tasa de resolución (%)', $resumen['tasa_resolucion']],
                ['Tasa de aprobación (%)', $resumen['tasa_aprobacion']],
                ['Tasa de rechazo (%)', $resumen['tasa_rechazo']],
            ]);

            $tiempos = $metricas['tiempos'];

            $seccion('Tiempos de resolución (días)', [
                ['Indicador', 'Valor'],
                ['Promedio', $tiempos['promedio'] ?? 'sin datos'],
                ['Mediana', $tiempos['mediana'] ?? 'sin datos'],
                ['Mínimo', $tiempos['minimo'] ?? 'sin datos'],
                ['Máximo', $tiempos['maximo'] ?? 'sin datos'],
                ['Percentil 90', $tiempos['percentil_90'] ?? 'sin datos'],
                ['Muestra (solicitudes medidas)', $tiempos['muestra']],
            ]);

            $seccion('Distribución por tipo de trámite', [
                ['Tipo de trámite', 'Total', 'Pendientes', 'Aprobadas', 'Rechazadas', 'Participación (%)', 'Aprobación (%)', 'Tiempo estimado (días)'],
                ...$metricas['por_tipo']->map(fn (array $fila) => [
                    $fila['nombre'],
                    $fila['total'],
                    $fila['pendientes'],
                    $fila['aprobadas'],
                    $fila['rechazadas'],
                    $fila['participacion'],
                    $fila['tasa_aprobacion'],
                    $fila['tiempo_estimado_dias'] ?? 'sin definir',
                ])->all(),
            ]);

            $seccion('Cumplimiento del tiempo estimado (SLA)', [
                ['Tipo de trámite', 'Tiempo estimado (días)', 'Promedio real (días)', 'Mediana real (días)', 'Dentro de plazo', 'Fuera de plazo', 'Cumplimiento (%)'],
                ...$metricas['sla_por_tipo']->map(fn (array $fila) => [
                    $fila['nombre'],
                    $fila['tiempo_estimado_dias'] ?? 'sin definir',
                    $fila['promedio_dias'] ?? 'sin datos',
                    $fila['mediana_dias'] ?? 'sin datos',
                    $fila['dentro_de_plazo'] ?? 'sin datos',
                    $fila['fuera_de_plazo'] ?? 'sin datos',
                    $fila['porcentaje_cumplimiento'] ?? 'sin datos',
                ])->all(),
            ]);

            $seccion('Rendimiento por administrador', [
                ['Administrador', 'Resoluciones', 'Aprobadas', 'Rechazadas', 'Tasa de aprobación (%)'],
                ...$metricas['por_responsable']->map(fn (array $fila) => [
                    $fila['nombre'],
                    $fila['total'],
                    $fila['aprobadas'],
                    $fila['rechazadas'],
                    $fila['tasa_aprobacion'],
                ])->all(),
            ]);

            $seccion('Antigüedad de las pendientes', [
                ['Rango', 'Solicitudes', 'Participación (%)'],
                ...array_map(fn (array $rango) => [
                    $rango['etiqueta'],
                    $rango['cantidad'],
                    $rango['porcentaje'],
                ], $metricas['antiguedad']['rangos']),
            ]);

            $seccion('Serie diaria (creadas vs resueltas)', [
                ['Fecha', 'Creadas', 'Resueltas'],
                ...array_map(fn (array $fila) => [
                    $fila['fecha'],
                    $fila['creadas'],
                    $fila['resueltas'],
                ], $metricas['serie']),
            ]);

            $seccion('Flujo de estados', [
                ['Estado anterior', 'Estado nuevo', 'Transiciones', 'Participación (%)'],
                ...$metricas['flujo']->map(fn (array $fila) => [
                    $fila['anterior'],
                    $fila['nuevo'],
                    $fila['total'],
                    $fila['participacion'],
                ])->all(),
            ]);

            fclose($salida);
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
