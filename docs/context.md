# context.md — Nachfolge-Experten.ch

## Project Overview
Professional Swiss M&A / Unternehmensnachfolge expert directory and lead-generation platform for Switzerland. The MVP uses a serious, professional white/grey visual direction matching the client's branding.

## Confirmed MVP Requirements

### Public Directory
- Swiss M&A / Unternehmensnachfolge specialists.
- Experts may have multiple offices/locations.
- Organized by the 26 Swiss cantons.
- Search via Google Maps and canton/region listing.
- Additional confirmed filter: Buy / Sell.
- Public profile shows only: Name, Address, Place, Logo.
- Public users contact experts through the platform contact form rather than simply seeing direct contact details.

### Expert Registration & Accounts
- Expert self-registration is supported.
- Admin must approve/activate every new expert before the profile goes live.
- Admin receives an email for new registrations.
- Approved expert receives a welcome/activation email.
- Rejected expert receives an email explaining the rejection.
- Expert has a private dashboard and can manage profile data.
- Expert profile fields include phone, email, website, company description, services/specializations, logo, and the final registration-form fields.
- Multiple offices/locations are supported.

### Leads
- Contact form creates a lead for the selected expert.
- Lead is emailed to the expert and stored in the expert dashboard.
- Admin can also view/manage leads.
- Expert can manage lead status, including New, Contacted, In Progress, Closed.
- Customer receives a confirmation email.
- Lead data includes name, phone/email, message, and optional company/industry and region/canton information.
- Client-provided email templates should be used as the basis for the actual transactional emails.

### Blogs / Articles
- Experts can submit articles directly.
- Expert-submitted articles become public immediately.
- Admin can block/delete articles.
- Blocked articles immediately disappear from the public website.
- Categories:
  1. Grundlagen und strategische Optionen
  2. Unternehmensbewertung
  3. Vorbereitung
  4. Psychologie und Kommunikation
  5. Recht, Steuern & Risiken
  6. Praxisberichte

### Crawling
- Crawl expert websites daily.
- Crawled articles are automatically published.
- Public source attribution is shown.
- Duplicate articles are automatically detected and skipped.
- Images are copied/imported to the platform.
- AI may process/categorize imported content.

### AI
Use multiple AI providers with fallback:
- Google Gemini
- Groq
- OpenAI
- Anthropic

AI-assisted expert data changes:
- Normally may be applied immediately.
- Admin can edit the suggested value before applying it.

AI must remain modular so provider failure can trigger fallback.

### Maps
- Google Maps is the map provider.

### Promotion / Paid Positions — INCLUDED IN MVP
Promotion is now explicitly part of the MVP.

- Up to 3 paid/promoted positions per canton/region.
- Expert selects an available position.
- Expert chooses the desired position when purchasing.
- Promotion duration is monthly.
- If all positions are occupied, expert can join a waiting list.
- When promotion expires, the expert automatically becomes a normal/non-promoted listing.

### Promotion Payment
The client has clarified that this is a very small market and expects no more than roughly 10 invoices per year in the best case.

Therefore MVP should use **manual invoicing**, not an online payment gateway:
- No Stripe/PayPal/TWINT requirement for MVP.
- Expert can request/select a promotion position.
- Admin records/manages the invoice.
- Admin records payment status.
- Admin activates the promotion after the manual invoice/payment process is completed.
- Architecture should remain extensible for online payment later.

### Admin Configuration
Admin needs a proper configuration/settings area instead of putting every operational integration only in `.env`.

Admin-configurable settings should include:
- Google Maps API
- Gemini API
- Groq API
- OpenAI API
- Anthropic API
- AI fallback configuration/order
- Resend.com email/API settings
- SMTP/email settings where required
- General application settings
- Promotion settings
- Other operational integrations

Security:
- Sensitive credentials must be protected, masked, and encrypted where appropriate.
- `.env` may still hold bootstrap/security-critical deployment settings.
- Normal operational integration settings should be manageable through the Admin UI.
- Never place client passwords or hosting/database secrets in this context file.

### Language & Design
- MVP: German only.
- Professional, serious, business-oriented.
- White/grey direction matching the logo.
- Avoid overly fancy/playful UI.

## Roles
- Admin
- M&A Expert

No additional roles are currently required.

## Admin Capabilities
Admin can:
- Approve/activate/reject experts
- Enter rejection reason
- Manage experts and offices
- View/manage leads
- Manage articles/blogs
- Block/delete articles
- Review/manage AI-assisted changes
- Manage crawled/imported content
- Configure Maps and AI providers
- Configure Resend/email
- Manage general settings
- Manage promoted positions
- Manage promotion invoices/payment status
- Manage promotion waiting list

## Client Email Templates Reviewed
Three uploaded documents were reviewed:

1. Lead notification to M&A expert:
   - Contains owner name, phone, email, company/industry when provided, region/canton, and message.
   - Asks the expert to contact the lead directly.
   - States that the basic listing/lead service is free.
   Source: `e-mail mit potentiellen Lead an M&A Berater.docx`

2. Customer confirmation:
   - Confirms successful receipt.
   - Says the customer's contact details/message are forwarded to the selected M&A firm.
   - Explains direct follow-up and confidentiality.
   Source: `Welcome e-mail für Customer.docx`

3. M&A registration welcome:
   - Confirms registration receipt.
   - Says each new profile is manually reviewed.
   - States 24–48 hour review, then activation and regional visibility.
   - States the basic listing is free.
   Source: `Welcome e-mail für Registration von M&A Firmen.docx`

## Initial Data
Original scope references an initial expert database/import source:
- `Basis telsearch 2019 06 16.xlsx`
- AI-assisted import/crawl can be used.
- Experts can subsequently complete/update their data.

## Still Undefined
- Exact database schema and field validation.
- Exact Buy/Sell semantics and whether an expert can select both.
- Exact definition of region versus canton.
- Exact promotion UI and invoice workflow.
- Whether promoted positions are shared or separated by Buy/Sell.
- Waiting-list ordering and notification rules.
- Admin settings permissions and audit logging.
- Exact AI fallback priority/order.
- AI prompts and approval UI.
- Exact crawling source-detection rules.
- Exact duplicate-detection algorithm.
- Article editing/attribution details.
- Map UX and marker behaviour.
- Exact lead statuses and transition rules.
- Exact registration form fields and validation.

## Important Missing Source
The previously referenced document `Datenstruktur Anmeldung M&A Form.docx` was not among the three newly uploaded documents reviewed in this turn. It should be reviewed before finalizing the registration database schema and registration fields.

## Implementation Principles
- Build from confirmed requirements only.
- Do not turn undefined questions into assumptions.
- Keep integrations modular and configurable.
- Keep promotion architecture extensible for future online payments.
- Keep AI providers modular with fallback support.
- Keep German language support throughout MVP.
