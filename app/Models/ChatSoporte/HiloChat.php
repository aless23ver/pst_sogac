<?php

namespace App\Models\ChatSoporte;

use App\Models\Concerns\RegistraCambios;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;

class HiloChat extends Model
{
    use RegistraCambios;

    protected $table = 'hilos_chat';

    protected $primaryKey = 'hch_id';

    protected $fillable = [
        'hch_id_usuario', 'hch_id_admin', 'hch_estado', 'hch_nivel_atencion', 'hch_etiqueta_tema', 'hch_fecha_solicitud_cierre',
    ];

    /**
     * La columna es timestamp pero no estaba casteada, así que llegaba como
     * string y cualquier format() o diffInDays() sobre ella fallaba.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hch_fecha_solicitud_cierre' => 'datetime',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'hch_id_usuario', 'usu_id');
    }

    public function admin()
    {
        return $this->belongsTo(Usuario::class, 'hch_id_admin', 'usu_id');
    }

    public function mensajes()
    {
        return $this->hasMany(MensajeChat::class, 'mch_id_hilo', 'hch_id');
    }

    /**
     * Los estados posibles de un hilo, con su texto para pantalla.
     *
     * Antes cada vista traducía el estado con ucfirst(str_replace('_', ' ', ...)),
     * lo que producía "Pendiente cierre", sin tilde y sin explicar que lo que
     * falta es la confirmación del estudiante.
     *
     * @return array<string, string>
     */
    public static function estadosDisponibles(): array
    {
        return [
            'pendiente' => 'Esperando atención',
            'activo' => 'En atención',
            'pendiente_cierre' => 'Pendiente de confirmación',
            'cerrado' => 'Cerrado',
            'pending_escalation' => 'Escalando (pendiente aprobación)',
        ];
    }

    /**
     * Niveles de atención del chat, alineados con la jerarquía de roles.
     *
     * Un chat nace en el nivel 1 (taquillero). Cuando sube, vuelve a la
     * bandeja y solo puede reclamarlo un rol DEL nivel indicado o superior:
     * un chat de nivel 2 ya no lo ve el taquillero.
     *
     * @var array<int, string>
     */
    public const NIVELES_ATENCION = [
        1 => 'Taquillero',
        2 => 'Analista',
        3 => 'Administrador',
    ];

    /** Nivel máximo al que puede escalar un chat. */
    public const NIVEL_MAXIMO = 3;

    /** Todos los estados posibles del chat. */
    public const ESTADOS = [
        'pendiente' => 'pendiente',
        'activo' => 'activo',
        'pendiente_cierre' => 'pendiente_cierre',
        'cerrado' => 'cerrado',
        'pending_escalation' => 'pending_escalation',
    ];

    /**
     * Nivel mínimo de rol necesario para atender un chat que está en el
     * nivel indicado.
     */
    public static function nivelRequeridoPorRol(string $rol): int
    {
        return match ($rol) {
            Rol::TAQUILLERO => 1,
            Rol::ANALISTA => 2,
            Rol::ADMINISTRADOR => 3,
            default => 1,
        };
    }

    /**
     * Texto del nivel, listo para mostrar ("Analista", "Administrador"...).
     */
    public function getNivelEtiquetaAttribute(): string
    {
        return self::NIVELES_ATENCION[$this->hch_nivel_atencion] ?? 'Nivel '.$this->hch_nivel_atencion;
    }

    /**
     * Si el chat todavía puede subir de nivel (estado normal).
     */
    public function puedeEscalar(): bool
    {
        return $this->hch_nivel_atencion < self::NIVEL_MAXIMO
            && $this->hch_estado !== 'pending_escalation';
    }

    /**
     * Si el chat está en estado de escalado pendiente de aprobación.
     */
    public function estaEnEscaladoPendiente(): bool
    {
        return $this->hch_estado === 'pending_escalation';
    }

    /**
     * Si el usuario actual puede solicitar el escalado de este chat.
     * - Estudiantes solo pueden solicitar si su chat está activo/pendiente
     * - Personal solo si no está ya escalado o cerrado
     */
    public function puedeSolicitarEscalarPor(string $rolUsuario): bool
    {
        if ($rolUsuario === 'estudiante') {
            return in_array($this->hch_estado, ['pendiente', 'activo'])
                && !$this->estaEnEscaladoPendiente();
        }
        // Analista o administrador pueden solicitar escalado si no está cerrado
        return $this->hch_estado !== 'cerrado'
            && $this->hch_estado !== 'pending_escalation';
    }

    /**
     * Clase de chip asociada a cada estado.
     *
     * @return array<string, string>
     */
    public static function chipsPorEstado(): array
    {
        return [
            'pendiente' => 'chip--warn',
            'activo' => 'chip--ok',
            'pendiente_cierre' => 'chip--neutro',
            'cerrado' => 'chip--neutro',
        ];
    }

    /**
     * Texto del estado, listo para mostrar.
     */
    public function getEstadoEtiquetaAttribute(): string
    {
        return self::estadosDisponibles()[$this->hch_estado]
            ?? ucfirst(str_replace('_', ' ', (string) $this->hch_estado));
    }

    /**
     * Clase CSS del chip para este estado.
     */
    public function getEstadoChipAttribute(): string
    {
        return self::chipsPorEstado()[$this->hch_estado] ?? 'chip--neutro';
    }
}
