<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue chez Banque Example</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .credentials { background-color: #fff; padding: 15px; border: 1px solid #ddd; margin: 20px 0; }
        .warning { color: #dc3545; font-weight: bold; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bienvenue chez Banque Example</h1>
            <p>Votre compte bancaire a été créé avec succès</p>
        </div>

        <div class="content">
            <p>Bonjour <strong>{{ $user->client->nom }}</strong>,</p>

            <p>Félicitations ! Votre compte bancaire a été créé avec succès. Voici vos identifiants de connexion :</p>

            <div class="credentials">
                <h3>Vos identifiants :</h3>
                <p><strong>Login :</strong> {{ $user->login }}</p>
                <p><strong>Mot de passe :</strong> {{ $motDePasseGenere }}</p>
                <p><strong>Numéro de compte :</strong> {{ $compte->numero_compte }}</p>
            </div>

            <div class="warning">
                <p>⚠️ <strong>Important :</strong> Conservez ces informations en lieu sûr et changez votre mot de passe lors de votre première connexion.</p>
            </div>

            <h3>Détails de votre compte :</h3>
            <ul>
                <li><strong>Type de compte :</strong> {{ ucfirst($compte->type) }}</li>
                <li><strong>Solde initial :</strong> {{ number_format($compte->solde, 2, ',', ' ') }} {{ $compte->devise }}</li>
                <li><strong>Statut :</strong> {{ ucfirst($compte->statut) }}</li>
                <li><strong>Date de création :</strong> {{ $compte->created_at->format('d/m/Y H:i') }}</li>
            </ul>

            <p>Pour activer votre compte, vous devrez utiliser le code d'activation qui vous a été envoyé par SMS.</p>

            <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>

            <p>Cordialement,<br>
            L'équipe Banque Example</p>
        </div>

        <div class="footer">
            <p>Cet e-mail a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>&copy; 2025 Banque Example. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>