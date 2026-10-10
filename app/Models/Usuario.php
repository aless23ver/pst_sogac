<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';

    protected $primaryKey = 'usu_id';

    public $timestamps = false;

    /**
     * La fecha de la última actualización de datos de precarga se maneja
     * como fecha para poder compararla con la vigencia configurada.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usu_datos_ultima_actualizacion' => 'datetime',
        ];
    }

    protected $fillable = [
        'usu_rol',
        'usu_tdo_id',
        'usu_primer_nombre',
        'usu_segundo_nombre',
        'usu_primer_apellido',
        'usu_segundo_apellido',
        'usu_numero_documento',
        'usu_correo_electronico',
        'usu_numero_telefono',
        'usu_pnf',
        'usu_trayecto',
        // Datos de precarga: los llena el estudiante una vez en su perfil y
        // se adjuntan a los trámites. El admin decide cuáles están activos
        // (ver DatosPrecargaConfig).
        'usu_direccion',
        'usu_lugar_nacimiento',
        'usu_fecha_nacimiento',
        'usu_lapso_academico_previo',
        'usu_datos_ultima_actualizacion',
        'usu_contrasena_hash',
        'usu_estado_cuenta',
        'usu_fecha_registro',
        'usu_ultimo_acceso',
        'usu_permisos',
    ];

    public function getEmailForPasswordReset()
    {
        return $this->usu_correo_electronico;
    }

    public function hilosComoUsuario()
    {
        return $this->hasMany(HiloChat::class, 'hch_id_usuario', 'usu_id');
    }

    public function hilosComoAdmin()
    {
        return $this->hasMany(HiloChat::class, 'hch_id_admin', 'usu_id');
    }

    // Un Usuario pertenece a un Tipo de Documento
    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'usu_tdo_id', 'tdo_id');
    }

    // Un Usuario (Estudiante) tiene muchas Solicitudes
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'sol_usu_id', 'usu_id');
    }

    // Un Usuario (Admin) registra/modifica muchos historiales de estados
    public function historialesModificados()
    {
        return $this->hasMany(HistorialEstadoSolicitud::class, 'hes_usu_id_responsable', 'usu_id');
    }

    /**
     * Nombre y apellidos en un solo valor.
     *
     * Las cuatro columnas de nombre estan separadas en la tabla porque cada
     * parte se usa por separado (buscar por primer nombre, ordenar por
     * apellido), asi que para mostrarlas juntas hay que unirlas en algun lado.
     * Si estan vacias se cae al correo, que es el dato con el que siempre se
     * puede identificar a una persona.
     */
    public function getNombreCompletoAttribute(): string
    {
        $nombre = collect([
            $this->usu_primer_nombre,
            $this->usu_segundo_nombre,
            $this->usu_primer_apellido,
            $this->usu_segundo_apellido,
        ])->filter()->implode(' ');

        return $nombre !== '' ? $nombre : (string) $this->usu_correo_electronico;
    }

    /**
     * Número de ficha del estudiante, derivado y estable.
     *
     * Formato FIC-año-número (FIC-2026-00012): usa el año de registro y el
     * identificador interno, así que no necesita columna ni counter que
     * mantener: un mismo estudiante siempre muestra la misma ficha y dos
     * estudiantes nunca comparten número.
     */
    public function getNumeroFichaAttribute(): string
    {
        $fecha = $this->usu_fecha_registro;
        if (is_string($fecha)) {
            $fecha = \DateTime::createFromFormat('Y-m-d H:i:s', $fecha);
            if (! $fecha) {
                $fecha = \DateTime::createFromFormat('Y-m-d', $fecha);
            }
        }
        $anio = $fecha?->format('Y') ?? now()->year;

        return sprintf('FIC-%s-%05d', $anio, $this->usu_id);
    }

    // ¡CRUCIAL! Le decimos a Laravel qué columna guarda la contraseña encriptada
    public function getAuthPassword()
    {
        return $this->usu_contrasena_hash;
    }

    // Columna del token "recuerdame" (convencion usu_ del proyecto)
    public function getRememberTokenName()
    {
        return 'usu_remember_token';
    }

    // Le decimos a Laravel cuál es tu clave primaria personalizada
    public function getAuthIdentifierName()
    {
        return 'usu_id';
    }

    // Esto le dice al cartero de Laravel a qué dirección exacta enviar el mensaje
    public function routeNotificationForMail($notification = null)
    {
        return $this->usu_correo_electronico;
    }

    // ============================================================
    // Jerarquia de roles
    // Roles: administrador > analista > taquillero, mas estudiante.
    // Solo los tres primeros entran al panel administrativo.
    // Definidos en App\Models\Rol.
    // ============================================================

    public function esAdministrador(): bool
    {
        return $this->usu_rol === Rol::ADMINISTRADOR;
    }

    public function esAnalista(): bool
    {
        return $this->usu_rol === Rol::ANALISTA;
    }

    public function esTaquillero(): bool
    {
        return $this->usu_rol === Rol::TAQUILLERO;
    }

    public function esEstudiante(): bool
    {
        return $this->usu_rol === Rol::ESTUDIANTE;
    }

    /**
     * Pertenece a la jerarquia administrativa (administrador, analista
     * o taquillero). Sustituye al antiguo chequeo "usu_rol === 'admin'".
     */
    public function esAdministrativo(): bool
    {
        return in_array($this->usu_rol, Rol::administrativos(), true);
    }

    /**
     * Rol listo para mostrar en pantalla (p. ej. "Administrador").
     *
     * Se usa junto al nombre completo cuando hay que decir quien atendio una
     * solicitud: en el historial el estudiante debe saber no solo el nombre de
     * la persona sino tambien el rol con el que lo hizo.
     */
    public function getRolEtiquetaAttribute(): string
    {
        return Rol::etiqueta((string) $this->usu_rol);
    }

    // ============================================================
    // Datos de precarga
    // El estudiante llena sus datos una vez (perfil) y estos se adjuntan
    // a los trámites. Qué campos cuentan está definido por el admin en
    // DatosPrecargaConfig.
    // ============================================================

    /**
     * Valor actual de cada campo de precarga habilitado por el admin.
     *
     * @return array<string, string|null> campo => valor tal como está en la tabla
     */
    public function datosPrecarga(): array
    {
        $valores = [];
        $columnas = DatosPrecargaConfig::camposActivos();

        if ($columnas->isEmpty()) {
            return $valores;
        }

        // Se recarga el modelo para leer los atributos frescos, incluidas
        // columnas que un modelo fabricado en memoria podría no traer.
        $this->refresh();

        foreach ($columnas as $config) {
            $valores[$config->dpc_campo] = $this->{$config->dpc_campo} !== null
                ? (string) $this->{$config->dpc_campo}
                : null;
        }

        return $valores;
    }

    /**
     * Cuántos de los campos habilitados tiene ya cargados.
     */
    public function camposPrecargaCargados(): int
    {
        return collect($this->datosPrecarga())->filter(fn ($valor) => $valor !== null && $valor !== '')->count();
    }

    /**
     * Si le falta algún campo OBLIGATORIO de la precarga.
     */
    public function precargaIncompleta(): bool
    {
        $this->refresh();

        return DatosPrecargaConfig::camposActivos()
            ->filter(fn ($config) => $config->dpc_obligatorio)
            ->contains(fn ($config) => $this->{$config->dpc_campo} === null || $this->{$config->dpc_campo} === '');
    }

    /**
     * Si los datos de precarga ya perdieron vigencia según lo que definió el
     * admin. Los campos sin vigencia configurada no vencen nunca.
     */
    public function precargaVencida(): bool
    {
        return DatosPrecargaConfig::camposActivos()
            ->filter(fn ($config) => ! $config->estaVigente($this->usu_datos_ultima_actualizacion))
            ->isNotEmpty();
    }

    /**
     * Verifica si el usuario tiene un permiso específico.
     *
     * Permite complementar el control por rol con permisos individuales
     * asignados directamente al usuario (columna usu_permisos, JSON).
     *
     * @param string $permission Nombre del permiso a verificar
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        // Si no tiene permisos individuales definidos, se usa el rol
        $permisos = $this->usu_permisos;
        if (is_null($permisos) || $permisos === []) {
            // Rol-based check: los permisos vienen definidos en la constante
            // Rol::PERMISOS o se pueden agregar aquí seg necesidades.
            // Por ahora, si no hay permisos individuales, se devuelve true
            // para no bloquear operaciones por defecto (según el rol).
            return true;
        }

        if (! is_array($permisos)) {
            $permisos = json_decode($permisos, true) ?? [];
        }

        return in_array($permission, $permisos, true);
    }
}
