# Documentation API - Gestion des Clients & Comptes

## Vue d'ensemble

Cette API RESTful permet de gérer les clients et leurs comptes bancaires. Elle suit les principes REST, utilise des UUID comme clés primaires et implémente un versioning (v1).

**Base URL :** `http://localhost:8000/api/v1`

## Authentification

L'API ne nécessite pas d'authentification pour l'instant (comme spécifié dans les exigences).

## Format de réponse standard

Toutes les réponses suivent ce format uniforme :

### Succès
```json
{
  "success": true,
  "message": "Opération réussie",
  "data": { /* données spécifiques */ }
}
```

### Erreur
```json
{
  "success": false,
  "message": "Description de l'erreur",
  "errors": {
    "code": "ERROR_CODE",
    "details": { /* contexte de l'erreur */ }
  }
}
```

### Pagination
```json
{
  "success": true,
  "data": [ /* tableau d'éléments */ ],
  "pagination": {
    "currentPage": 1,
    "totalPages": 5,
    "totalItems": 50,
    "itemsPerPage": 10,
    "hasNext": true,
    "hasPrevious": false
  },
  "links": {
    "self": "/api/v1/comptes?page=1",
    "next": "/api/v1/comptes?page=2",
    "first": "/api/v1/comptes?page=1",
    "last": "/api/v1/comptes?page=5"
  }
}
```

## Endpoints

### 1. Comptes bancaires

#### GET `/api/v1/comptes` - Lister tous les comptes

Récupère une liste paginée de comptes avec possibilité de filtrage et tri.

**Paramètres de requête :**
- `page` (integer, optionnel) : Numéro de page (défaut: 1)
- `limit` (integer, optionnel) : Nombre d'éléments par page (défaut: 10, max: 100)
- `type` (string, optionnel) : Filtrer par type (`epargne`, `cheque`)
- `statut` (string, optionnel) : Filtrer par statut (`actif`, `bloque`, `ferme`)
- `search` (string, optionnel) : Recherche par numéro de compte ou nom du titulaire
- `sort` (string, optionnel) : Champ de tri (`dateCreation`, `solde`, `titulaire`) - défaut: `dateCreation`
- `order` (string, optionnel) : Ordre de tri (`asc`, `desc`) - défaut: `desc`

**Exemple de requête :**
```
GET /api/v1/comptes?page=1&limit=10&type=epargne&statut=actif&sort=dateCreation&order=desc
```

**Réponse 200 :**
```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "numeroCompte": "C00123456",
      "titulaire": "Amadou Diallo",
      "type": "epargne",
      "solde": 1250000,
      "devise": "FCFA",
      "dateCreation": "2023-03-15T00:00:00Z",
      "statut": "bloque",
      "metadonnees": {
        "derniereModification": "2023-06-10T14:30:00Z",
        "version": 1
      }
    }
  ],
  "pagination": {
    "currentPage": 1,
    "totalPages": 3,
    "totalItems": 25,
    "itemsPerPage": 10,
    "hasNext": true,
    "hasPrevious": false
  },
  "links": {
    "self": "/api/v1/comptes?page=1&limit=10",
    "next": "/api/v1/comptes?page=2&limit=10",
    "first": "/api/v1/comptes?page=1&limit=10",
    "last": "/api/v1/comptes?page=3&limit=10"
  }
}
```

#### POST `/api/v1/comptes` - Créer un compte

Crée un nouveau compte bancaire avec les informations du client.

**En-têtes de requête :**
- `Authorization: Bearer {token}`
- `Accept: application/json`
- `Content-Type: application/json`

**Corps de la requête :**
```json
{
  "type": "cheque",
  "soldeInitial": 500000,
  "devise": "FCFA",
  "solde": 10000,
  "client": {
    "titulaire": "Fadil dev",
    "nci": "",
    "email": "fadilddev@example.com",
    "telephone": "+221771234567",
    "adresse": "Dakar, Sénégal"
  }
}
```

**Règles de validation :**
- Tous les champs sont obligatoires
- Téléphone est unique et respecte les critères d'un téléphone portable Sénégalais
- Solde à la création est supérieur ou égal à 10000
- L'email est unique
- Le numéro de téléphone est unique et respecte les règles d'un NCI Sénégalais

**Réponse 201 :**
```json
{
  "success": true,
  "message": "Compte créé avec succès",
  "data": {
    "id": "660f9511-f30c-52e5-b827-557766551111",
    "numeroCompte": "C00123460",
    "titulaire": "fadild dev",
    "type": "cheque",
    "solde": 500000,
    "devise": "FCFA",
    "dateCreation": "2025-10-19T10:30:00Z",
    "statut": "actif",
    "metadata": {
      "derniereModification": "2025-10-19T10:30:00Z",
      "version": 1
    }
  }
}
```

**Réponse 400 (Bad Request) :**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Les données fournies sont invalides",
    "details": {
      "titulaire": "Le nom du titulaire est requis",
      "soldeInitial": "Le solde initial doit être supérieur à 0"
    }
  }
}
```

#### GET `/api/v1/comptes/{id}` - Détails d'un compte

Récupère les détails d'un compte spécifique.

**Paramètres d'URL :**
- `id` (string, UUID) : ID du compte

**Réponse 200 :**
```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "numeroCompte": "C00123456",
    "titulaire": "Amadou Diallo",
    "type": "epargne",
    "solde": 1250000,
    "devise": "FCFA",
    "dateCreation": "2023-03-15T00:00:00Z",
    "statut": "bloque",
    "metadonnees": {
      "derniereModification": "2023-06-10T14:30:00Z",
      "version": 1
    }
  }
}
```

**Réponse 404 (Compte non trouvé) :**
```json
{
  "success": false,
  "message": "Le compte avec l'ID spécifié n'existe pas",
  "errors": {
    "code": "COMPTE_NOT_FOUND",
    "details": {
      "compteId": "550e8400-e29b-41d4-a716-446655440000"
    }
  }
}
```

#### PATCH `/api/v1/comptes/{id}` - Mettre à jour un compte

Met à jour partiellement un compte existant.

**Paramètres d'URL :**
- `id` (string, UUID) : ID du compte

**Corps de la requête :**
```json
{
  "solde": 1500000,
  "statut": "actif",
  "metadonnees": {
    "derniereModification": "2023-10-22T11:00:00Z",
    "version": 2
  }
}
```

**Réponse 200 :**
```json
{
  "success": true,
  "message": "Compte mis à jour avec succès",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "numeroCompte": "C00123456",
    "titulaire": "Amadou Diallo",
    "type": "epargne",
    "solde": 1500000,
    "devise": "FCFA",
    "dateCreation": "2023-03-15T00:00:00Z",
    "statut": "actif",
    "metadonnees": {
      "derniereModification": "2023-10-22T11:00:00Z",
      "version": 2
    }
  }
}
```

#### DELETE `/api/v1/comptes/{id}` - Supprimer un compte

Supprime un compte existant.

**Paramètres d'URL :**
- `id` (string, UUID) : ID du compte

**Réponse 200 :**
```json
{
  "success": true,
  "message": "Compte supprimé avec succès"
}
```

### 2. Utilisateurs

#### GET `/api/v1/users` - Lister tous les utilisateurs

Récupère une liste paginée d'utilisateurs.

**Paramètres de requête :**
- `page` (integer, optionnel) : Numéro de page (défaut: 1)
- `limit` (integer, optionnel) : Nombre d'éléments par page (défaut: 10, max: 100)
- `role` (string, optionnel) : Filtrer par rôle (`admin`, `client`)
- `search` (string, optionnel) : Recherche par nom, email ou téléphone
- `sort` (string, optionnel) : Champ de tri (`dateCreation`, `nom`) - défaut: `dateCreation`
- `order` (string, optionnel) : Ordre de tri (`asc`, `desc`) - défaut: `desc`

**Exemple de requête :**
```
GET /api/v1/users?page=1&limit=10&role=client&sort=nom&order=asc
```

**Réponse 200 :**
```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "nom": "Amadou Diallo",
      "nci": "123456789012",
      "email": "amadou.diallo@email.com",
      "telephone": "+221771234567",
      "adresse": "Dakar, Sénégal",
      "role": "client",
      "dateCreation": "2023-03-15T00:00:00Z",
      "derniereModification": "2023-06-10T14:30:00Z"
    }
  ],
  "pagination": {
    "currentPage": 1,
    "totalPages": 2,
    "totalItems": 15,
    "itemsPerPage": 10,
    "hasNext": true,
    "hasPrevious": false
  },
  "links": {
    "self": "/api/v1/users?page=1&limit=10",
    "next": "/api/v1/users?page=2&limit=10",
    "first": "/api/v1/users?page=1&limit=10",
    "last": "/api/v1/users?page=2&limit=10"
  }
}
```

#### POST `/api/v1/users` - Créer un utilisateur

Crée un nouvel utilisateur.

**Corps de la requête :**
```json
{
  "nom": "Fatou Sow",
  "nci": "987654321098",
  "email": "fatou.sow@email.com",
  "telephone": "+221782345678",
  "adresse": "Thiès, Sénégal",
  "role": "client"
}
```

**Réponse 201 :**
```json
{
  "success": true,
  "message": "Utilisateur créé avec succès",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440002",
    "nom": "Fatou Sow",
    "nci": "987654321098",
    "email": "fatou.sow@email.com",
    "telephone": "+221782345678",
    "adresse": "Thiès, Sénégal",
    "role": "client",
    "dateCreation": "2023-10-22T12:00:00Z",
    "derniereModification": "2023-10-22T12:00:00Z"
  }
}
```

#### GET `/api/v1/users/{id}` - Détails d'un utilisateur

Récupère les détails d'un utilisateur avec ses comptes.

**Paramètres d'URL :**
- `id` (string, UUID) : ID de l'utilisateur

**Réponse 200 :**
```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "nom": "Amadou Diallo",
    "nci": "123456789012",
    "email": "amadou.diallo@email.com",
    "telephone": "+221771234567",
    "adresse": "Dakar, Sénégal",
    "role": "client",
    "dateCreation": "2023-03-15T00:00:00Z",
    "derniereModification": "2023-06-10T14:30:00Z"
  }
}
```

#### PATCH `/api/v1/users/{id}` - Mettre à jour un utilisateur

Met à jour partiellement un utilisateur.

**Paramètres d'URL :**
- `id` (string, UUID) : ID de l'utilisateur

**Corps de la requête :**
```json
{
  "nom": "Amadou Diallo Jr",
  "telephone": "+221773456789"
}
```

**Réponse 200 :**
```json
{
  "success": true,
  "message": "Utilisateur mis à jour avec succès",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "nom": "Amadou Diallo Jr",
    "nci": "123456789012",
    "email": "amadou.diallo@email.com",
    "telephone": "+221773456789",
    "adresse": "Dakar, Sénégal",
    "role": "client",
    "dateCreation": "2023-03-15T00:00:00Z",
    "derniereModification": "2023-10-22T13:00:00Z"
  }
}
```

#### DELETE `/api/v1/users/{id}` - Supprimer un utilisateur

Supprime un utilisateur existant.

**Paramètres d'URL :**
- `id` (string, UUID) : ID de l'utilisateur

**Réponse 200 :**
```json
{
  "success": true,
  "message": "Utilisateur supprimé avec succès"
}
```

## Codes d'erreur

### Erreurs de validation (422)
```json
{
  "success": false,
  "message": "Erreur de validation",
  "errors": {
    "email": ["L'adresse email est déjà utilisée."],
    "telephone": ["Le numéro de téléphone est déjà utilisé."]
  }
}
```

### Ressource non trouvée (404)
```json
{
  "success": false,
  "message": "Le compte avec l'ID spécifié n'existe pas",
  "errors": {
    "code": "COMPTE_NOT_FOUND",
    "details": {
      "compteId": "550e8400-e29b-41d4-a716-446655440000"
    }
  }
}
```

### Erreur serveur (500)
```json
{
  "success": false,
  "message": "Erreur interne du serveur",
  "errors": {
    "code": "INTERNAL_SERVER_ERROR"
  }
}
```

## Schémas de données

### Compte
```json
{
  "id": "string (UUID)",
  "numeroCompte": "string",
  "titulaire": "string",
  "type": "epargne | cheque",
  "solde": "number",
  "devise": "string",
  "dateCreation": "datetime",
  "statut": "actif | bloque | ferme",
  "metadonnees": {
    "derniereModification": "datetime",
    "version": "integer"
  }
}
```

### Utilisateur
```json
{
  "id": "string (UUID)",
  "nom": "string",
  "nci": "string",
  "email": "string",
  "telephone": "string",
  "adresse": "string",
  "role": "admin | client",
  "dateCreation": "datetime",
  "derniereModification": "datetime"
}
```

## Configuration CORS

L'API est configurée pour accepter les requêtes cross-origin avec les méthodes suivantes :
- GET, POST, PUT, PATCH, DELETE, OPTIONS
- Cache des preflight requests : 24 heures
- Headers ouverts pour la flexibilité

## Documentation Swagger

La documentation interactive Swagger/OpenAPI est disponible à l'adresse :
`http://localhost:8000/api/documentation`

Cette documentation fournit une interface interactive pour tester tous les endpoints de l'API.

## Notes importantes

1. **UUID** : Tous les identifiants utilisent le format UUID v4 pour la sécurité
2. **Numéros de compte** : Générés automatiquement avec le préfixe "C" suivi de 10 caractères alphanumériques
3. **Pagination** : Implémentée sur toutes les listes avec métadonnées complètes
4. **Tri et filtrage** : Support avancé pour faciliter la recherche et la navigation
5. **Validation** : Contraintes strictes sur les données pour garantir l'intégrité
6. **Gestion d'erreurs** : Messages d'erreur structurés et informatifs

## Tests

Pour tester l'API, vous pouvez utiliser :

1. **Swagger UI** : `http://localhost:8000/api/documentation`
2. **cURL** ou **Postman** avec les exemples fournis
3. **Laravel Test** : Tests unitaires et fonctionnels inclus

L'API est maintenant prête pour l'intégration frontend et les développements supplémentaires.