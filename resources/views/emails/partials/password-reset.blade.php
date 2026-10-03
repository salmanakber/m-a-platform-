<p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#0a2a42;">
    Guten Tag <strong style="color:#0E4971;">{{ $name }}</strong>,
</p>
<p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#0a2a42;">
    Sie haben angefordert, Ihr Passwort für <strong style="color:#0E4971;">nachfolge-experten.ch</strong> zurückzusetzen.
    Klicken Sie auf den Button unten, um ein neues Passwort festzulegen.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 20px;background-color:#f7fafc;border:1px solid #c5d6e4;">
    <tr>
        <td style="padding:16px;">
            <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#009DE1;">Sicherheitshinweis</p>
            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#0a2a42;">
                Der Link ist <strong>{{ $expiresMinutes }} Minuten</strong> gültig. Wenn Sie diese Anfrage nicht gestellt haben, können Sie diese E-Mail ignorieren — Ihr Passwort bleibt unverändert.
            </p>
        </td>
    </tr>
</table>

<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.55;color:#5a6b78;">
    Falls der Button nicht funktioniert, kopieren Sie diesen Link in Ihren Browser:<br>
    <a href="{{ $resetUrl }}" style="color:#009DE1;word-break:break-all;text-decoration:none;">{{ $resetUrl }}</a>
</p>
