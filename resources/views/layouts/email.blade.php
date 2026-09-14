{{-- Coquille e-mail transactionnel MALKUTHAR.
     Styles inline uniquement (compatibilité clients mail). --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $title ?? 'MALKUTHAR' }}</title>
</head>
<body style="margin:0; padding:0; background:#faf9f6; font-family:'Plus Jakarta Sans',Segoe UI,Roboto,Helvetica,Arial,sans-serif; color:#141310;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#faf9f6;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border:1px solid #e8e5de; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="padding:22px 28px 18px; border-bottom:2px solid #9a7b3e;">
                            <span style="font-size:15px; font-weight:700; letter-spacing:3px; color:#141310;">MALKUTHAR</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px; font-size:15px; line-height:1.65; color:#57534b;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px 26px; border-top:1px solid #e8e5de; font-size:12px; color:#8c877d;">
                            © {{ date('Y') }} MALKUTHAR SARL — cet e-mail vous est envoyé suite à une action sur votre compte.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
