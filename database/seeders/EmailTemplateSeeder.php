<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'lead_notification_expert',
                'subject' => 'Neue Lead-Anfrage von {{owner_name}}',
                'body_html' => <<<'HTML'
<p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#0a2a42;">
    Guten Tag,<br>
    Sie haben eine neue Anfrage über <strong style="color:#0E4971;">nachfolge-experten.ch</strong> erhalten.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 20px;background-color:#f7fafc;border:1px solid #c5d6e4;">
    <tr>
        <td style="padding:12px 16px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#5a6b78;width:130px;">Name</td>
        <td style="padding:12px 16px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#0a2a42;font-weight:700;">{{owner_name}}</td>
    </tr>
    <tr>
        <td style="padding:12px 16px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#5a6b78;">E-Mail</td>
        <td style="padding:12px 16px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:15px;"><a href="mailto:{{email}}" style="color:#009DE1;text-decoration:none;font-weight:700;">{{email}}</a></td>
    </tr>
    <tr>
        <td style="padding:12px 16px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#5a6b78;">Telefon</td>
        <td style="padding:12px 16px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#0a2a42;">{{phone}}</td>
    </tr>
    <tr>
        <td style="padding:12px 16px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#5a6b78;">Firma</td>
        <td style="padding:12px 16px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#0a2a42;">{{company_name}}</td>
    </tr>
    <tr>
        <td style="padding:12px 16px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#5a6b78;">Branche</td>
        <td style="padding:12px 16px;border-bottom:1px solid #e0ebf3;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#0a2a42;">{{industry}}</td>
    </tr>
    <tr>
        <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#5a6b78;">Kanton</td>
        <td style="padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#0a2a42;">{{canton_name}}</td>
    </tr>
</table>

<p style="margin:0 0 8px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#5a6b78;">Nachricht</p>
<p style="margin:0 0 18px;padding:14px 16px;background-color:#e5f5fd;border:1px solid #9fc5de;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.55;color:#0a2a42;">{{message}}</p>

<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#5a6b78;">
    Bitte kontaktieren Sie den Interessenten direkt. Der Basis-Listing-Service ist kostenlos.
</p>
HTML,
                'body_text' => "Neue Lead-Anfrage von {{owner_name}}\n\nE-Mail: {{email}}\nTelefon: {{phone}}\nFirma: {{company_name}}\nBranche: {{industry}}\nKanton: {{canton_name}}\n\nNachricht:\n{{message}}",
            ],
            [
                'key' => 'lead_confirmation_customer',
                'subject' => 'Ihre Anfrage wurde übermittelt',
                'body_html' => <<<'HTML'
<p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#0a2a42;">
    Guten Tag <strong style="color:#0E4971;">{{owner_name}}</strong>,
</p>
<p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#0a2a42;">
    vielen Dank für Ihre Anfrage. Ihre Kontaktdaten und Nachricht wurden an
    <strong style="color:#0E4971;">{{expert_company_name}}</strong> weitergeleitet.
</p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 18px;background-color:#f7fafc;border:1px solid #c5d6e4;">
    <tr>
        <td style="padding:16px;">
            <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#009DE1;">Nächster Schritt</p>
            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.55;color:#0a2a42;">
                Die M&amp;A-Beratung wird sich direkt bei Ihnen melden. Ihre Angaben werden vertraulich behandelt.
            </p>
        </td>
    </tr>
</table>
<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#5a6b78;">
    Bei Fragen antworten Sie einfach auf diese E-Mail — oder besuchen Sie nachfolge-experten.ch.
</p>
HTML,
                'body_text' => "Guten Tag {{owner_name}},\n\nIhre Anfrage wurde an {{expert_company_name}} weitergeleitet.\nDie Beratung meldet sich direkt bei Ihnen.",
            ],
            [
                'key' => 'expert_registration_welcome',
                'subject' => 'Registrierung bei nachfolge-experten.ch erhalten',
                'body_html' => <<<'HTML'
<p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#0a2a42;">
    Guten Tag <strong style="color:#0E4971;">{{contact_name}}</strong>,
</p>
<p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#0a2a42;">
    vielen Dank für Ihre Registrierung als M&amp;A-Experte für
    <strong style="color:#0E4971;">{{company_name}}</strong>.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 18px;border:1px solid #c5d6e4;">
    <tr>
        <td style="padding:14px 16px;border-bottom:1px solid #e0ebf3;background-color:#f7fafc;">
            <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;color:#009DE1;">01 · Prüfung</p>
            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#0a2a42;">Jedes Profil wird manuell geprüft (in der Regel 24–48 Stunden).</p>
        </td>
    </tr>
    <tr>
        <td style="padding:14px 16px;border-bottom:1px solid #e0ebf3;background-color:#ffffff;">
            <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;color:#009DE1;">02 · Freigabe</p>
            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#0a2a42;">Nach Freischaltung erhalten Sie eine Bestätigung per E-Mail.</p>
        </td>
    </tr>
    <tr>
        <td style="padding:14px 16px;background-color:#f7fafc;">
            <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;color:#009DE1;">03 · Sichtbarkeit</p>
            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#0a2a42;">Ihr Eintrag wird regional im Verzeichnis und auf der Karte sichtbar. Die Basis-Listung ist kostenlos.</p>
        </td>
    </tr>
</table>
HTML,
                'body_text' => "Registrierung für {{company_name}} erhalten.\nManuelle Prüfung innerhalb von 24–48 Stunden.\nDie Basis-Listung ist kostenlos.",
            ],
            [
                'key' => 'password_reset',
                'subject' => 'Passwort zurücksetzen — nachfolge-experten.ch',
                'body_html' => <<<'HTML'
<p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#0a2a42;">
    Guten Tag <strong style="color:#0E4971;">{{name}}</strong>,
</p>
<p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#0a2a42;">
    Sie haben angefordert, Ihr Passwort für <strong style="color:#0E4971;">nachfolge-experten.ch</strong> zurückzusetzen.
    Nutzen Sie den Button in dieser E-Mail, um ein neues Passwort festzulegen.
</p>
<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#5a6b78;">
    Der Link ist {{expires_minutes}} Minuten gültig. Wenn Sie diese Anfrage nicht gestellt haben, ignorieren Sie diese E-Mail.
</p>
HTML,
                'body_text' => "Guten Tag {{name}},\n\nPasswort zurücksetzen: {{reset_url}}\n\nGültig {{expires_minutes}} Minuten.",
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(['key' => $template['key']], $template);
        }
    }
}
