<?php

namespace App\Mail;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientCredentialsMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public Compte $compte;
    public string $motDePasseGenere;

    
    public function __construct(User $user, Compte $compte, string $motDePasseGenere)
    {
        $this->user = $user;
        $this->compte = $compte;
        $this->motDePasseGenere = $motDePasseGenere;
    }

    
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue - Vos identifiants de connexion',
        );
    }

   
    public function content(): Content
    {
        return new Content(
            view: 'emails.client_credentials',
            with: [
                'user' => $this->user,
                'compte' => $this->compte,
                'motDePasseGenere' => $this->motDePasseGenere,
            ],
        );
    }

   
    public function attachments(): array
    {
        return [];
    }
}
