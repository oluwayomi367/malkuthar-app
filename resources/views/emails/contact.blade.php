<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f7f5f0;font-family:Poppins,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1c1917;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f5f0;">
<tr><td align="center" style="padding:32px 16px;">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fffefb;border:1px solid #e4ded0;border-radius:16px;overflow:hidden;">
        <tr><td style="padding:22px 28px 18px;border-bottom:1px solid #8a6d2c;">
            <span style="font-family:Poppins,'Segoe UI',Helvetica,Arial,sans-serif;font-size:22px;font-weight:600;letter-spacing:4px;color:#1c1917;">MALKUTHAR</span>
        </td></tr>
        <tr><td style="padding:28px;font-size:15px;line-height:1.7;color:#57534e;">
            <p style="margin:0 0 16px;color:#1c1917;font-size:17px;font-weight:600;">Nouveau message de contact</p>
            <p style="margin:0 0 4px;"><strong>De :</strong> {{ $senderName }} &lt;{{ $senderEmail }}&gt;</p>
            <p style="margin:0 0 16px;"><strong>Sujet :</strong> {{ $subjectLabel }}</p>
            <div style="border-top:1px solid #e4ded0;padding-top:16px;white-space:pre-wrap;">{{ $body }}</div>
            <p style="margin:20px 0 0;font-size:13px;color:#6b665e;">Répondez directement à cet e-mail pour contacter {{ $senderName }}.</p>
        </td></tr>
        <tr><td style="padding:18px 28px 26px;border-top:1px solid #e4ded0;font-size:12px;color:#6b665e;">
            © {{ date('Y') }} MALKUTHAR SARL
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
