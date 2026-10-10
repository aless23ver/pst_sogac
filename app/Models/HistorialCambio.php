<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Un cambio registrado en la bitacora del sistema.
 *
 * La escribe sola el trait App\Models\Concerns\RegistraCambios a partir de los
 * eventos de Eloquent, asi que ningun controller tiene que acordarse de
 * registrarla. El modelo es de solo lectura: no tiene Updating ni Deleting,
 * porque una bitacora que se puede editar no sirve para auditar nada.
 *
 * @property int $hcm_id
 * @property int|null $hcm_usu_id
 * @property string $hcm_entidad
 * @property int|null $hcm_entidad_id
 * @property int|null $hcm_sol_id
 * @property string $hcm_accion
 * @property string|null $hcm_resumen
 * @property array<int, array{campo: string, anterior: string, nuevo: string}>|null $hcm_cambios
 * @property string|null $hcm_ruta
 * @property string|null $hcm_ip
 * @property string|null $hcm_navegador
 * @property Carbon $hcm_fecha
 */
class HistorialCambio extends Model
{
    protected $table = 'historial_cambios';

    protected $primaryKey = 'hcm_id';

    public $timestamps = false;

    protected $fillable = [
        'hcm_usu_id',
        'hcm_entidad',
        'hcm_entidad_id',
        'hcm_sol_id',
        'hcm_accion',
        'hcm_resumen',
        'hcm_cambios',
        'hcm_ruta',
        'hcm_ip',
        'hcm_navegador',
        'hcm_fecha',
    ];

    /** Acciones que registra el trait, con su texto y su chip para pantalla. */
    public const ACCIONES = [
        'creo' => ['etiqueta' => 'Creó', 'chip' => 'chip--ok'],
        'actualizo' => ['etiqueta' => 'Actualizó', 'chip' => 'chip--neutro'],
        'elimino' => ['etiqueta' => 'Eliminó', 'chip' => 'chip--error'],
    ];

    /**
     * Entidades que se auditan, con el nombre que se muestra en pantalla.
     *
     * El filtro del listado se arma con los valores que de verdad hay en la
     * tabla, asi que esta lista no es un menu de filtros sino el respaldo para
     * cuando el listado llega vacio y todavia hay que explicar de que trata
     * la bitacora.
     *
     * @var array<string, array{singular: string, plural: string}>
     */
    public const ENTIDADES = [
        'Solicitud' => ['singular' => 'Solicitud', 'plural' => 'Solicitudes'],
        'TipoSolicitud' => ['singular' => 'Tipo de trámite', 'plural' => 'Tipos de trámite'],
        'Requisito' => ['singular' => 'Requisito', 'plural' => 'Requisitos'],
        'PreguntasFrecuentes' => ['singular' => 'Pregunta frecuente', 'plural' => 'Preguntas frecuentes'],
        'HiloChat' => ['singular' => 'Chat de soporte', 'plural' => 'Chats de soporte'],
        // Datos de precarga: el estudiante actualiza su perfil y el admin
        // ajusta qué campos se piden (DatosPrecargaConfig).
        'Usuario' => ['singular' => 'Datos de estudiante', 'plural' => 'Datos de estudiantes'],
        'DatosPrecargaConfig' => ['singular' => 'Campo de precarga', 'plural' => 'Configuración de precarga'],
    ];

    /**
     * Nombre legible de una entidad, en singular o en plural.
     */
    public static function etiquetaEntidad(string $entidad, bool $plural = false): string
    {
        return self::ENTIDADES[$entidad][$plural ? 'plural' : 'singular'] ?? $entidad;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hcm_cambios' => 'array',
            'hcm_fecha' => 'datetime',
        ];
    }

    /**
     * Quien hizo el cambio. Puede venir en null si el usuario fue borrado o si
     * el cambio lo hizo un proceso de consola.
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'hcm_usu_id', 'usu_id');
    }

    /**
     * Solicitud afectada, cuando el cambio pertenece al circuito de tramites.
     */
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'hcm_sol_id', 'sol_id');
    }

    public function getAccionEtiquetaAttribute(): string
    {
        return self::ACCIONES[$this->hcm_accion]['etiqueta'] ?? ucfirst((string) $this->hcm_accion);
    }

    public function getAccionChipAttribute(): string
    {
        return self::ACCIONES[$this->hcm_accion]['chip'] ?? 'chip--neutro';
    }

    /**
     * Nombre del autor listo para pintar, o "Sistema" cuando no hay sesion
     * (por ejemplo, un comando de consola o una carga de datos iniciales).
     */
    public function getAutorNombreAttribute(): string
    {
        return $this->autor?->nombre_completo ?? 'Sistema';
    }

    /**
     * Entidad con su nombre legible ("Tipo de trámite" en vez de "TipoSolicitud").
     */
    public function getEntidadEtiquetaAttribute(): string
    {
        return self::etiquetaEntidad($this->hcm_entidad);
    }

    /**
     * Diferencias registradas, siempre como lista (vacia si no las hay) para que
     * las vistas no tengan que comprobar null en cada fila.
     *
     * @return array<int, array<string, string>>
     */
    public function getDiferenciasAttribute(): array
    {
        return $this->hcm_cambios ?? [];
    }

    /**
     * Enlace al registro afectado. Solo se ofrece para las solicitudes porque
     * son las unicas que tienen una pagina de detalle; el resto lleva el
     * identificador en texto plano.
     */
    public function getEnlaceAttribute(): ?string
    {
        if ($this->hcm_entidad !== 'Solicitud' || ! $this->hcm_sol_id) {
            return null;
        }

        return route('user.historial.show', $this->hcm_sol_id);
    }
}
