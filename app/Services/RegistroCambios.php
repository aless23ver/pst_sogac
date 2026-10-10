<?php

namespace App\Services;

use App\Models\ChatSoporte\HiloChat;
use App\Models\EstadoSolicitud;
use App\Models\HistorialCambio;
use App\Models\LapsoAcademico;
use App\Models\Solicitud;
use App\Models\TipoSolicitud;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Escribe en la bitacora de cambios.
 *
 * Trabaja con los eventos de Eloquent (ver el trait App\Models\Concerns\RegistraCambios)
 * y se encarga de una cosa que el resto del sistema hace de otra manera: dejar
 * el cambio en texto legible. Guardar "sol_eso_id: 1 -> 2" no sirve ni para
 * entenderlo ahora ni dentro de un año, asi que los identificadores se traducen
 * a su nombre ("pendiente" -> "aprobada") antes de tocar la base.
 */
class RegistroCambios
{
    /** Interruptor global: los seeders y las pruebas lo apagan para poder fabricar datos. */
    protected static bool $activo = true;

    /**
     * Caracteres que se guardan de cada valor. Los motivos y las respuestas de
     * las preguntas frecuentes llegan a miles de letras y la bitacora solo
     * necesita dejar constancia de qué se tocó, no de archivarlo entero.
     */
    protected const LIMITE_VALOR = 200;

    /**
     * Columnas que nunca se guardan: son marcadores tecnicos que ensuciarian la
     * bitacora sin contar nada.
     *
     * @var list<string>
     */
    protected const COLUMNAS_TECNICAS = [
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
        'sol_fecha_ultima_actualizacion',
    ];

    /**
     * Campos cuyas claves foraneas hay que traducir a un nombre legible.
     *
     * Formato: columna => [modelo, clave primaria, columna con el nombre].
     * Con null en el tercer elemento se usa el nombre completo del usuario.
     *
     * @var array<string, array{class-string, string, string|null}>
     */
    protected const RESOLUTORES = [
        'sol_eso_id' => [EstadoSolicitud::class, 'eso_id', 'eso_nombre_estado'],
        'sol_tsi_id' => [TipoSolicitud::class, 'tsi_id', 'tsi_nombre_tipo'],
        'sol_lac_id' => [LapsoAcademico::class, 'lac_id', 'lac_id_lapso'],
        'sol_usu_id' => [Usuario::class, 'usu_id', null],
        'hch_id_usuario' => [Usuario::class, 'usu_id', null],
        'hch_id_admin' => [Usuario::class, 'usu_id', null],
    ];

    /**
     * Campos cuyo valor no es un identificador sino una clave de un catalogo
     * propio del modelo (por ejemplo los estados del chat).
     *
     * @var array<string, array{class-string, string}>
     */
    protected const CATALOGOS = [
        'hch_estado' => [HiloChat::class, 'estadosDisponibles'],
    ];

    /**
     * Etiquetas de cada columna por entidad. Lo que no aparezca aqui se muestra
     * con el nombre de la columna tal cual.
     *
     * @var array<string, array<string, string>>
     */
    protected const ETIQUETAS = [
        'Solicitud' => [
            'sol_id_seguimiento' => 'Código de seguimiento',
            'sol_tsi_id' => 'Trámite',
            'sol_lac_id' => 'Periodo académico',
            'sol_eso_id' => 'Estado',
            'sol_usu_id' => 'Estudiante',
            'sol_prioridad' => 'Prioridad',
            'sol_motivo_detallado' => 'Motivo detallado',
            'sol_fecha_creacion' => 'Fecha de creación',
            'sol_fecha_resolucion' => 'Fecha de resolución',
        ],
        'TipoSolicitud' => [
            'tsi_nombre_tipo' => 'Nombre',
            'tsi_descripcion' => 'Descripción',
            'tsi_tiempo_estimado_dias' => 'Tiempo estimado (días)',
            'tsi_requiere_aprobacion_especial' => 'Requiere aprobación especial',
            'tsi_estado_tipo' => 'Estado',
            'tsi_fecha_inicio' => 'Fecha de inicio',
            'tsi_fecha_fin' => 'Fecha de fin',
        ],
        'Requisito' => [
            'req_nombre_requisito' => 'Nombre',
            'req_descripcion' => 'Descripción',
            'req_formato_esperado' => 'Formato esperado',
        ],
        'PreguntasFrecuentes' => [
            'pregunta' => 'Pregunta',
            'respuesta' => 'Respuesta',
        ],
        'HiloChat' => [
            'hch_id_usuario' => 'Estudiante',
            'hch_id_admin' => 'Administrador',
            'hch_estado' => 'Estado',
            'hch_etiqueta_tema' => 'Tema',
            'hch_fecha_solicitud_cierre' => 'Fecha de solicitud de cierre',
        ],
        'Usuario' => [
            'usu_primer_nombre' => 'Primer nombre',
            'usu_segundo_nombre' => 'Segundo nombre',
            'usu_primer_apellido' => 'Primer apellido',
            'usu_segundo_apellido' => 'Segundo apellido',
            'usu_correo_electronico' => 'Correo electrónico',
            'usu_numero_telefono' => 'Teléfono',
            'usu_direccion' => 'Dirección',
            'usu_lugar_nacimiento' => 'Lugar de nacimiento',
            'usu_fecha_nacimiento' => 'Fecha de nacimiento',
            'usu_lapso_academico_previo' => 'Último lapso académico aprobado',
            'usu_datos_ultima_actualizacion' => 'Última actualización de datos',
        ],
        'DatosPrecargaConfig' => [
            'dpc_campo' => 'Campo',
            'dpc_etiqueta' => 'Etiqueta',
            'dpc_obligatorio' => 'Obligatorio',
            'dpc_activo' => 'Habilitado',
            'dpc_vigencia_meses' => 'Vigencia (meses)',
        ],
    ];

    /**
     * Ejecuta una operacion sin escribir en la bitacora.
     */
    public static function sinRegistro(callable $operacion): mixed
    {
        $anterior = static::$activo;
        static::$activo = false;

        try {
            return $operacion();
        } finally {
            static::$activo = $anterior;
        }
    }

    public static function desactivar(): void
    {
        static::$activo = false;
    }

    public static function activar(): void
    {
        static::$activo = true;
    }

    public static function activo(): bool
    {
        return static::$activo;
    }

    /**
     * Registro de un alta.
     */
    public static function creacion(Model $modelo): void
    {
        static::registrar($modelo, 'creo', static::diferenciasDeAlta($modelo));
    }

    /**
     * Registro de una modificacion, con el valor anterior y el nuevo de cada
     * columna que cambio.
     *
     * Ojo con los dos metodos que parecen servir para esto: getChanges()
     * devuelve los valores NUEVOS de las columnas tocadas, y getPrevious() los
     * que tenian antes del guardado. Comparar getChanges() consigo mismo daria
     * siempre "sin cambios", que es justo lo que hacia que las ediciones
     * quedaran registradas sin diferencias.
     */
    public static function actualizacion(Model $modelo): void
    {
        $visibles = static::atributosVisibles($modelo);
        $tocados = array_values(array_intersect(array_keys($modelo->getChanges()), $visibles));

        if ($tocados === []) {
            return;
        }

        static::registrar(
            $modelo,
            'actualizo',
            static::diferencias($modelo, $tocados, $modelo->getPrevious(), $modelo->getAttributes()),
        );
    }

    /**
     * Registro de una baja. Se guardan los valores que tenia el registro para
     * que quede constancia de lo que se elimino, no solo de su identificador.
     */
    public static function eliminacion(Model $modelo): void
    {
        static::registrar($modelo, 'elimino', static::diferenciasDeAlta($modelo));
    }

    /**
     * Diferencias de un alta o de una baja: todos los campos visibles del
     * registro, con "—" como valor anterior.
     *
     * @return array<int, array{campo: string, anterior: string, nuevo: string}>
     */
    protected static function diferenciasDeAlta(Model $modelo): array
    {
        return static::diferencias(
            $modelo,
            static::atributosVisibles($modelo),
            [],
            $modelo->getAttributes(),
        );
    }

    /**
     * Escribe la fila de la bitacora.
     */
    public static function registrar(
        Model $modelo,
        string $accion,
        array $diferencias,
        ?string $resumen = null,
    ): ?HistorialCambio {
        if (! static::$activo) {
            return null;
        }

        return HistorialCambio::create([
            'hcm_usu_id' => Auth::id(),
            'hcm_entidad' => class_basename($modelo),
            'hcm_entidad_id' => $modelo->getKey(),
            'hcm_sol_id' => static::solicitudIdRelacionada($modelo),
            'hcm_accion' => $accion,
            'hcm_resumen' => $resumen ?? static::resumen($accion, $modelo, $diferencias),
            'hcm_cambios' => $diferencias,
            'hcm_ruta' => static::recortar(Request::path(), 255),
            'hcm_ip' => Request::ip(),
            'hcm_navegador' => static::recortar((string) Request::userAgent(), 255),
            'hcm_fecha' => now(),
        ]);
    }

    /**
     * Construye la lista de diferencias ya en texto legible.
     *
     * La comparación de "ha cambiado" se hace sobre el valor entero, nunca
     * sobre el texto recortado: si se compararan los valores ya truncados, dos
     * motivos de 400 caracteres que solo se diferencian en la última frase
     * saldrían idénticos y el cambio se descartaría como si no hubiera habido
     * ninguno.
     *
     * @param  array<int, string>  $columnas
     * @param  array<string, mixed>  $previos
     * @param  array<string, mixed>  $actuales
     * @return array<int, array{campo: string, anterior: string, nuevo: string}>
     */
    protected static function diferencias(
        Model $modelo,
        array $columnas,
        array $previos,
        array $actuales,
    ): array {
        $etiquetas = self::ETIQUETAS[class_basename($modelo)] ?? [];

        $diferencias = [];

        foreach ($columnas as $columna) {
            $valorAnterior = $previos[$columna] ?? null;
            $valorNuevo = $actuales[$columna] ?? null;

            if (static::mismoValor($valorAnterior, $valorNuevo)) {
                continue;
            }

            $anterior = static::valorLegible($columna, $valorAnterior);
            $nuevo = static::valorLegible($columna, $valorNuevo);

            $diferencias[] = [
                'campo' => $etiquetas[$columna] ?? $columna,
                'anterior' => static::recortar($anterior),
                'nuevo' => static::recortarDistinguible($anterior, $nuevo),
            ];
        }

        return $diferencias;
    }

    /**
     * Dos valores cuentan como iguales cuando la base los guarda igual. El
     * detalle está en las cadenas vacías: estas columnas las deja vacías en
     * unos sitios y nulas en otros, y para el usuario no hay diferencia entre
     * "sin motivo" y "motivo en blanco".
     */
    protected static function mismoValor(mixed $anterior, mixed $nuevo): bool
    {
        return ($anterior === '' ? null : $anterior) === ($nuevo === '' ? null : $nuevo);
    }

    /**
     * Recorta el valor nuevo dejando ver en qué se diferenciaba del anterior.
     *
     * Cuando el cambio cae después del punto de corte, el valor recortado sería
     * una copia exacta del anterior y la tabla de diferencias enseñaría dos
     * celdas iguales sin explicar nada. En ese caso se añade el final del texto,
     * que es justo donde está lo que se editó.
     */
    protected static function recortarDistinguible(string $anterior, string $nuevo, int $limite = self::LIMITE_VALOR): string
    {
        $recortado = Str::limit($nuevo, $limite);

        if ($recortado === Str::limit($anterior, $limite) && mb_strlen($nuevo) > $limite) {
            return Str::limit($nuevo, $limite, '…['.mb_substr($nuevo, -40).']');
        }

        return $recortado;
    }

    /**
     * Columnas del modelo que interesan para la bitacora.
     *
     * @return list<string>
     */
    protected static function atributosVisibles(Model $modelo): array
    {
        return array_values(array_filter(
            array_keys($modelo->getAttributes()),
            fn (string $columna) => ! in_array($columna, self::COLUMNAS_TECNICAS, true)
                && ! str_ends_with($columna, '_hash')
                && ! str_ends_with($columna, '_token'),
        ));
    }

    /**
     * Frase corta que resume el cambio, para el listado y para el CSV.
     *
     * @param  array<int, array{campo: string, anterior: string, nuevo: string}>  $diferencias
     */
    protected static function resumen(string $accion, Model $modelo, array $diferencias): string
    {
        $entidad = HistorialCambio::etiquetaEntidad(class_basename($modelo));
        $referencia = $modelo->getKey() !== null ? ' #'.$modelo->getKey() : '';

        if ($accion === 'actualizo') {
            $campos = collect($diferencias)->pluck('campo')->all();

            if ($campos === []) {
                return 'Actualizó '.$entidad.$referencia;
            }

            $lista = implode(', ', array_slice($campos, 0, 3));

            return count($campos) > 3
                ? sprintf('Actualizó %d campos: %s y otros', count($campos), $lista)
                : 'Actualizó '.$lista;
        }

        return ($accion === 'creo' ? 'Alta de ' : 'Baja de ').$entidad.$referencia;
    }

    /**
     * Solicitud a la que pertenece el cambio, para poder filtrar la bitacora
     * por tramite. Solo las solicitudes se cuelgan de si mismas; el resto de
     * cambios (catalogo, preguntas frecuentes, chats) no tienen una solicitud
     * asociada y se consultan por entidad.
     */
    protected static function solicitudIdRelacionada(Model $modelo): ?int
    {
        return $modelo instanceof Solicitud ? (int) $modelo->getKey() : null;
    }

    /**
     * Traduce un valor de la base a algo que se pueda leer en pantalla.
     *
     * Devuelve el valor entero sin recortar: el recorte se aplica al montar
     * cada celda de la lista de diferencias, donde hace falta distinguir el
     * caso de "cambió más allá del punto de corte".
     */
    protected static function valorLegible(string $columna, mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }

        if (isset(self::RESOLUTORES[$columna])) {
            [$modelo, $clave, $columnaNombre] = self::RESOLUTORES[$columna];

            return static::resolverClaveForanea($modelo, $clave, $columnaNombre, $valor);
        }

        if (isset(self::CATALOGOS[$columna])) {
            [$modelo, $metodo] = self::CATALOGOS[$columna];

            return (string) ($modelo::$metodo()[$valor] ?? $valor);
        }

        return (string) $valor;
    }

    /**
     * Busca el nombre legible de una clave foranea.
     *
     * Sin cache a proposito: una cache estática sobrevive entre peticiones en
     * los procesos de larga duracion (Octane, colas) y devolvería el nombre
     * viejo de un tramite renombrado. Son una o dos consultas por cambio, que es
     * un precio irrelevante al lado de acertar.
     */
    protected static function resolverClaveForanea(
        string $modelo,
        string $clave,
        ?string $columna,
        mixed $valor,
    ): string {
        if (! is_scalar($valor)) {
            return static::recortar($valor);
        }

        $registro = $modelo::query()->where($clave, $valor)->first();

        return $registro === null
            ? 'desconocido'
            : ($columna === null ? (string) $registro->nombre_completo : (string) $registro->{$columna});
    }

    /**
     * Recorta valores muy largos (motivos, respuestas de las preguntas
     * frecuentes) para que la bitacora no crezca sin control.
     */
    protected static function recortar(mixed $valor, int $limite = self::LIMITE_VALOR): string
    {
        if (! is_scalar($valor)) {
            return '—';
        }

        $texto = trim((string) $valor);

        return $texto === '' ? '—' : Str::limit($texto, $limite);
    }
}
