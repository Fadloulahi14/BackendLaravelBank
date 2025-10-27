<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // en millisecondes

        // Log les opérations sur les comptes (création, mise à jour, suppression, blocage/déblocage)
        if (str_contains($request->path(), 'comptes')) {
            $operation = $this->getOperationType($request);

            if ($operation) {
                Log::channel('compte_operations')->info($operation, [
                    'timestamp' => now()->toISOString(),
                    'host' => $request->getHost(),
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                    'user_id' => $request->user() ? $request->user()->id : null,
                    'method' => $request->method(),
                    'endpoint' => $request->path(),
                    'status_code' => $response->getStatusCode(),
                    'duration_ms' => $duration,
                    'request_data' => $this->sanitizeRequestData($request),
                    'response_status' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 ? 'success' : 'error'
                ]);
            }
        }

        return $response;
    }

    /**
     * Déterminer le type d'opération
     */
    private function getOperationType(Request $request): ?string
    {
        $method = $request->method();
        $path = $request->path();

        if ($method === 'POST' && str_ends_with($path, '/comptes')) {
            return 'Création de compte bancaire';
        }

        if ($method === 'PATCH' && preg_match('/\/comptes\/[^\/]+$/', $path)) {
            return 'Mise à jour de compte bancaire';
        }

        if ($method === 'DELETE' && preg_match('/\/comptes\/[^\/]+$/', $path)) {
            return 'Suppression de compte bancaire';
        }

        if ($method === 'POST' && str_contains($path, '/bloquer')) {
            return 'Blocage de compte bancaire';
        }

        if ($method === 'POST' && str_contains($path, '/debloquer')) {
            return 'Déblocage de compte bancaire';
        }

        return null;
    }

    /**
     * Nettoyer les données sensibles de la requête
     */
    private function sanitizeRequestData(Request $request): array
    {
        $data = $request->all();

        // Masquer les mots de passe et autres données sensibles
        $sensitiveFields = ['password', 'mot_de_passe', 'code_activation'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***masked***';
            }
        }

        return $data;
    }
}
