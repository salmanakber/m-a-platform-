# Scope of Work --- nachfolge-experten.ch

**Project type:** PHP-based web application\
**Platform:** nachfolge-experten.ch\
**Market:** Switzerland\
**MVP language:** German\
**Primary roles:** Admin, M&A Expert\
**Design direction:** Professional, serious, white/grey, aligned with
the existing logo/brand

------------------------------------------------------------------------

# 1. Executive Summary

nachfolge-experten.ch is a Swiss M&A / Unternehmensnachfolge platform
whose primary purpose is to make M&A experts discoverable by Swiss
company owners and buyers/sellers while generating qualified contact
requests for the listed experts.

The platform combines:

1.  A public Swiss M&A expert directory.
2.  Canton/region and map-based discovery.
3.  Public expert profiles with controlled visibility.
4.  A lead/contact-request system.
5.  Private expert dashboards.
6.  Admin management and approval workflows.
7.  Expert-authored articles/blogs.
8.  Automated crawling/importing of expert articles.
9.  AI-assisted data extraction and content processing.
10. Paid promoted positions.
11. Manual invoice management for promotions.
12. Central Admin configuration for external services and APIs.

The original scope describes the platform as a "who and blogs" platform
and explicitly calls for an overview of M&A specialists in Switzerland,
organized by the 26 cantons, searchable through a Swiss map or canton
lists, with names/logos but without publicly exposing direct contact
information. Users instead contact experts through a simple form.
fileciteturn3file0

------------------------------------------------------------------------

# 2. Source of Requirements

This scope is based on:

-   Original `Scope of Nachfolgeexperten` document.
-   `Questions Salman` clarification document.
-   Client-provided lead notification email.
-   Client-provided customer confirmation email.
-   Client-provided M&A registration welcome email.
-   Subsequent confirmed decisions supplied during project
    clarification.

The original scope also references additional source documents such as
the registration data structure, legal documents, homepage structure,
initial expert database, logo assets, and email templates. Where those
documents were not available for detailed review, this scope does not
invent their contents.

------------------------------------------------------------------------

# 3. MVP Scope

## 3.1 Public Website

The public website should provide a professional, trustworthy Swiss
business-directory experience.

### Main areas

-   Homepage
-   Expert directory
-   Swiss map
-   Canton/region navigation
-   Expert profile pages
-   Contact/lead forms
-   Blog/article area
-   Blog category pages
-   Legal pages
-   Registration entry point for M&A experts

The exact homepage structure should follow the client's referenced
homepage-structure document when available.

------------------------------------------------------------------------

# 4. M&A Expert Directory

## 4.1 Expert records

Each expert/company record should support:

-   Company/expert name
-   Logo
-   Website
-   Email
-   Telephone
-   Company description
-   Services/specializations
-   Multiple offices
-   Address
-   City/place
-   Canton
-   Region where applicable
-   Buy / Sell classification
-   Registration/account status
-   Public visibility status
-   Creation/update timestamps

Experts may have multiple offices. Each office should be represented as
a separate location associated with the same expert/company.

## 4.2 Public profile

The confirmed public profile is intentionally limited.

Publicly displayed:

-   Name
-   Address
-   Place
-   Logo

The expert may maintain additional private information, but that
information is not automatically exposed publicly.

This distinction is important: the database may contain significantly
more information than the public profile.

------------------------------------------------------------------------

# 5. Swiss Regional Structure

The platform should support the 26 Swiss cantons.

Each expert/location should be associated with a canton.

Recommended data structure:

-   Canton
-   Canton code
-   Region metadata
-   Office/location
-   Latitude
-   Longitude

The system should be designed so that a future regional grouping can
exist without changing the underlying expert model.

------------------------------------------------------------------------

# 6. Search & Discovery

## 6.1 Search methods

The original scope defines two primary discovery methods:

1.  Google Map
2.  Canton/list navigation

Both must be available.

## 6.2 Filters

Confirmed MVP filter:

-   Buy / Sell

The original scope also identifies useful lead information such as
company type and employee count. These fields should therefore be
supported in the lead form/data model even if their use as public
directory filters is configurable.

Potential future filters should not be treated as mandatory until
confirmed.

## 6.3 Map

Google Maps is the confirmed provider.

Recommended implementation:

-   Display expert office markers.
-   Markers should link to the appropriate public profile.
-   Multiple offices must create multiple map locations.
-   Map and list results should remain synchronized where practical.
-   Paid promoted positions should have a distinct but professional
    visual treatment.

------------------------------------------------------------------------

# 7. Public Expert Profile

Each public profile should have:

-   Expert/company name
-   Logo
-   Office/address
-   Place
-   Canton/region
-   Contact/request CTA
-   Relevant public blog/article links

Direct phone/email contact information should not be publicly exposed
merely because it exists in the database.

The primary conversion action is the platform contact form.

------------------------------------------------------------------------

# 8. Lead Generation System

## 8.1 Contact flow

A visitor selects an expert and submits a request.

The request is:

1.  Validated.
2.  Stored in the database.
3.  Associated with the selected expert.
4.  Visible to Admin.
5.  Visible in the expert dashboard.
6.  Emailed to the expert.
7.  Confirmed to the submitting customer by email.

## 8.2 Lead data

Confirmed/expected fields include:

-   Owner/contact name
-   Email
-   Telephone
-   Message
-   Company/firm name where supplied
-   Industry where supplied
-   Canton/region
-   Buy/Sell context where applicable
-   Selected expert
-   Submission timestamp

The client email template specifically contains owner name, telephone,
email, company/industry, region/canton and free-text message.
fileciteturn3file1

## 8.3 Lead management

Experts can view and manage their received leads.

Recommended MVP status model:

-   New
-   Contacted
-   In Progress
-   Closed

Admin can view all leads.

Admin should be able to search/filter leads by:

-   Expert
-   Status
-   Canton/region
-   Date
-   Buy/Sell
-   Contact information

Exact status transition rules can remain configurable.

------------------------------------------------------------------------

# 9. Transactional Email System

The project must include transactional email infrastructure.

### Customer confirmation

After a lead is submitted, the customer receives a confirmation that:

-   The request was received.
-   Their information was forwarded to the selected M&A company.
-   The selected expert is identified.
-   The expert is expected to contact them.
-   Confidentiality/discretion is communicated.

This follows the supplied customer email template. fileciteturn3file5

### Expert lead email

The selected expert receives the lead details and is instructed to
contact the customer directly. fileciteturn3file1

### Registration email

A newly registered expert receives confirmation that their profile is
being reviewed.

The supplied template states that registration is manually reviewed,
normally within 24--48 hours, followed by activation and regional
visibility after approval. fileciteturn3file2

### Email provider

Resend.com should be supported as the primary email delivery
integration.

------------------------------------------------------------------------

# 10. Expert Registration

## 10.1 Registration workflow

1.  Expert opens registration form.
2.  Expert submits company/profile information.
3.  System validates data.
4.  Account is created in pending state.
5.  Admin receives notification.
6.  Admin reviews registration.
7.  Admin either:
    -   Approves/activates, or
    -   Rejects with a reason.
8.  Expert receives the appropriate email.
9.  Approved profile becomes publicly searchable.

The supplied registration email confirms manual review before final
publication. fileciteturn3file2

## 10.2 Expert dashboard

After activation, the expert can manage:

-   Profile information
-   Offices
-   Logo
-   Contact information
-   Website
-   Company description
-   Services/specializations
-   Buy/Sell information
-   Blog/article content
-   Leads
-   Lead statuses
-   Promotion requests
-   Account/security settings

The original clarification explicitly states that experts may modify
everything, even though only selected fields are shown publicly.
fileciteturn3file3

------------------------------------------------------------------------

# 11. Admin Panel

The Admin panel is a central requirement, not merely a database back
office.

## 11.1 Dashboard

Recommended dashboard widgets:

-   Total experts
-   Pending registrations
-   Active experts
-   Leads today/month
-   Open leads
-   Articles
-   Crawled articles
-   Failed crawl jobs
-   Promotion positions
-   Pending promotion requests
-   Outstanding invoices
-   AI/API health

## 11.2 Expert management

Admin can:

-   Search experts
-   View/edit experts
-   Approve registrations
-   Reject registrations
-   Enter rejection reason
-   Activate/deactivate experts
-   Manage offices
-   Correct imported information
-   Control public visibility
-   Review source/import history

## 11.3 Lead management

Admin can:

-   View all leads
-   Filter leads
-   Open full lead details
-   See selected expert
-   Change status
-   Review timestamps
-   Track notification delivery
-   Export data if required

## 11.4 Article management

Admin can:

-   View all articles
-   Filter by expert/category/source
-   Edit
-   Block
-   Delete
-   Review crawled source
-   Review imported images
-   Manage article categories

Blocking an article must immediately remove it from the public website.

------------------------------------------------------------------------

# 12. Blog / Article Platform

The original scope requires a blog form for each M&A company and Admin
control to overview, block and erase blogs. fileciteturn3file0

## Categories

1.  Grundlagen und strategische Optionen
2.  Unternehmensbewertung
3.  Vorbereitung
4.  Psychologie und Kommunikation
5.  Recht, Steuern & Risiken
6.  Praxisberichte

## Expert-authored content

-   Expert creates article in dashboard.
-   Expert can publish directly.
-   Article becomes publicly visible.
-   Admin retains the right to block/delete.

## Article capabilities

Recommended:

-   Title
-   Slug
-   Excerpt
-   Body
-   Cover image
-   Inline images
-   Category
-   Author/expert
-   Publication date
-   Source URL if applicable
-   Source attribution
-   Status
-   Crawl metadata
-   SEO title/description

------------------------------------------------------------------------

# 13. Automated Website Crawling

The original requirement calls for articles to be crawled from expert
websites and copied to the platform, partly to generate platform
traffic. The clarification explicitly confirms copying rather than
simply linking. fileciteturn3file3

## Confirmed behaviour

-   Daily crawling.
-   Automatic publication of crawled articles.
-   Public source attribution.
-   Automatic duplicate detection.
-   Duplicate articles are skipped.
-   Article images are copied/imported.
-   AI can assist in processing.

## Recommended crawler architecture

Use a queue/job-based architecture:

1.  Select active experts with a crawlable website.
2.  Fetch website/RSS/sitemap where available.
3.  Discover candidate article URLs.
4.  Extract article content.
5.  Extract title/date/images/category.
6.  Normalize content.
7.  Calculate duplicate/fingerprint information.
8.  Process through AI.
9.  Import article/images.
10. Publish.
11. Store crawl result/log.

A failed crawl for one expert should not stop the entire daily crawl.

------------------------------------------------------------------------

# 14. AI Processing Layer

AI is a core supporting capability.

Confirmed providers:

-   Google Gemini
-   Groq
-   OpenAI
-   Anthropic

The application should use a provider abstraction rather than hard-code
one provider throughout the codebase.

## AI use cases

-   Extracting expert/company information from websites.
-   Suggesting address changes.
-   Extracting article content.
-   Categorizing articles.
-   Cleaning/normalizing content.
-   Identifying relevant article metadata.
-   Assisting database imports.

## Fallback architecture

Recommended:

`AI Manager → Provider Adapter → Primary Provider → Fallback Provider → Result`

Each provider should have its own adapter.

Provider failures should be logged without exposing API credentials.

## Admin approval

AI-suggested expert data can normally be applied immediately, but Admin
must be able to edit a suggested value before applying it.

Recommended audit information:

-   Original value
-   AI suggestion
-   Provider
-   Timestamp
-   Applied value
-   Admin/user who approved/edited it

------------------------------------------------------------------------

# 15. Initial Expert Database Import

The original scope references an initial database:

`Basis telsearch 2019 06 16.xlsx`

and explicitly proposes AI-assisted processing.

The import system should support:

-   Spreadsheet import
-   Column mapping
-   Validation
-   Duplicate detection
-   Address normalization
-   Canton matching
-   Website extraction
-   Logo discovery where possible
-   AI enrichment
-   Review of uncertain records
-   Bulk approval/activation
-   Import logs

Imported experts should not automatically become publicly visible unless
they satisfy the project's activation rules.

------------------------------------------------------------------------

# 16. Promotion / Monetization --- MVP

Promotion is now confirmed as part of the MVP.

## Business model

-   Three promoted positions per canton/region.
-   Expert chooses an available position.
-   Promotion is monthly.
-   If all positions are occupied, an expert can join a waiting list.
-   When promotion expires, the listing automatically returns to
    normal/non-promoted status.

## Payment model

The client has clarified that this is a very small market and expects
approximately no more than 10 invoices per year in the best case.

Therefore:

**MVP uses manual invoicing.**

No online payment gateway is required.

The system should support:

-   Promotion request
-   Selected position
-   Monthly period
-   Invoice record
-   Invoice number
-   Invoice date
-   Amount
-   Payment status
-   Activation date
-   Expiration date
-   Admin notes
-   Waiting-list position
-   Promotion status

Recommended promotion states:

-   Requested
-   Invoice pending
-   Payment pending
-   Active
-   Expired
-   Cancelled
-   Waiting list

Online payment can be added later without redesigning the promotion
domain model.

------------------------------------------------------------------------

# 17. Admin Configuration System

A major architectural requirement is that operational configuration
should not simply be hard-coded into `.env`.

Admin should have a protected Settings area.

## Integration settings

### Maps

-   Google Maps API key
-   Map configuration
-   Default map settings

### AI

-   Gemini API
-   Groq API
-   OpenAI API
-   Anthropic API
-   Provider enabled/disabled
-   Fallback priority

### Email

-   Resend API key
-   From name
-   From address
-   Reply-to
-   SMTP settings where needed
-   Email testing function

### Application

-   Site name
-   Contact email
-   Default settings
-   Article/crawl settings
-   Promotion settings

## Security

Sensitive credentials must:

-   Be encrypted at rest where practical.
-   Be masked in the UI.
-   Never appear in normal logs.
-   Never be returned unnecessarily to the browser.
-   Be accessible only to authorized Admins.

Environment variables may still be used for bootstrap secrets,
encryption keys, deployment configuration and other
infrastructure-critical settings.

------------------------------------------------------------------------

# 18. Authentication & Authorization

## Admin

Admin authentication should include:

-   Secure login
-   Password hashing
-   Session management
-   Password reset
-   Optional 2FA as an extensibility point
-   Authorization middleware

## Expert

Expert authentication should include:

-   Login
-   Password reset
-   Session protection
-   Account activation state
-   Account disablement
-   Authorization to access only their own data

Experts must never be able to access another expert's leads, settings,
articles, invoices or private information.

------------------------------------------------------------------------

# 19. Database Architecture

Recommended relational database structure:

### Core

-   users
-   roles
-   experts
-   expert_offices
-   expert_services
-   cantons
-   regions
-   expert_categories / capabilities

### Leads

-   leads
-   lead_statuses
-   lead_status_history
-   lead_notifications

### Articles

-   articles
-   article_categories
-   article_images
-   article_sources
-   article_crawl_records

### Crawling

-   crawl_sources
-   crawl_jobs
-   crawl_results
-   crawl_errors

### AI

-   ai_providers
-   ai_runs
-   ai_suggestions
-   ai_change_history

### Promotions

-   promotion_positions
-   promotions
-   promotion_waitlist
-   invoices
-   invoice_items
-   payment_records

### System

-   settings
-   setting_groups
-   audit_logs
-   notifications
-   media/files

Foreign keys, indexes, unique constraints and soft-delete strategy
should be designed before implementation.

------------------------------------------------------------------------

# 20. Media Management

The application needs centralized media handling for:

-   Expert logos
-   Article cover images
-   Article inline images
-   Imported images

Recommended controls:

-   MIME validation
-   File-size limits
-   Safe filenames
-   Image optimization
-   Thumbnail generation
-   Storage abstraction
-   Protection against executable uploads

Crawled images should be copied into controlled application storage
rather than hotlinked.

------------------------------------------------------------------------

# 21. Background Jobs / Scheduler

The PHP application should not execute heavy crawling and AI tasks
inside normal HTTP requests.

Use background jobs/queues for:

-   Daily crawling
-   AI processing
-   Image importing
-   Email processing
-   Duplicate detection
-   Data enrichment
-   Scheduled promotion expiration
-   Waiting-list notifications
-   Cleanup tasks

A scheduler/cron should trigger queued jobs.

------------------------------------------------------------------------

# 22. Audit Logging

Admin actions should be auditable.

Recommended audit events:

-   Expert approved
-   Expert rejected
-   Expert edited
-   Expert activated/deactivated
-   Lead status changed
-   Article blocked/deleted
-   AI suggestion accepted
-   AI suggestion edited
-   API configuration changed
-   Promotion activated
-   Promotion expired
-   Invoice status changed

Audit logs should include:

-   Actor
-   Action
-   Entity
-   Entity ID
-   Timestamp
-   Relevant before/after values where appropriate

------------------------------------------------------------------------

# 23. Security Requirements

The application handles personal contact information and therefore
requires strong security.

Minimum requirements:

-   Password hashing using modern password hashing.
-   CSRF protection.
-   XSS protection/output escaping.
-   SQL injection prevention through parameterized queries/ORM.
-   Authorization checks on every private resource.
-   Secure sessions.
-   Rate limiting on authentication and public forms.
-   Spam/bot protection on lead and registration forms.
-   File-upload validation.
-   Secure API credential storage.
-   No credentials in source control.
-   Secure production error handling.
-   Security headers where appropriate.
-   Database backups.
-   Logging and monitoring.

The platform should also respect the legal/privacy requirements supplied
by the client, including the referenced terms, privacy policy and
Impressum documents.

This scope does not independently interpret the client's legal
documents.

------------------------------------------------------------------------

# 24. SEO

Because the business objective includes traffic from articles, SEO
should be considered part of the platform architecture.

Recommended:

-   Clean URLs.
-   SEO-friendly expert pages.
-   SEO-friendly article URLs.
-   Canonical URLs.
-   Meta title/description.
-   Open Graph metadata.
-   XML sitemap.
-   Robots.txt.
-   Structured data where appropriate.
-   Internal linking between experts, cantons and articles.
-   Source attribution for imported articles.

------------------------------------------------------------------------

# 25. Performance

The application should be designed for efficient directory browsing and
content delivery.

Recommended:

-   Database indexes.
-   Pagination.
-   Cached canton/region data.
-   Cached public directory queries where appropriate.
-   Optimized images.
-   Lazy loading.
-   Queue-based crawling/AI.
-   Efficient map marker loading.
-   Background processing for heavy work.

The system should not assume a huge market, but architecture should
still be production-quality and scalable.

------------------------------------------------------------------------

# 26. PHP Application Architecture

The project is explicitly PHP-based.

Recommended architecture:

-   Modern supported PHP version.
-   MVC-style application structure.
-   Clear separation between:
    -   Controllers
    -   Services
    -   Domain/business logic
    -   Repositories/data access
    -   Jobs/queues
    -   Integrations
    -   Views
    -   API endpoints
-   Relational database such as MySQL/MariaDB.
-   Database migrations.
-   Environment-specific configuration.
-   Automated dependency management with Composer.

A mature PHP framework such as Laravel would be a suitable
implementation choice, but the original client documents specify PHP
rather than a particular framework. Therefore the framework remains an
implementation decision unless separately confirmed.

------------------------------------------------------------------------

# 27. Recommended Service Boundaries

The codebase should separate major business services:

-   ExpertService
-   OfficeService
-   LeadService
-   ArticleService
-   CrawlService
-   AIService
-   PromotionService
-   InvoiceService
-   NotificationService
-   MediaService
-   SettingsService
-   SearchService
-   AuditService

External services should be behind adapters/interfaces.

This makes it possible to replace:

-   AI provider
-   Email provider
-   Map provider
-   Storage provider
-   Payment system in a future version

without rewriting the business logic.

------------------------------------------------------------------------

# 28. Email Templates

The supplied client templates should become editable application
templates rather than hard-coded strings.

Template types should include:

-   New expert registration
-   Expert registration approved
-   Expert registration rejected
-   New lead to expert
-   Lead confirmation to customer
-   Promotion/invoice notification
-   Promotion activated
-   Promotion expired
-   Waiting-list notification
-   Password reset

Admin may be able to edit template content while protected system
placeholders remain validated.

------------------------------------------------------------------------

# 29. Legal / Compliance Pages

The original scope explicitly references:

-   Allgemeine Geschäftsbedingungen
-   Datenschutzerklärung
-   Impressum

These pages should be implemented as managed public pages.

The actual legal text should come from the client's supplied legal
documents and should not be invented by the developer.

------------------------------------------------------------------------

# 30. Initial Content / Data Migration

Implementation should include a migration/import phase:

1.  Prepare database.
2.  Import canton master data.
3.  Import initial expert dataset.
4.  Normalize records.
5.  Detect duplicates.
6.  Enrich using AI where permitted.
7.  Validate websites/addresses.
8.  Assign offices/cantons.
9.  Review uncertain records.
10. Activate approved records.

Import jobs should be repeatable without creating duplicates.

------------------------------------------------------------------------

# 31. Admin Settings vs Environment Variables

This distinction is important.

### `.env` / infrastructure configuration

Keep items such as:

-   Database connection.
-   Application encryption key.
-   Deployment/runtime secrets.
-   Queue infrastructure.
-   Server-level credentials.

### Admin UI configuration

Manage items such as:

-   Google Maps API key.
-   AI provider credentials.
-   Resend credentials.
-   AI fallback order.
-   Crawl frequency/settings.
-   Promotion rules.
-   Email sender configuration.
-   General operational settings.

Sensitive Admin settings should be encrypted and protected.

------------------------------------------------------------------------

# 32. Testing Requirements

The MVP should include:

## Unit tests

For:

-   Lead creation
-   Lead status changes
-   Expert approval
-   Promotion expiration
-   Invoice state
-   Duplicate detection
-   AI provider fallback
-   Permission checks

## Integration tests

For:

-   Registration flow
-   Lead submission/email workflow
-   Expert dashboard
-   Admin approval
-   Google Maps integration
-   Resend integration
-   AI provider integration
-   Crawl/import workflow

## Security testing

Test:

-   Unauthorized expert access
-   Cross-expert data access
-   Admin-only endpoints
-   CSRF
-   File uploads
-   Authentication rate limits
-   API credential exposure

------------------------------------------------------------------------

# 33. Deployment

Production deployment should include:

-   PHP runtime
-   Database
-   Web server
-   SSL
-   Scheduled jobs/cron
-   Queue worker
-   Storage
-   Email integration
-   Monitoring/logging
-   Backup strategy

The original scope references third-party domain and hosting accounts.
Those credentials must never be committed to source code or this scope
document.

------------------------------------------------------------------------

# 34. MVP Acceptance Criteria

The MVP should be considered functionally complete when:

### Directory

-   Experts can be displayed by canton/region.
-   Google Map works.
-   Multiple offices work.
-   Buy/Sell filter works.
-   Public profiles expose only intended public fields.

### Registration

-   Expert can register.
-   Admin receives notification.
-   Admin approves/rejects.
-   Rejection reason can be sent.
-   Approved expert can log in.
-   Expert can edit their information.

### Leads

-   Customer can submit a request.
-   Request is stored.
-   Expert receives email.
-   Expert sees request in dashboard.
-   Admin sees request.
-   Customer receives confirmation.
-   Expert can change lead status.

### Articles

-   Expert can publish articles.
-   Admin can manage them.
-   Blocked articles disappear publicly.
-   Daily crawling works.
-   Source attribution works.
-   Duplicate detection works.
-   Images are imported.

### AI

-   At least the configured AI providers are supported.
-   Fallback logic works.
-   AI-assisted changes can be reviewed/edited by Admin.

### Promotion

-   Three promoted positions can be configured per canton/region.
-   Expert can request/select a position.
-   Monthly promotion periods work.
-   Waiting list works.
-   Manual invoice can be recorded.
-   Admin can activate promotion.
-   Expired promotion returns to normal listing.

### Configuration

-   Admin can configure Maps.
-   Admin can configure AI providers.
-   Admin can configure Resend/email.
-   Secrets are protected.

------------------------------------------------------------------------

# 35. Future Extensions --- Not Required to Block MVP

The architecture should allow future additions such as:

-   Online payments.
-   Stripe/TWINT/etc.
-   Additional languages.
-   Advanced expert matching.
-   More sophisticated regional segmentation.
-   Lead routing/multi-expert distribution.
-   Expert subscription packages.
-   Analytics dashboards.
-   Paid article promotion.
-   Advanced SEO automation.
-   Additional AI providers.
-   Automated invoice generation.
-   Customer accounts.
-   Advanced CRM functionality.

These are architectural considerations, not MVP acceptance requirements
unless explicitly added later.

------------------------------------------------------------------------

# 36. Final Scope Boundary

The MVP is a **professional PHP-based Swiss M&A expert directory and
lead platform**, not a full M&A CRM.

Its central workflow is:

**Swiss entrepreneur / buyer / seller → discover expert → submit
confidential request → expert receives lead → expert manages lead →
platform records the interaction**

Supporting workflows are:

**Expert registration → Admin review → approval → public listing**

**Expert → article creation → public publication**

**Expert website → daily crawl → AI processing → duplicate detection →
article import → public publication**

**Expert → promoted position request → manual invoice → Admin activation
→ monthly promotion → automatic expiration**

The implementation should prioritize reliability, security, clean
administration, modular integrations, and a professional Swiss
business-directory experience.
