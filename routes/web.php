<?php

use App\Http\Controllers\Admin\AISuggestionController as AdminAISuggestionController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\CrawlController as AdminCrawlController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmailTemplateController as AdminEmailTemplateController;
use App\Http\Controllers\Admin\ExpertController as AdminExpertController;
use App\Http\Controllers\Admin\ExpertOfficeController as AdminExpertOfficeController;
use App\Http\Controllers\Admin\ImportController as AdminImportController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\RankingController as AdminRankingController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Expert\ArticleController as ExpertArticleController;
use App\Http\Controllers\Expert\CrawlController as ExpertCrawlController;
use App\Http\Controllers\Expert\DashboardController as ExpertDashboardController;
use App\Http\Controllers\Expert\EventController as ExpertEventController;
use App\Http\Controllers\Expert\LeadController as ExpertLeadController;
use App\Http\Controllers\Expert\OfficeController as ExpertOfficeController;
use App\Http\Controllers\Expert\ProfileController as ExpertProfileController;
use App\Http\Controllers\Expert\PromotionController as ExpertPromotionController;
use App\Http\Controllers\PublicSite\ArticleController as PublicArticleController;
use App\Http\Controllers\PublicSite\CantonController;
use App\Http\Controllers\PublicSite\DirectoryController;
use App\Http\Controllers\PublicSite\EventController as PublicEventController;
use App\Http\Controllers\PublicSite\ExpertProfileController as PublicExpertProfileController;
use App\Http\Controllers\PublicSite\HomeController;
use App\Http\Controllers\PublicSite\LeadController as PublicLeadController;
use App\Http\Controllers\PublicSite\MapController;
use App\Http\Controllers\PublicSite\PageController as PublicPageController;
use App\Http\Controllers\PublicSite\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/experten', [DirectoryController::class, 'index'])->name('directory.index');
Route::get('/experten/{slug}', [PublicExpertProfileController::class, 'show'])->name('experts.show');

Route::get('/kanton/{code}', [CantonController::class, 'show'])->name('cantons.show');
Route::get('/kantone', [CantonController::class, 'index'])->name('cantons.index');

Route::get('/karte', [MapController::class, 'index'])->name('map.index');
Route::get('/karte/vorschlaege', [MapController::class, 'suggest'])->name('map.suggest');

Route::get('/blog', [PublicArticleController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [PublicArticleController::class, 'show'])->name('blog.show');

Route::get('/events', [PublicEventController::class, 'index'])->name('events.index');
Route::get('/events/{slug}', [PublicEventController::class, 'show'])->name('events.show');
Route::post('/events/{slug}/anmelden', [PublicEventController::class, 'register'])->name('events.register');

Route::get('/registrierung', [RegistrationController::class, 'create'])->name('registration.create');
Route::post('/registrierung', [RegistrationController::class, 'store'])->name('registration.store');
Route::get('/registrierung/danke', [RegistrationController::class, 'thanks'])->name('registration.thanks');

Route::post('/kontakt/{expertSlug}', [PublicLeadController::class, 'store'])->name('leads.store');
Route::get('/kontakt/{expertSlug}/danke', [PublicLeadController::class, 'thankYou'])->name('leads.thank-you');

Route::get('/impressum', [PublicPageController::class, 'legalImpressum'])->name('legal.impressum');
Route::get('/datenschutz', [PublicPageController::class, 'legalDatenschutz'])->name('legal.datenschutz');
Route::get('/agb', [PublicPageController::class, 'legalAgb'])->name('legal.agb');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    Route::get('/passwort-vergessen', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/passwort-vergessen', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/passwort-zuruecksetzen/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/passwort-zuruecksetzen', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/experten', [AdminExpertController::class, 'index'])->name('experts.index');
    Route::get('/experten/neu', [AdminExpertController::class, 'create'])->name('experts.create');
    Route::post('/experten', [AdminExpertController::class, 'store'])->name('experts.store');
    Route::post('/experten/bulk', [AdminExpertController::class, 'bulk'])->name('experts.bulk');
    Route::get('/experten/{expert}', [AdminExpertController::class, 'show'])->name('experts.show');
    Route::get('/experten/{expert}/bearbeiten', [AdminExpertController::class, 'edit'])->name('experts.edit');
    Route::put('/experten/{expert}', [AdminExpertController::class, 'update'])->name('experts.update');
    Route::put('/experten/{expert}/zugang', [AdminExpertController::class, 'updateCredentials'])->name('experts.credentials');
    Route::post('/experten/{expert}/passwort-reset', [AdminExpertController::class, 'sendPasswordReset'])->name('experts.password-reset');
    Route::post('/experten/{expert}/freigeben', [AdminExpertController::class, 'approve'])->name('experts.approve');
    Route::post('/experten/{expert}/ablehnen', [AdminExpertController::class, 'reject'])->name('experts.reject');
    Route::post('/experten/{expert}/deaktivieren', [AdminExpertController::class, 'deactivate'])->name('experts.deactivate');
    Route::post('/experten/{expert}/aktivieren', [AdminExpertController::class, 'activate'])->name('experts.activate');
    Route::delete('/experten/{expert}', [AdminExpertController::class, 'destroy'])->name('experts.destroy');

    Route::get('/experten/{expert}/standorte/vorschlaege', [AdminExpertOfficeController::class, 'suggest'])->name('experts.offices.suggest');
    Route::post('/experten/{expert}/standorte', [AdminExpertOfficeController::class, 'store'])->name('experts.offices.store');
    Route::put('/experten/{expert}/standorte/{office}', [AdminExpertOfficeController::class, 'update'])->name('experts.offices.update');
    Route::delete('/experten/{expert}/standorte/{office}', [AdminExpertOfficeController::class, 'destroy'])->name('experts.offices.destroy');

    Route::get('/anfragen', [AdminLeadController::class, 'index'])->name('leads.index');
    Route::patch('/anfragen/bulk-status', [AdminLeadController::class, 'bulkUpdateStatus'])->name('leads.bulk-status');
    Route::get('/anfragen/{lead}', [AdminLeadController::class, 'show'])->name('leads.show');
    Route::patch('/anfragen/{lead}/status', [AdminLeadController::class, 'updateStatus'])->name('leads.update-status');

    Route::get('/artikel', [AdminArticleController::class, 'index'])->name('articles.index');
    Route::post('/artikel/bulk', [AdminArticleController::class, 'bulk'])->name('articles.bulk');
    Route::get('/artikel/{article}/bearbeiten', [AdminArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/artikel/{article}', [AdminArticleController::class, 'update'])->name('articles.update');
    Route::post('/artikel/{article}/sperren', [AdminArticleController::class, 'block'])->name('articles.block');
    Route::post('/artikel/{article}/entsperren', [AdminArticleController::class, 'unblock'])->name('articles.unblock');
    Route::delete('/artikel/{article}', [AdminArticleController::class, 'destroy'])->name('articles.destroy');

    Route::get('/promotionen', [AdminPromotionController::class, 'index'])->name('promotions.index');
    Route::post('/promotionen/bulk', [AdminPromotionController::class, 'bulk'])->name('promotions.bulk');
    Route::post('/promotionen/{promotion}/aktivieren', [AdminPromotionController::class, 'activate'])->name('promotions.activate');
    Route::post('/promotionen/{promotion}/beenden', [AdminPromotionController::class, 'expire'])->name('promotions.expire');
    Route::post('/promotionen/{promotion}/stornieren', [AdminPromotionController::class, 'cancel'])->name('promotions.cancel');
    Route::post('/promotionen/{promotion}/rechnung', [AdminPromotionController::class, 'createInvoice'])->name('promotions.create-invoice');

    Route::get('/rankings', [AdminRankingController::class, 'index'])->name('rankings.index');
    Route::get('/rankings/liste', [AdminRankingController::class, 'editGroup'])->name('rankings.edit-group');
    Route::post('/rankings/liste', [AdminRankingController::class, 'saveGroup'])->name('rankings.save-group');
    Route::delete('/rankings/{ranking}', [AdminRankingController::class, 'destroy'])->name('rankings.destroy');
    // Legacy single-row URLs → group editor
    Route::get('/rankings/neu', fn (\Illuminate\Http\Request $r) => redirect()->route('admin.rankings.edit-group', ['scope' => $r->query('scope', 'national_top5')]))->name('rankings.create');
    Route::get('/rankings/{ranking}/bearbeiten', function (\App\Models\Ranking $ranking) {
        return redirect()->route('admin.rankings.edit-group', array_filter([
            'scope' => $ranking->scope,
            'canton_id' => $ranking->canton_id,
            'professional_group' => $ranking->professional_group,
        ]));
    })->name('rankings.edit');

    Route::get('/rechnungen', [AdminInvoiceController::class, 'index'])->name('invoices.index');
    Route::patch('/rechnungen/bulk', [AdminInvoiceController::class, 'bulk'])->name('invoices.bulk');
    Route::post('/rechnungen/{invoice}/email', [AdminInvoiceController::class, 'sendEmail'])->name('invoices.send-email');
    Route::patch('/rechnungen/{invoice}/zahlungsstatus', [AdminInvoiceController::class, 'updatePaymentStatus'])->name('invoices.update-payment-status');

    Route::get('/einstellungen', [AdminSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/einstellungen', [AdminSettingsController::class, 'update'])->name('settings.update');

    Route::get('/crawl', [AdminCrawlController::class, 'index'])->name('crawl.index');
    Route::post('/crawl/ausfuehren', [AdminCrawlController::class, 'runDaily'])->name('crawl.run');

    Route::get('/ki-vorschlaege', [AdminAISuggestionController::class, 'index'])->name('ai-suggestions.index');
    Route::post('/ki-vorschlaege/{aiSuggestion}/anwenden', [AdminAISuggestionController::class, 'apply'])->name('ai-suggestions.apply');
    Route::post('/ki-vorschlaege/{aiSuggestion}/ablehnen', [AdminAISuggestionController::class, 'reject'])->name('ai-suggestions.reject');

    Route::get('/seiten', [AdminPageController::class, 'index'])->name('pages.index');
    Route::get('/seiten/{page}/bearbeiten', [AdminPageController::class, 'edit'])->name('pages.edit');
    Route::put('/seiten/{page}', [AdminPageController::class, 'update'])->name('pages.update');

    Route::get('/e-mail-vorlagen', [AdminEmailTemplateController::class, 'index'])->name('email-templates.index');
    Route::get('/e-mail-vorlagen/{emailTemplate}/bearbeiten', [AdminEmailTemplateController::class, 'edit'])->name('email-templates.edit');
    Route::put('/e-mail-vorlagen/{emailTemplate}', [AdminEmailTemplateController::class, 'update'])->name('email-templates.update');
    Route::post('/e-mail-vorlagen/{emailTemplate}/vorschau', [AdminEmailTemplateController::class, 'preview'])->name('email-templates.preview');
    Route::post('/e-mail-vorlagen/{emailTemplate}/testmail', [AdminEmailTemplateController::class, 'sendTest'])->name('email-templates.test');

    Route::get('/import', [AdminImportController::class, 'index'])->name('import.index');
    Route::post('/import', [AdminImportController::class, 'store'])->name('import.store');
    Route::get('/import/template', [AdminImportController::class, 'template'])->name('import.template');
});

Route::prefix('expert')->name('expert.')->middleware(['auth', 'role:expert'])->group(function () {
    Route::get('/', [ExpertDashboardController::class, 'index'])->name('dashboard');

    Route::get('/profil', [ExpertProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ExpertProfileController::class, 'update'])->name('profile.update');

    Route::get('/crawl', [ExpertCrawlController::class, 'index'])->name('crawl.index');
    Route::put('/crawl', [ExpertCrawlController::class, 'update'])->name('crawl.update');
    Route::post('/crawl/starten', [ExpertCrawlController::class, 'run'])->name('crawl.run');
    Route::post('/crawl/process/{job}', [ExpertCrawlController::class, 'process'])->name('crawl.process');
    Route::get('/crawl/status/{job}', [ExpertCrawlController::class, 'status'])->name('crawl.status');

    Route::get('/standorte', [ExpertOfficeController::class, 'index'])->name('offices.index');
    Route::get('/standorte/vorschlaege', [ExpertOfficeController::class, 'suggest'])->name('offices.suggest');
    Route::post('/standorte', [ExpertOfficeController::class, 'store'])->name('offices.store');
    Route::put('/standorte/{office}', [ExpertOfficeController::class, 'update'])->name('offices.update');
    Route::delete('/standorte/{office}', [ExpertOfficeController::class, 'destroy'])->name('offices.destroy');

    Route::get('/anfragen', [ExpertLeadController::class, 'index'])->name('leads.index');
    Route::get('/anfragen/{lead}', [ExpertLeadController::class, 'show'])->name('leads.show');
    Route::patch('/anfragen/{lead}/status', [ExpertLeadController::class, 'updateStatus'])->name('leads.update-status');

    Route::get('/artikel', [ExpertArticleController::class, 'index'])->name('articles.index');
    Route::get('/artikel/neu', [ExpertArticleController::class, 'create'])->name('articles.create');
    Route::post('/artikel/ki-generieren', [ExpertArticleController::class, 'generateAi'])->name('articles.generate-ai');
    Route::post('/artikel', [ExpertArticleController::class, 'store'])->name('articles.store');
    Route::get('/artikel/{article}/bearbeiten', [ExpertArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/artikel/{article}', [ExpertArticleController::class, 'update'])->name('articles.update');
    Route::delete('/artikel/{article}', [ExpertArticleController::class, 'destroy'])->name('articles.destroy');

    Route::get('/events', [ExpertEventController::class, 'index'])->name('events.index');
    Route::get('/events/neu', [ExpertEventController::class, 'create'])->name('events.create');
    Route::post('/events/ki-generieren', [ExpertEventController::class, 'generateAi'])->name('events.generate-ai');
    Route::post('/events', [ExpertEventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}/bearbeiten', [ExpertEventController::class, 'edit'])->name('events.edit');
    Route::put('/events/{event}', [ExpertEventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [ExpertEventController::class, 'destroy'])->name('events.destroy');

    Route::get('/promotionen', [ExpertPromotionController::class, 'index'])->name('promotions.index');
    Route::post('/promotionen/anfragen', [ExpertPromotionController::class, 'requestPromotion'])->name('promotions.request');
    Route::post('/promotionen/warteliste', [ExpertPromotionController::class, 'joinWaitlist'])->name('promotions.waitlist');
});
