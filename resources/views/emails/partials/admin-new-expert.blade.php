<p style="margin:0 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#0a2a42;">
    Es liegt eine neue Experten-Registrierung zur Prüfung vor.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 8px;background-color:#f7fafc;border:1px solid #c5d6e4;">
    <tr>
        <td style="padding:14px 16px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#5a6b78;width:120px;">Firma</td>
        <td style="padding:14px 16px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#0a2a42;font-weight:700;">{{ $company }}</td>
    </tr>
    <tr>
        <td style="padding:14px 16px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#5a6b78;">Kontakt</td>
        <td style="padding:14px 16px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#0a2a42;">{{ $contact !== '' ? $contact : '—' }}</td>
    </tr>
    <tr>
        <td style="padding:14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#5a6b78;">E-Mail</td>
        <td style="padding:14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;">
            <a href="mailto:{{ $email }}" style="color:#009DE1;text-decoration:none;font-weight:700;">{{ $email }}</a>
        </td>
    </tr>
</table>
