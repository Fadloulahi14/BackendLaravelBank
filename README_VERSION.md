# API RESTful Gestion des Clients & Comptes (Version 1.0.0)

## Vue d'ensemble

Cette API RESTful Laravel 10 implémente un système complet de gestion bancaire avec authentification, gestion des clients et comptes bancaires. L'architecture suit les principes SOLID et utilise une approche Domain Driven Design (DDD) pour assurer la maintenabilité et l'évolutivité.

## Fonctionnalités principales

### ✅ Gestion des Utilisateurs
- Modèle `User` unifié avec rôles (admin/client)
- Authentification JWT avec Passport
- Profils clients et administrateurs séparés
- Champs `login` et `password` nullable pour les clients

### ✅ Gestion des Comptes Bancaires
- Création automatique de numéros de compte uniques
- Types de comptes : épargne, chèque
- Calcul dynamique du solde via transactions
- Statuts : actif, bloqué, fermé
- Métadonnées JSON pour extensions futures

### ✅ Gestion des Transactions
- Suivi complet des opérations bancaires
- Types : dépôt, retrait, virement, frais
- Statuts : en attente, validée, annulée
- Calcul automatique de l'impact sur le solde

### ✅ Validation Métier
- Règles personnalisées pour numéros CNI sénégalais
- Validation des numéros de téléphone sénégalais
- Validation conditionnelle selon le contexte

### ✅ Notifications Automatiques
- E-mails avec identifiants générés
- SMS avec codes d'activation
- File d'attente pour traitement asynchrone

### ✅ Logging et Audit
- Middleware de logging des opérations sensibles
- Masquage automatique des données confidentielles
- Métriques de performance

### ✅ Documentation API
- Documentation Swagger/OpenAPI complète
- Exemples de requêtes et réponses
- Gestion des erreurs documentée

## Architecture

### Principes SOLID appliqués

- **S** (Single Responsibility) : Chaque classe a une responsabilité unique
- **O** (Open/Closed) : Extensible via événements, services et règles personnalisées
- **L** (Liskov Substitution) : Héritage approprié des classes Laravel
- **I** (Interface Segregation) : Interfaces claires pour les services
- **D** (Dependency Inversion) : Injection de dépendances pour les services métier

### Structure DDD

```
app/
├── Events/           # Événements métier
├── Listeners/        # Gestionnaires d'événements
├── Models/          # Modèles Eloquent
├── Rules/           # Règles de validation personnalisées
├── Services/        # Services métier
├── Http/
│   ├── Controllers/ # Contrôleurs API
│   ├── Middleware/  # Middlewares personnalisés
│   └── Requests/    # Classes de validation
└── Mail/            # Templates d'e-mails
```

## Installation et Configuration

### Prérequis
- PHP 8.1+
- Composer
- PostgreSQL/MySQL
- Redis (optionnel, pour file d'attente)

### Installation

1. **Cloner le projet**
```bash
git clone <repository-url>
cd bankProjet
```

2. **Installer les dépendances**
```bash
composer install
```

3. **Configuration de l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configuration de la base de données**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=bank_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Exécuter les migrations**
```bash
php artisan migrate
```

6. **Créer les données de test**
```bash
php artisan db:seed
```

7. **Installer Passport pour l'authentification**
```bash
php artisan passport:install
```

8. **Démarrer le serveur**
```bash
php artisan serve
```

## Utilisation de l'API

### Authentification

#### Créer un client administrateur
```bash
# Via tinker ou seeder personnalisé
php artisan tinker
```
```php
\App\Models\User::create([
    'id' => \Illuminate\Support\Str::uuid(),
    'login' => 'admin',
    'password' => bcrypt('password'),
    'role' => 'admin'
]);
```

#### Obtenir un token JWT
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"login": "admin", "password": "password"}'
```

### Création d'un compte bancaire

#### Endpoint
```
POST /api/v1/comptes
Authorization: Bearer {token}
Content-Type: application/json
```

#### Créer un compte pour un nouveau client
```bash
curl -X POST "http://localhost:8000/api/v1/comptes" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "type": "cheque",
    "soldeInitial": 500000,
    "devise": "FCFA",
    "client": {
      "titulaire": "Amadou Diallo",
      "nci": "1987654321098",
      "email": "amadou.diallo@example.com",
      "telephone": "771234567",
      "adresse": "Dakar, Sénégal"
    }
  }'
```

#### Créer un compte pour un client existant
```bash
curl -X POST "http://localhost:8000/api/v1/comptes" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "type": "epargne",
    "soldeInitial": 1000000,
    "devise": "FCFA",
    "client": {
      "id": "uuid-du-client-existant"
    }
  }'
```

#### Réponse de succès
```json
{
  "success": true,
  "message": "Compte créé avec succès",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "numeroCompte": "C00123456",
    "titulaire": "Amadou Diallo",
    "type": "cheque",
    "solde": 500000,
    "devise": "FCFA",
    "dateCreation": "2025-10-25T18:00:00Z",
    "statut": "actif",
    "metadonnees": {
      "derniereModification": "2025-10-25T18:00:00Z",
      "version": 1
    }
  }
}
```

### Lister les comptes

#### Endpoint
```
GET /api/v1/comptes?page=1&limit=10&type=cheque&statut=actif
Authorization: Bearer {token}
```

#### Paramètres de requête
- `page` : Numéro de page (défaut: 1)
- `limit` : Nombre d'éléments par page (max: 100, défaut: 10)
- `type` : Filtrer par type (epargne|cheque)
- `statut` : Filtrer par statut (actif|bloque|ferme)
- `search` : Recherche par numéro de compte ou nom
- `sort` : Champ de tri (dateCreation|solde|titulaire)
- `order` : Ordre de tri (asc|desc)

### Détails d'un compte

#### Endpoint
```
GET /api/v1/comptes/{id}
Authorization: Bearer {token}
```

### Mise à jour d'un compte

#### Endpoint
```
PATCH /api/v1/comptes/{id}
Authorization: Bearer {token}
Content-Type: application/json
```

#### Corps de la requête
```json
{
  "solde": 750000,
  "statut": "actif",
  "metadonnees": {
    "note": "Mise à jour suite à dépôt"
  }
}
```

### Suppression d'un compte

#### Endpoint
```
DELETE /api/v1/comptes/{id}
Authorization: Bearer {token}
```

## Règles de validation

### Numéro CNI Sénégalais
- Doit contenir exactement 13 chiffres
- Commence par 1
- Validation de la clé de contrôle (algorithme Luhn simplifié)

### Numéro de téléphone sénégalais
- Format international : +2217XXXXXXXX (9 chiffres après +221)
- Format local : 7XXXXXXXX (9 chiffres)
- Pas de suites répétées

### Champs obligatoires
- Pour nouveau client : titulaire, nci, email, telephone, adresse
- Pour client existant : id du client
- Pour compte : type, soldeInitial, devise

## Gestion des erreurs

### Codes d'erreur courants

#### 400 - Bad Request
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Les données fournies sont invalides",
    "details": {
      "devise": ["La devise doit contenir exactement 3 caractères"],
      "client.nci": ["Le numéro de CNI n'est pas valide"]
    }
  }
}
```

#### 401 - Unauthorized
```json
{
  "success": false,
  "message": "Authentification requise"
}
```

#### 404 - Not Found
```json
{
  "success": false,
  "message": "Client non trouvé"
}
```

#### 422 - Unprocessable Entity
```json
{
  "success": false,
  "message": "Erreur de validation",
  "errors": {
    "client.email": ["Cette adresse e-mail est déjà utilisée"]
  }
}
```

## Notifications

### E-mail automatique
Envoyé lors de la création d'un compte avec :
- Identifiants de connexion (login/mot de passe)
- Numéro de compte
- Instructions de sécurité

### SMS automatique
Envoyé avec le code d'activation pour :
- Activation du compte
- Validation de l'identité

## Logging et audit

### Logs d'opérations
- Création de comptes : `storage/logs/compte_operations.log`
- Erreurs système : `storage/logs/laravel.log`
- Requêtes lentes : monitoring automatique

### Données trackées
- Timestamp, IP, User-Agent
- Durée d'exécution
- Statut de succès/échec
- Données sensibles masquées

## Tests

### Exécuter les tests
```bash
php artisan test
```

### Tests disponibles
- Tests unitaires des règles de validation
- Tests fonctionnels des endpoints API
- Tests d'intégration des événements
- Tests de performance des requêtes

## Sécurité

### Mesures implémentées
- Hashage automatique des mots de passe
- Validation stricte des entrées
- Protection contre les injections SQL
- Logs d'audit des opérations sensibles
- Masquage des données confidentielles

### Bonnes pratiques
- Utilisation de transactions pour l'intégrité des données
- Validation côté serveur uniquement (API-first)
- Gestion des erreurs sans fuite d'informations
- Rate limiting (configurable)

## Performance

### Optimisations
- Index composites sur les champs fréquemment recherchés
- Chargement paresseux des relations
- Cache des requêtes fréquentes
- File d'attente pour les opérations lourdes (e-mails/SMS)

### Métriques monitorées
- Temps de réponse des endpoints
- Taux d'erreur par endpoint
- Utilisation mémoire et CPU
- Taille des logs

## Extension et maintenance

### Ajouter un nouveau type de compte
1. Modifier l'enum dans la migration
2. Mettre à jour les règles de validation
3. Adapter la logique métier si nécessaire

### Ajouter une nouvelle notification
1. Créer un nouvel Event
2. Créer le Listener correspondant
3. Enregistrer dans `EventServiceProvider`

### Personnaliser les règles de validation
1. Étendre les classes `ValidationRule`
2. Modifier les messages d'erreur
3. Tester les nouveaux scénarios

## Support et contribution

### Signaler un bug
1. Vérifier les logs d'erreur
2. Reproduire le problème
3. Ouvrir une issue avec les détails

### Contribuer
1. Forker le projet
2. Créer une branche feature
3. Écrire des tests
4. Soumettre une pull request

## Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.

---

**Version 1.0.0** - Octobre 2025
Développé avec Laravel 10 et principes SOLID/DDD