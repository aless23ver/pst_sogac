<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChatSoporte\HiloChat;

class CerrarChatsInactivos extends Command
{

    protected $signature = 'chat:cerrar-inactivos';

    protected $description = 'Cierra automáticamente los chats que llevan más de 7 días esperando confirmación del usuario.';

    public function handle()
    {
        $fechaLimite = now()->subDays(7);

        $cantidadActualizados = HiloChat::where('hch_estado', 'pendiente_cierre')
            ->where('hch_fecha_solicitud_cierre', '<', $fechaLimite)
            ->update([
                'hch_estado' => 'cerrado'
            ]);

        $this->info("¡Tarea completada! Se cerraron {$cantidadActualizados} chats inactivos de forma automática.");
    }
}