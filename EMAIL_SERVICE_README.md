# Service d'Envoi d'E-mails - Documentation

## Vue d'ensemble

Le service `EmailService` est un service métier dédié à l'envoi d'e-mails dans l'application bancaire. Il suit les principes SOLID et fournit une interface unifiée pour tous les besoins d'envoi d'e-mails.

## Architecture

### Structure du service

```
app/Services/
└── EmailService.php          # Service principal d'envoi d'e-mails

app/Mail/
└── ClientCredentialsMail.php # Template d'e-mail pour identifiants client

resources/views/emails/
└── client_credentials.blade.php # Template Blade pour l'e-mail
```

## Installation et Configuration

### 1. Configuration de Laravel Mail

Dans votre fichier `.env`, configurez les paramètres d'e-mail :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=votre-mot-de-passe-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=votre-email@gmail.com
MAIL_FROM_NAME="Banque Example"
```

### 2. Configuration pour Gmail (exemple)

1. Activez la vérification en deux étapes sur votre compte Gmail
2. Générez un mot de passe d'application
3. Utilisez ce mot de passe dans `MAIL_PASSWORD`

### 3. Configuration pour d'autres services

#### SendGrid
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=votre-api-key-sendgrid
```

#### Mailgun
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=votre-domaine.mailgun.org
MAILGUN_SECRET=votre-cle-secrete-mailgun
```

## Utilisation du Service

### Injection de dépendance

Le service est injecté automatiquement via le conteneur Laravel :

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\EmailService;

class CompteController extends Controller
{
    public function __construct(
        private EmailService $emailService
    ) {}

    public function store(Request $request)
    {
        // Utilisation du service
        $this->emailService->sendClientCredentials($user, $compte, $password);
    }
}
```

### Méthodes disponibles

#### 1. `sendClientCredentials(User $user, Compte $compte, string $motDePasseGenere): bool`

Envoie un e-mail avec les identifiants de connexion lors de la création d'un compte.

**Paramètres :**
- `$user` : Instance du modèle User
- `$compte` : Instance du modèle Compte
- `$motDePasseGenere` : Mot de passe généré automatiquement

**Retour :** `true` si l'envoi réussit, `false` sinon

**Exemple :**
```php
$success = $emailService->sendClientCredentials($user, $compte, 'Abc123!@#');
```

#### 2. `sendNotification(string $email, string $subject, string $message, array $data = []): bool`

Envoie un e-mail de notification générique.

**Paramètres :**
- `$email` : Adresse e-mail du destinataire
- `$subject` : Sujet de l'e-mail
- `$message` : Contenu de l'e-mail (texte brut)
- `$data` : Données supplémentaires (optionnel)

**Retour :** `true` si l'envoi réussit, `false` sinon

**Exemple :**
```php
$success = $emailService->sendNotification(
    'client@example.com',
    'Notification importante',
    'Votre compte a été crédité de 100.000 FCFA'
);
```

#### 3. `isValidEmail(string $email): bool`

Valide le format d'une adresse e-mail.

**Paramètres :**
- `$email` : Adresse e-mail à valider

**Retour :** `true` si l'adresse est valide, `false` sinon

**Exemple :**
```php
if ($emailService->isValidEmail('user@example.com')) {
    // Adresse valide
}
```

#### 4. `getDeliveryStatus(string $messageId): ?array`

Récupère le statut de livraison d'un e-mail (nécessite un service externe configuré).

**Paramètres :**
- `$messageId` : ID du message (fourni par le service d'e-mail)

**Retour :** Array avec les informations de livraison ou `null`

**Exemple :**
```php
$status = $emailService->getDeliveryStatus('message-123');
if ($status) {
    echo "Livré : " . ($status['delivered'] ? 'Oui' : 'Non');
}
```

## Templates d'E-mails

### Template ClientCredentialsMail

Le template `ClientCredentialsMail` est utilisé pour envoyer les identifiants de connexion aux nouveaux clients.

**Contenu du template :**
- Informations du client (nom)
- Identifiants de connexion (login/mot de passe)
- Détails du compte (numéro, type, solde)
- Instructions de sécurité
- Informations de contact

**Personnalisation :**
Le template se trouve dans `resources/views/emails/client_credentials.blade.php` et peut être modifié selon vos besoins.

## Gestion des Erreurs

### Logging automatique

Le service log automatiquement :
- Succès d'envoi d'e-mails
- Échecs d'envoi avec détails d'erreur
- Tentatives de livraison

### Gestion des exceptions

Toutes les méthodes du service sont wrappées dans des try-catch pour :
- Prévenir les crashes de l'application
- Logger les erreurs détaillées
- Retourner des valeurs booléennes cohérentes

## File d'attente (Queue)

### Configuration pour l'asynchrone

Pour améliorer les performances, configurez une file d'attente :

1. **Dans `.env` :**
```env
QUEUE_CONNECTION=database
```

2. **Créez la table des jobs :**
```bash
php artisan queue:table
php artisan migrate
```

3. **Démarrez le worker :**
```bash
php artisan queue:work
```

### E-mails en file d'attente

Les e-mails sont automatiquement mis en file d'attente si configuré dans le Mailable :

```php
class ClientCredentialsMail extends Mailable implements ShouldQueue
{
    use Queueable;
    // ...
}
```

## Intégration avec des Services Externes

### Extension pour SendGrid

```php
// Dans EmailService.php
public function sendWithSendGrid(string $to, string $subject, string $content): bool
{
    // Intégration SendGrid
    $email = new \SendGrid\Mail\Mail();
    $email->setFrom("noreply@banque.com", "Banque Example");
    $email->setSubject($subject);
    $email->addTo($to);
    $email->addContent("text/plain", $content);

    $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
    try {
        $response = $sendgrid->send($email);
        return $response->statusCode() == 202;
    } catch (\Exception $e) {
        Log::error('Erreur SendGrid', ['error' => $e->getMessage()]);
        return false;
    }
}
```

### Extension pour Mailgun

```php
// Dans EmailService.php
public function sendWithMailgun(string $to, string $subject, string $content): bool
{
    // Intégration Mailgun
    $mg = \Mailgun\Mailgun::create(getenv('MAILGUN_API_KEY'));
    $domain = getenv('MAILGUN_DOMAIN');

    try {
        $result = $mg->messages()->send($domain, [
            'from'    => 'noreply@banque.com',
            'to'      => $to,
            'subject' => $subject,
            'text'    => $content
        ]);
        return true;
    } catch (\Exception $e) {
        Log::error('Erreur Mailgun', ['error' => $e->getMessage()]);
        return false;
    }
}
```

## Tests

### Tests unitaires

```php
<?php

namespace Tests\Unit\Services;

use App\Services\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailServiceTest extends TestCase
{
    public function test_send_client_credentials_returns_true_on_success()
    {
        // Test d'envoi réussi
        $emailService = app(EmailService::class);
        $result = $emailService->sendClientCredentials($user, $compte, 'password123');
        $this->assertTrue($result);
    }

    public function test_is_valid_email_returns_true_for_valid_email()
    {
        $emailService = app(EmailService::class);
        $this->assertTrue($emailService->isValidEmail('test@example.com'));
    }
}
```

### Tests fonctionnels

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Compte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_sent_when_compte_created()
    {
        // Test que l'e-mail est envoyé lors de la création d'un compte
        \Mail::shouldReceive('to->send')->once();
    }
}
```

## Monitoring et Métriques

### Métriques à surveiller

- Taux de livraison des e-mails
- Temps de réponse des envois
- Taux d'erreur par destinataire
- Taux d'ouverture (si tracking activé)

### Commandes de monitoring

```bash
# Vérifier la file d'attente
php artisan queue:status

# Vider la file d'attente en cas de problème
php artisan queue:clear

# Redémarrer les workers
php artisan queue:restart
```

## Sécurité

### Bonnes pratiques

1. **Ne jamais logger les mots de passe** (automatiquement masqué)
2. **Valider les adresses e-mail** avant envoi
3. **Utiliser HTTPS** pour les webhooks
4. **Limiter le taux d'envoi** pour éviter le spam
5. **Surveiller les bounces** et désabonnements

### Protection contre les abus

- Rate limiting sur les endpoints d'envoi
- Validation stricte des adresses e-mail
- Logging de toutes les tentatives d'envoi
- Blocage des adresses en liste noire

## Dépannage

### Problèmes courants

#### 1. E-mails non reçus
- Vérifier la configuration SMTP
- Contrôler les logs Laravel
- Tester avec un service comme Mailtrap

#### 2. Erreur de connexion SMTP
- Vérifier les credentials
- Contrôler le firewall/port
- Tester la connectivité réseau

#### 3. E-mails dans spam
- Configurer SPF/DKIM/DMARC
- Utiliser un domaine dédié
- Améliorer le contenu des e-mails

### Commandes de debug

```bash
# Tester l'envoi d'e-mail
php artisan tinker
Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); });

# Vérifier la configuration
php artisan config:show mail
```

## Support et Maintenance

### Mise à jour du service

1. **Nouveaux templates :** Ajouter dans `app/Mail/`
2. **Nouveaux services :** Étendre `EmailService`
3. **Nouvelles fonctionnalités :** Ajouter des méthodes publiques

### Migration vers un nouveau service

1. Créer une nouvelle implémentation
2. Tester en parallèle
3. Migrer progressivement
4. Supprimer l'ancienne implémentation

---

**Service EmailService v1.0**
Compatible avec Laravel 10.x
Dernière mise à jour : Octobre 2025