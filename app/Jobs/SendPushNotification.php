<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Models\Notificacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $notificacionId)
    {
    }

    public function handle(): void
    {
        $notificacion = Notificacion::find($this->notificacionId);

        if (! $notificacion || $notificacion->enviada_at) {
            return;
        }

        $tokens = DeviceToken::where('usuario_id', $notificacion->usuario_id)
            ->pluck('token')
            ->filter()
            ->values()
            ->all();

        if (! $tokens) {
            return;
        }

        $dataPayload = array_merge(
            $notificacion->data ?? [],
            [
                'notificacion_id' => (string) $notificacion->id,
                'tipo' => (string) ($notificacion->tipo ?? ''),
                'fuente' => (string) ($notificacion->fuente ?? ''),
                'fuente_id' => (string) ($notificacion->fuente_id ?? ''),
            ]
        );

        $serviceAccountPath = config('services.fcm.service_account_path')
            ?: config('services.firebase.credentials');
        if (! $serviceAccountPath) {
            Log::warning('FCM service account missing; skipping push.', [
                'notificacion_id' => $this->notificacionId,
            ]);
            return;
        }

        if (! is_file($serviceAccountPath)) {
            Log::warning('FCM service account file missing', [
                'notificacion_id' => $this->notificacionId,
                'path' => $serviceAccountPath,
            ]);
            return;
        }

        try {
            $messaging = (new Factory())
                ->withServiceAccount($serviceAccountPath)
                ->createMessaging();

            $data = [];
            foreach ($dataPayload as $key => $value) {
                $data[$key] = is_string($value) ? $value : json_encode($value);
            }

            $notification = FirebaseNotification::create(
                (string) $notificacion->titulo,
                (string) $notificacion->cuerpo
            );

            $sent = 0;
            foreach ($tokens as $token) {
                $message = CloudMessage::fromArray([
                    'token' => $token,
                    'notification' => $notification,
                    'data' => $data,
                ]);

                $messaging->send($message);
                $sent++;
            }

            if ($sent > 0) {
                $notificacion->enviada_at = now();
                $notificacion->save();
            }
        } catch (\Throwable $e) {
            Log::warning('FCM Kreait push failed', [
                'notificacion_id' => $this->notificacionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
