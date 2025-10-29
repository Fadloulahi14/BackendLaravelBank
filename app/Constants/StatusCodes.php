<?php

namespace App\Constants;

/**
 * Classe contenant tous les codes de statut HTTP utilisés dans l'application
 * Centralise les codes HTTP pour une meilleure maintenabilité et cohérence
 */
class StatusCodes
{
    // Codes de succès (2xx)
    const OK = 200;
    const CREATED = 201;
    const ACCEPTED = 202;
    const NO_CONTENT = 204;

    // Codes de redirection (3xx)
    const MOVED_PERMANENTLY = 301;
    const FOUND = 302;
    const NOT_MODIFIED = 304;

    // Codes d'erreur client (4xx)
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const CONFLICT = 409;
    const UNPROCESSABLE_ENTITY = 422;
    const TOO_MANY_REQUESTS = 429;

    // Codes d'erreur serveur (5xx)
    const INTERNAL_SERVER_ERROR = 500;
    const NOT_IMPLEMENTED = 501;
    const BAD_GATEWAY = 502;
    const SERVICE_UNAVAILABLE = 503;
    const GATEWAY_TIMEOUT = 504;

    // Codes spécifiques à l'application
    const VALIDATION_ERROR = 422;
    const BUSINESS_LOGIC_ERROR = 400;
    const RESOURCE_NOT_FOUND = 404;
    const UNAUTHENTICATED = 401;
    const INSUFFICIENT_PERMISSIONS = 403;

    /**
     * Récupère le code HTTP par sa clé
     *
     * @param string $key
     * @return int
     */
    public static function get(string $key): int
    {
        return constant("self::$key") ?? self::INTERNAL_SERVER_ERROR;
    }

    /**
     * Vérifie si une clé de code existe
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return defined("self::$key");
    }

    /**
     * Vérifie si le code est un code de succès (2xx)
     *
     * @param int $code
     * @return bool
     */
    public static function isSuccess(int $code): bool
    {
        return $code >= 200 && $code < 300;
    }

    /**
     * Vérifie si le code est un code d'erreur client (4xx)
     *
     * @param int $code
     * @return bool
     */
    public static function isClientError(int $code): bool
    {
        return $code >= 400 && $code < 500;
    }

    /**
     * Vérifie si le code est un code d'erreur serveur (5xx)
     *
     * @param int $code
     * @return bool
     */
    public static function isServerError(int $code): bool
    {
        return $code >= 500 && $code < 600;
    }

    /**
     * Retourne le message par défaut pour un code HTTP
     *
     * @param int $code
     * @return string
     */
    public static function getDefaultMessage(int $code): string
    {
        $messages = [
            self::OK => 'OK',
            self::CREATED => 'Created',
            self::ACCEPTED => 'Accepted',
            self::NO_CONTENT => 'No Content',
            self::MOVED_PERMANENTLY => 'Moved Permanently',
            self::FOUND => 'Found',
            self::NOT_MODIFIED => 'Not Modified',
            self::BAD_REQUEST => 'Bad Request',
            self::UNAUTHORIZED => 'Unauthorized',
            self::FORBIDDEN => 'Forbidden',
            self::NOT_FOUND => 'Not Found',
            self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
            self::CONFLICT => 'Conflict',
            self::UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
            self::TOO_MANY_REQUESTS => 'Too Many Requests',
            self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
            self::NOT_IMPLEMENTED => 'Not Implemented',
            self::BAD_GATEWAY => 'Bad Gateway',
            self::SERVICE_UNAVAILABLE => 'Service Unavailable',
            self::GATEWAY_TIMEOUT => 'Gateway Timeout',
        ];

        return $messages[$code] ?? 'Unknown Status Code';
    }
}