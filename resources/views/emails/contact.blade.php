<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#faf9f6;font-family:'Plus Jakarta Sans',Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#141310;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#faf9f6;">
<tr><td align="center" style="padding:32px 16px;">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border:1px solid #e8e5de;border-radius:8px;overflow:hidden;">
        <tr><td style="padding:22px 28px 18px;border-bottom:2px solid #9a7b3e;">
            <span style="font-size:15px;font-weight:700;letter-spacing:3px;">MALKUTHAR</span>
        </td></tr>
        <tr><td style="padding:28px;font-size:15px;line-height:1.65;color:#57534b;">
            <p style="margin:0 0 12px;color:#141310;font-weight:600;">Nouveau message de contact</p>
            <p style="margin:0 0 4px;"><strong>De :</strong> {{ $senderName }} &lt;{{ $senderEmail }}&gt;</p>
            <p style="margin:0 0 16px;"><strong>Sujet :</strong> {{ $subjectLabel }}</p>
            <div style="border-top:1px solid #e8e5de;padding-top:16px;white-space:pre-wrap;">{{ $body }}</div>
            <p style="margin:20px 0 0;font-size:13px;color:#8c877d;">Répondez directement à cet e-mail pour contacter {{ $senderName }}.</p>
        </td></tr>
        <tr><td style="padding:18px 28px 26px;border-top:1px solid #e8e5de;font-size:12px;color:#8c877d;">
            © {{ date('Y') }} MALKUTHAR SARL
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
