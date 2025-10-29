<?php

namespace App\Listeners;

use App\Events\CompteCreeEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use App\Services\EmailService;

class SendClientNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected EmailService $emailService;

    /**
     * Create the event listener.
     */
    public function __construct(EmailService $emailService)
    {
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

            Log::info('Email envoyé avec succès pour le compte', [
                'compte_id' => $event->compte->id,
                'user_id' => $event->user->id,
                'numero_compte' => $event->compte->numero_compte
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email', [
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
