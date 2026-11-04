<?php

namespace App\Support;

final class SettingKey
{
    public const GROUP_GENERAL = 'general';

    public const GROUP_AI = 'ai';

    public const GROUP_EMAIL = 'email';

    public const GROUP_MAPS = 'maps';

    public const GROUP_PROMOTION = 'promotion';

    public const GROUP_FOOTER = 'footer';

    public const GROUP_INVOICE = 'invoice';

    public const APP_NAME = 'app.name';

    public const FOOTER_CONTACT_LINES = 'footer.contact_lines';

    public const FOOTER_CONTACT_EMAIL = 'footer.contact_email';

    public const GOOGLE_MAPS_API_KEY = 'maps.google_api_key';

    public const AI_PROVIDER_ORDER = 'ai.provider_order';

    public const AI_GEMINI_API_KEY = 'ai.gemini_api_key';

    public const AI_GEMINI_MODEL = 'ai.gemini_model';

    public const AI_GROQ_API_KEY = 'ai.groq_api_key';

    public const AI_GROQ_MODEL = 'ai.groq_model';

    public const AI_OPENAI_API_KEY = 'ai.openai_api_key';

    public const AI_OPENAI_MODEL = 'ai.openai_model';

    public const AI_ANTHROPIC_API_KEY = 'ai.anthropic_api_key';

    public const AI_ANTHROPIC_MODEL = 'ai.anthropic_model';

    public const RESEND_API_KEY = 'email.resend_api_key';

    public const RESEND_FROM_EMAIL = 'email.resend_from_email';

    public const RESEND_FROM_NAME = 'email.resend_from_name';

    public const PROMOTION_MONTHLY_PRICE_CHF = 'promotion.monthly_price_chf';

    public const ADMIN_NOTIFICATION_EMAIL = 'email.admin_notification_email';

    public const RESEND_REPLY_TO = 'email.reply_to';

    public const INVOICE_SENDER_NAME = 'invoice.sender_name';

    public const INVOICE_SENDER_COMPANY = 'invoice.sender_company';

    public const INVOICE_SENDER_ADDRESS = 'invoice.sender_address';

    public const INVOICE_BANK_NAME = 'invoice.bank_name';

    public const INVOICE_BANK_IBAN = 'invoice.bank_iban';

    public const INVOICE_BANK_BIC = 'invoice.bank_bic';

    public const INVOICE_PAYMENT_HINT = 'invoice.payment_hint';

    public const INVOICE_DEFAULT_VAT_RATE = 'invoice.default_vat_rate';

    public const INVOICE_VAT_UID = 'invoice.vat_uid';
}
