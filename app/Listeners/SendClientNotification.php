<?php

namespace App\Listeners;

use App\Events\CompteCreeEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use App\Services\EmailService;
use App\Services\SMSService;

class SendClientNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected SMSService $smsService;
    protected EmailService $emailService;

    /**
     * Create the event listener.
     */
    public function __construct(SMSService $smsService, EmailService $emailService)
    {
        $this->smsService = $smsService;
        $this->emailService = $emailService;
    }

    /**
     * Handle the event.
     */
    public function handle(CompteCreeEvent $event): void
    {
        try {
            // Envoyer l'e-mail avec les identifiants
            $this->sendCredentialsEmail($event);

            // Envoyer le SMS avec le code d'activation
            $this->sendActivationSMS($event);

            Log::info('Notifications envoyées avec succès pour le compte', [
                'compte_id' => $event->compte->id,
                'user_id' => $event->user->id,
                'numero_compte' => $event->compte->numero_compte
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des notifications', [
                'compte_id' => $event->compte->id,
                'user_id' => $event->user->id,
                'error' => $e->getMessage()
            ]);

            // Re-throw pour que le job soit marqué comme échoué
            throw $e;
        }
    }

    /**
     * Envoyer l'e-mail avec les identifiants
     */
    private function sendCredentialsEmail(CompteCreeEvent $event): void
    {
        $this->emailService->sendClientCredentials(
            $event->user,
            $event->compte,
            $event->motDePasseGenere
        );
    }

    /**
     * Envoyer le SMS avec le code d'activation
     */
    private function sendActivationSMS(CompteCreeEvent $event): void
    {
        $message = "Bienvenue {$event->user->client->nom}! Votre compte bancaire {$event->compte->numero_compte} a été créé. Code d'activation: {$event->codeActivation}";

        $this->smsService->sendSMS(
            $event->user->client->telephone,
            $message
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(CompteCreeEvent $event, \Throwable $exception): void
    {
        Log::error('Échec définitif de l\'envoi des notifications', [
            'compte_id' => $event->compte->id,
            'user_id' => $event->user->id,
            'error' => $exception->getMessage()
        ]);
    }
}
