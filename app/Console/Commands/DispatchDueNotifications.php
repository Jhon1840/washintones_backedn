<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotification;
use App\Models\Notificacion;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DispatchDueNotifications extends Command
{
    protected $signature = 'notifications:dispatch-due {--days=2} {--sync}';
    protected $description = 'Crea y envia notificaciones por vencer en los proximos dias.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $days = $days > 0 ? $days : 2;

        $now = Carbon::now()->startOfDay();
        $end = Carbon::now()->addDays($days)->endOfDay();

        $this->createFromTareas($now, $end);
        $this->createFromHistorial('historial_acciones', 'Captacion', 'clientes', $now, $end);
        $this->createFromHistorial('colocacion_historial_acciones', 'Colocacion', 'colocacion_clientes', $now, $end);
        $this->createFromHistorial('visitas_historial_acciones', 'Visitas', 'visitas_clientes', $now, $end);
        $this->createFromHistorial('pasar_informacion_historial_acciones', 'PasarInformacion', 'pasar_informacion_clientes', $now, $end);
        $this->createFromHistorial('inmuebles_captados_historial_acciones', 'InmueblesCaptados', 'inmuebles_captados_clientes', $now, $end);

        $this->dispatchPending($end, (bool) $this->option('sync'));

        $this->info('Notificaciones procesadas.');
        return self::SUCCESS;
    }

    private function createFromTareas(Carbon $start, Carbon $end): void
    {
        if (! DB::getSchemaBuilder()->hasTable('tareas')) {
            return;
        }

        $rows = DB::table('tareas as t')
            ->join('historial_acciones as ha', 'ha.id', '=', 't.historial_id')
            ->leftJoin('clientes as c', 'c.id', '=', 'ha.cliente_id')
            ->select(['t.id', 't.descripcion', 't.fecha', 'ha.usuario_id', 'c.nombre as cliente_nombre'])
            ->where('t.completado', false)
            ->whereNull('ha.deleted_at')
            ->whereBetween('t.fecha', [$start->toDateString(), $end->toDateString()])
            ->get();

        foreach ($rows as $row) {
            $cliente = trim((string) ($row->cliente_nombre ?? ''));
            $cuerpo = (string) $row->descripcion;
            if ($cliente !== '') {
                $cuerpo .= ' · Cliente: ' . $cliente;
            }

            $this->firstOrCreateNotificacion(
                (int) $row->usuario_id,
                'Tarea por vencer',
                $cuerpo,
                'tarea',
                'tareas',
                (int) $row->id,
                Carbon::parse($row->fecha)->endOfDay(),
                $cliente !== '' ? ['cliente' => $cliente] : []
            );
        }
    }

    private function createFromHistorial(
        string $table,
        string $tipo,
        string $clienteTable,
        Carbon $start,
        Carbon $end
    ): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        $rows = DB::table($table . ' as ha')
            ->join('catalogo_acciones as ca', 'ca.id', '=', 'ha.accion_id')
            ->leftJoin($clienteTable . ' as c', 'c.id', '=', 'ha.cliente_id')
            ->select([
                'ha.id',
                'ha.usuario_id',
                'ha.fecha_proxima_accion',
                'ha.notas',
                'ca.nombre as accion',
                'c.nombre as cliente_nombre',
            ])
            ->whereNull('ha.deleted_at')
            ->whereBetween('ha.fecha_proxima_accion', [$start->toDateString(), $end->toDateString()])
            ->get();

        foreach ($rows as $row) {
            $fecha = Carbon::parse($row->fecha_proxima_accion)->endOfDay();
            $titulo = 'Próxima acción por vencer';
            $cuerpo = $row->accion ? (string) $row->accion : (string) $row->notas;
            $cliente = trim((string) ($row->cliente_nombre ?? ''));
            if ($cliente !== '') {
                $cuerpo .= ' · Cliente: ' . $cliente;
            }

            $this->firstOrCreateNotificacion(
                (int) $row->usuario_id,
                $titulo,
                $cuerpo,
                strtolower($tipo),
                $table,
                (int) $row->id,
                $fecha,
                $cliente !== ''
                    ? ['accion' => $row->accion, 'cliente' => $cliente]
                    : ['accion' => $row->accion]
            );
        }
    }

    private function firstOrCreateNotificacion(
        int $usuarioId,
        string $titulo,
        string $cuerpo,
        string $tipo,
        string $fuente,
        int $fuenteId,
        Carbon $fechaProgramada,
        array $data
    ): void {
        $exists = Notificacion::where('usuario_id', $usuarioId)
            ->where('fuente', $fuente)
            ->where('fuente_id', $fuenteId)
            ->whereNull('enviada_at')
            ->exists();

        if ($exists) {
            return;
        }

        Notificacion::create([
            'usuario_id' => $usuarioId,
            'titulo' => $titulo,
            'cuerpo' => $cuerpo,
            'tipo' => $tipo,
            'fuente' => $fuente,
            'fuente_id' => $fuenteId,
            'fecha_programada' => $fechaProgramada,
            'data' => $data,
        ]);
    }

    private function dispatchPending(Carbon $end, bool $sync): void
    {
        $serviceAccount = config('services.fcm.service_account_path');
        if (! $serviceAccount) {
            $this->warn('FCM_SERVICE_ACCOUNT_JSON no configurada; no se podran enviar pushes.');
        }

        $pending = Notificacion::whereNull('enviada_at')
            ->whereNotNull('fecha_programada')
            ->where('fecha_programada', '<=', $end)
            ->orderBy('id')
            ->limit(200)
            ->get();

        foreach ($pending as $notificacion) {
            $tokensCount = \App\Models\DeviceToken::where('usuario_id', $notificacion->usuario_id)->count();
            $this->info(
                sprintf(
                    'Enviando notificacion #%d usuario:%d fecha:%s tokens:%d',
                    $notificacion->id,
                    $notificacion->usuario_id,
                    $notificacion->fecha_programada,
                    $tokensCount
                )
            );
            if ($sync) {
                (new SendPushNotification($notificacion->id))->handle();
            } else {
                SendPushNotification::dispatch($notificacion->id);
            }
        }
    }
}
