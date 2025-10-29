<?php

namespace App\Constants;

/**
 * Classe contenant tous les messages d'erreur et de succès utilisés dans l'application
 * Centralise les messages pour une meilleure maintenabilité et cohérence
 */
class Messages
{
    const SUCCESS_OPERATION = 'Opération réalisée avec succès';
    const SUCCESS_CREATED = 'Créé avec succès';
    const SUCCESS_UPDATED = 'Mis à jour avec succès';
    const SUCCESS_DELETED = 'Supprimé avec succès';
    const SUCCESS_LOGIN = 'Connexion réussie';
    const SUCCESS_LOGOUT = 'Déconnexion réussie';

    const COMPTE_CREATED = 'Compte créé avec succès';
    const COMPTE_UPDATED = 'Compte mis à jour avec succès';
    const COMPTE_DELETED = 'Compte supprimé avec succès';
    const COMPTE_BLOCKED = 'Compte bloqué avec succès';
    const COMPTE_UNBLOCKED = 'Compte débloqué avec succès';

    const TRANSACTION_CREATED = 'Transaction créée avec succès';
    const TRANSACTION_VALIDATED = 'Transaction validée avec succès';
    const TRANSACTION_CANCELLED = 'Transaction annulée avec succès';

    const ERROR_UNAUTHORIZED = 'Authentification requise';
    const ERROR_FORBIDDEN = 'Accès interdit';
    const ERROR_NOT_FOUND = 'Ressource non trouvée';
    const ERROR_VALIDATION = 'Les données fournies sont invalides';
    const ERROR_SERVER = 'Erreur interne du serveur';
    const ERROR_BAD_REQUEST = 'Requête invalide';

    const COMPTE_NOT_FOUND = 'Compte non trouvé';
    const COMPTE_ALREADY_EXISTS = 'Un compte avec ces informations existe déjà';
    const COMPTE_CANNOT_BLOCK = 'Ce compte ne peut pas être bloqué';
    const COMPTE_CANNOT_UNBLOCK = 'Ce compte ne peut pas être débloqué';
    const COMPTE_INSUFFICIENT_BALANCE = 'Solde insuffisant';
    const COMPTE_ALREADY_BLOCKED = 'Le compte est déjà bloqué';
    const COMPTE_NOT_BLOCKED = 'Le compte n\'est pas bloqué';
    const COMPTE_ACTIVE_REQUIRED = 'Le compte doit être actif pour cette opération';
    const COMPTE_CHEQUE_CANNOT_BLOCK = 'Un compte chèque ne peut pas être bloqué, seulement fermé';
    const COMPTE_EPARGNE_ONLY_BLOCK = 'Seuls les comptes épargne peuvent être bloqués';

    const CLIENT_NOT_FOUND = 'Client non trouvé';
    const CLIENT_ALREADY_EXISTS = 'Un client avec ces informations existe déjà';
    const CLIENT_CANNOT_DELETE = 'Ce client ne peut pas être supprimé';

    const USER_NOT_FOUND = 'Utilisateur non trouvé';
    const USER_ALREADY_EXISTS = 'Un utilisateur avec ces informations existe déjà';
    const USER_INVALID_CREDENTIALS = 'Identifiants invalides';
    const USER_ACCOUNT_LOCKED = 'Compte utilisateur verrouillé';
    const USER_EMAIL_NOT_VERIFIED = 'Adresse e-mail non vérifiée';

    const TRANSACTION_NOT_FOUND = 'Transaction non trouvée';
    const TRANSACTION_ALREADY_VALIDATED = 'Cette transaction est déjà validée';
    const TRANSACTION_CANNOT_CANCEL = 'Cette transaction ne peut pas être annulée';
    const TRANSACTION_INVALID_AMOUNT = 'Montant de transaction invalide';
    const TRANSACTION_INVALID_TYPE = 'Type de transaction invalide';

    const AUTH_INVALID_TOKEN = 'Token d\'authentification invalide';
    const AUTH_TOKEN_EXPIRED = 'Token d\'authentification expiré';
    const AUTH_TOKEN_MISSING = 'Token d\'authentification manquant';

    const EMAIL_SEND_FAILED = 'Échec de l\'envoi de l\'e-mail';
    const EMAIL_INVALID = 'Adresse e-mail invalide';
    const EMAIL_ALREADY_EXISTS = 'Cette adresse e-mail est déjà utilisée';

    const SMS_SEND_FAILED = 'Échec de l\'envoi du SMS';
    const SMS_INVALID_NUMBER = 'Numéro de téléphone invalide';

    const FILE_UPLOAD_FAILED = 'Échec du téléchargement du fichier';
    const FILE_INVALID_TYPE = 'Type de fichier invalide';
    const FILE_TOO_LARGE = 'Fichier trop volumineux';
    const FILE_NOT_FOUND = 'Fichier non trouvé';

    const INFO_OPERATION_IN_PROGRESS = 'Opération en cours';
    const INFO_OPERATION_COMPLETED = 'Opération terminée';
    const INFO_DATA_UPDATED = 'Données mises à jour';

    /**
     * Récupère un message par sa clé
     *
     * @param string $key
     * @return string
     */
    public static function get(string $key): string
    {
        return constant("self::$key") ?? $key;
    }

    /**
     * Vérifie si une clé de message existe
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return defined("self::$key");
    }
}