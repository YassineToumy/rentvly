<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Code de vérification</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f4f5;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:480px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e4e4e7;">
          <tr>
            <td style="height:4px;background:linear-gradient(90deg,#22c55e,#10b981);"></td>
          </tr>
          <tr>
            <td style="padding:32px 28px;">
              <p style="margin:0 0 8px;font-size:13px;color:#71717a;letter-spacing:0.04em;text-transform:uppercase;">Rentvly</p>
              <h1 style="margin:0 0 12px;font-size:22px;color:#18181b;">Vérification de votre compte</h1>
              <p style="margin:0 0 24px;font-size:15px;line-height:1.5;color:#52525b;">
                Bonjour {{ $userName }},<br>
                Bienvenue sur Rentvly&nbsp;! Voici votre code de vérification. Il expire dans <strong>15 minutes</strong>.
              </p>
              <div style="text-align:center;margin:0 0 24px;">
                <div style="display:inline-block;padding:14px 28px;background:#f4f4f5;border-radius:12px;border:1px solid #e4e4e7;">
                  <span style="font-size:32px;font-weight:700;letter-spacing:0.35em;color:#18181b;">{{ $code }}</span>
                </div>
              </div>
              <p style="margin:0;font-size:13px;line-height:1.5;color:#a1a1aa;">
                Si vous n’êtes pas à l’origine de cette inscription, ignorez cet e-mail.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
