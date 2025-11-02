<?php

namespace App\Docs\Swagger;

/**
 * @OA\Info(
 *   title="Compte API",
 *   version="1.0.0",
 *   description="API pour la gestion des comptes, transactions et utilisateurs"
 * )
 *
 * @OA\Server(url="/api", description="API v1 server")
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT"
 * )
 *
 * @OA\Tag(name="Auth", description="Authentification et tokens")
 * @OA\Tag(name="Comptes", description="Opérations sur les comptes")
 * @OA\Tag(name="Transactions", description="Opérations sur les transactions")
 */
final class ApiInfo
{
	// This class only exists to carry the OpenAPI annotations.
}
