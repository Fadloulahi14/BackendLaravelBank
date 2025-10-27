<?php

namespace App\Events;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteCreeEvent
{
    use Dispatchable, SerializesModels;

    public Compte $compte;
    public User $user;
    public string $motDePasseGenere;
    public string $codeActivation;

    /**
     * Create a new event instance.
     */
    public function __construct(Compte $compte, User $user, string $motDePasseGenere, string $codeActivation)
    {
        $this->compte = $compte;
        $this->user = $user;
        $this->motDePasseGenere = $motDePasseGenere;
        $this->codeActivation = $codeActivation;
    }
}
