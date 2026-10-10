<?php

namespace Database\Seeders;

use App\Models\DatosPrecargaConfig;
use Illuminate\Database\Seeder;

/**
 * Configuración inicial de los datos de precarga del estudiante.
 *
 * Es idempotente: solo crea los campos que faltan, así que re-ejecutarlo no
 * duplica filas ni pisa los ajustes que el administrador haya hecho a mano
 * (habilitar, marcar obligatorio o cambiar la vigencia).
 */
class DatosPrecargaConfigSeeder extends Seeder
{
    /**
     * Campos de la tabla usuarios que el estudiante puede precargar.
     *
     * @var list<array{dpc_campo: string, dpc_etiqueta: string, dpc_obligatorio: bool, dpc_vigencia_meses: int|null}>
     */
    private const CAMPOS = [
        [
            'dpc_campo' => 'usu_numero_telefono',
            'dpc_etiqueta' => 'Teléfono',
            'dpc_obligatorio' => false,
            'dpc_vigencia_meses' => 12,
        ],
        [
            'dpc_campo' => 'usu_direccion',
            'dpc_etiqueta' => 'Dirección',
            'dpc_obligatorio' => true,
            'dpc_vigencia_meses' => 6,
        ],
        [
            'dpc_campo' => 'usu_lugar_nacimiento',
            'dpc_etiqueta' => 'Lugar de nacimiento',
            'dpc_obligatorio' => true,
            'dpc_vigencia_meses' => null,
        ],
        [
            'dpc_campo' => 'usu_fecha_nacimiento',
            'dpc_etiqueta' => 'Fecha de nacimiento',
            'dpc_obligatorio' => true,
            'dpc_vigencia_meses' => null,
        ],
        [
            'dpc_campo' => 'usu_lapso_academico_previo',
            'dpc_etiqueta' => 'Último lapso académico aprobado',
            'dpc_obligatorio' => true,
            'dpc_vigencia_meses' => 12,
        ],
    ];

    public function run(): void
    {
        foreach (self::CAMPOS as $campo) {
            DatosPrecargaConfig::firstOrCreate(
                ['dpc_campo' => $campo['dpc_campo']],
                $campo,
            );
        }
    }
}
