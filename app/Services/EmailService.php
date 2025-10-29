<?php

namespace App\Services;

use App\Mail\ClientCredentialsMail;
use App\Models\Compte;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Envoyer un e-mail avec les identifiants du client
     *
     * @param User $user
     * @param Compte $compte
     * @param string $motDePasseGenere
     * @return bool
     */
    public function sendClientCredentials(User $user, Compte $compte, string $motDePasseGenere): bool
    {
        try {
            Log::info('Tentative d\'envoi d\'email - Début', [
                'user_id' => $user->id,
                'compte_id' => $compte->id,
                'email' => $user->client->email,
                'numero_compte' => $compte->numero_compte
            ]);

            Mail::to($user->client->email)->send(
                new ClientCredentialsMail($user, $compte, $motDePasseGenere)
            );

            Log::info('E-mail d\'identifiants envoyé avec succès', [
                'user_id' => $user->id,
                'compte_id' => $compte->id,
                'email' => $user->client->email,
                'numero_compte' => $compte->numero_compte
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'e-mail d\'identifiants', [
                'user_id' => $user->id,
                'compte_id' => $compte->id,
                'email' => $user->client->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Envoyer un e-mail de notification générique
     *
     * @param string $email
     * @param string $subject
     * @param string $message
     * @param array $data
     * @return bool
     */
    public function sendNotification(string $email, string $subject, string $message, array $data = []): bool
    {
        try {
            Log::info('Tentative d\'envoi d\'email de notification', [
                'to' => $email,
                'subject' => $subject,
                'timestamp' => now()
            ]);

            // Ici vous pouvez créer un mail générique ou utiliser une classe Mailable existante
            Mail::raw($message, function ($mail) use ($email, $subject) {
                $mail->to($email)->subject($subject);
            });

            Log::info('E-mail de notification envoyé', [
                'to' => $email,
                'subject' => $subject,
                'timestamp' => now()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'e-mail de notification', [
                'to' => $email,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Vérifier si une adresse e-mail est valide
     *
     * @param string $email
     * @return bool
     */
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Tester l'envoi d'email
     *
     * @param string $email
     * @return bool
     */
    public function testEmail(string $email = 'fadloulahi14@gmail.com'): bool
    {
        try {
            Log::info('Test d\'envoi d\'email - Début', [
                'to' => $email,
                'timestamp' => now()
            ]);

            Mail::raw('Ceci est un email de test pour vérifier le service de messagerie.', function ($mail) use ($email) {
                $mail->to($email)->subject('Test Email Service - Laravel Bank');
            });

            Log::info('Test d\'envoi d\'email - Succès', [
                'to' => $email,
                'timestamp' => now()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Test d\'envoi d\'email - Échec', [
                'to' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()
            ]);

            return false;
        }
    }

    /**
     * Obtenir le statut de livraison des e-mails (si configuré avec un service externe)
     *
     * @param string $messageId
     * @return array|null
     */
    public function getDeliveryStatus(string $messageId): ?array
    {
        // Ici vous intégreriez avec votre service d'e-mail (SendGrid, Mailgun, etc.)
        // pour vérifier le statut de livraison

        Log::info('Vérification du statut de livraison', [
            'message_id' => $messageId
        ]);

        return [
            'delivered' => true,
            'opened' => false,
            'clicked' => false,
            'timestamp' => now()
        ];
    }
}