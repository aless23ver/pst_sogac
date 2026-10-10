<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Configuración de un dato que el estudiante precarga en su perfil.
 *
 * El administrador define aquí la "versión" de los datos que los estudiantes
 * pueden precargar: qué campos están habilitados, cuáles son obligatorios y
 * cada cuántos meses pierden vigencia. El formulario del estudiante se arma
 * a partir de esta tabla, así que cambiar un campo acá cambia lo que ven
 * todos sin tocar código.
 *
 * @property int $dpc_id
 * @property string $dpc_campo
 * @property string $dpc_etiqueta
 * @property bool $dpc_obligatorio
 * @property bool $dpc_activo
 * @property int|null $dpc_vigencia_meses
 */
class DatosPrecargaConfig extends Model
{
    use RegistraCambios;

    protected $table = 'datos_precarga_config';

    protected $primaryKey = 'dpc_id';

    /**
     * Columnas de la tabla usuarios que se pueden ofrecer como datos de
     * precarga, con su etiqueta sugerida.
     *
     * Solo se ofertan columnas que YA EXISTEN en la base: un campo de
     * precarga guarda su valor en la propia fila del estudiante, así que
     * agregar un dato que no tenga columna tendría que nacer de una
     * migración y no de una pantalla.
     *
     * @var array<string, string>
     */
    public const COLUMNAS_OFERTADAS = [
        'usu_pnf' => 'PNF / carrera',
        'usu_trayecto' => 'Trayecto académico',
    ];

    protected $fillable = [
        'dpc_campo',
        'dpc_etiqueta',
        'dpc_obligatorio',
        'dpc_activo',
        'dpc_vigencia_meses',
    ];

    /**
     * @return array<string, bool|int|null>
     */
    protected function casts(): array
    {
        return [
            'dpc_obligatorio' => 'boolean',
            'dpc_activo' => 'boolean',
            'dpc_vigencia_meses' => 'integer',
        ];
    }

    /**
     * Campos habilitados para la precarga, en el orden en que se definieron.
     *
     * @return Collection<int, self>
     */
    public static function camposActivos(): Collection
    {
        return static::query()
            ->where('dpc_activo', true)
            ->orderBy('dpc_id')
            ->get();
    }

    /**
     * Todos los campos configurados, activos o no, para el panel del admin.
     *
     * @return Collection<int, self>
     */
    public static function todos(): Collection
    {
        return static::query()->orderBy('dpc_id')->get();
    }

    /**
     * Columnas ofertables que todavía no están configuradas: de ahí elige
     * el administrador cuando agrega un campo de precarga nuevo.
     *
     * @return array<string, string> columna => etiqueta sugerida
     */
    public static function columnasDisponibles(): array
    {
        $configuradas = static::query()->pluck('dpc_campo')->all();

        return array_diff_key(self::COLUMNAS_OFERTADAS, array_flip($configuradas));
    }

    /**
     * Si los datos cargados en este campo siguen vigentes.
     *
     * La vigencia se cuenta desde la última vez que el estudiante actualizó
     * sus datos, no desde cada campo: los datos de precarga se llenan juntos
     * en una sola pantalla, así que comparten fecha de actualización.
     */
    public function estaVigente(?Carbon $ultimaActualizacion): bool
    {
        if ($this->dpc_vigencia_meses === null) {
            return true;
        }

        if ($ultimaActualizacion === null) {
            return false;
        }

        return $ultimaActualizacion->copy()->addMonths($this->dpc_vigencia_meses)->isFuture();
    }

    /**
     * Alcance para filtrar por estado de habilitación en el listado admin.
     *
     * @param  bool|null  $estado  null = todos, true = activos, false = inactivos
     */
    public function scopeEstado(Builder $consulta, ?bool $estado): Builder
    {
        return $estado === null ? $consulta : $consulta->where('dpc_activo', $estado);
    }
}
