<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email - Banque Example</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2d3748; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 5px 5px; }
        .credentials { background: white; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #2d3748; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Test Email - Banque Example</h1>
            <p>Ceci est un e-mail de test pour vérifier la configuration Gmail</p>
        </div>

        <div class="content">
            <p>Bonjour,</p>

            <p>Cet e-mail confirme que votre configuration Gmail fonctionne parfaitement !</p>

            <div class="credentials">
                <h3>✅ Configuration réussie :</h3>
                <ul>
                    <li><strong>Serveur SMTP :</strong> smtp.gmail.com</li>
                    <li><strong>Port :</strong> 587</li>
                    <li><strong>Chiffrement :</strong> TLS</li>
                    <li><strong>Expéditeur :</strong> fadloulahi14@gmail.com</li>
                </ul>
            </div>

            <p>Si vous recevez cet e-mail, cela signifie que :</p>
            <ul>
                <li>✅ Votre mot de passe d'application Gmail est correct</li>
                <li>✅ La configuration Laravel Mail est valide</li>
                <li>✅ Les e-mails peuvent être envoyés depuis votre application</li>
            </ul>

            <p>Vous pouvez maintenant utiliser pleinement le système d'e-mails de votre API bancaire !</p>

            <p>Cordialement,<br>
            L'équipe Banque Example</p>
        </div>

        <div class="footer">
            <p>Cet e-mail a été envoyé automatiquement depuis votre application Laravel.</p>
            <p>&copy; 2025 Banque Example. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>