<?php

use App\Http\Controllers\Admin\AISuggestionController as AdminAISuggestionController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\CrawlController as AdminCrawlController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmailTemplateController as AdminEmailTemplateController;
use App\Http\Controllers\Admin\ExpertController as AdminExpertController;
use App\Http\Controllers\Admin\ImportController as AdminImportController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Expert\ArticleController as ExpertArticleController;
use App\Http\Controllers\Expert\CrawlController as ExpertCrawlController;
use App\Http\Controllers\Expert\DashboardController as ExpertDashboardController;
use App\Http\Controllers\Expert\LeadController as ExpertLeadController;
use App\Http\Controllers\Expert\OfficeController as ExpertOfficeController;
use App\Http\Controllers\Expert\ProfileController as ExpertProfileController;
use App\Http\Controllers\Expert\PromotionController as ExpertPromotionController;
use App\Http\Controllers\PublicSite\ArticleController as PublicArticleController;
use App\Http\Controllers\PublicSite\CantonController;
use App\Http\Controllers\PublicSite\DirectoryController;
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

Route::get('/blog', [PublicArticleController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [PublicArticleController::class, 'show'])->name('blog.show');

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
    Route::post('/experten/bulk', [AdminExpertController::class, 'bulk'])->name('experts.bulk');
    Route::get('/experten/{expert}', [AdminExpertController::class, 'show'])->name('experts.show');
    Route::get('/experten/{expert}/bearbeiten', [AdminExpertController::class, 'edit'])->name('experts.edit');
    Route::put('/experten/{expert}', [AdminExpertController::class, 'update'])->name('experts.update');
    Route::post('/experten/{expert}/freigeben', [AdminExpertController::class, 'approve'])->name('experts.approve');
    Route::post('/experten/{expert}/ablehnen', [AdminExpertController::class, 'reject'])->name('experts.reject');
    Route::post('/experten/{expert}/deaktivieren', [AdminExpertController::class, 'deactivate'])->name('experts.deactivate');
    Route::post('/experten/{expert}/aktivieren', [AdminExpertController::class, 'activate'])->name('experts.activate');

    Route::get('/anfragen', [AdminLeadController::class, 'index'])->name('leads.index');
    Route::get('/anfragen/{lead}', [AdminLeadController::class, 'show'])->name('leads.show');
    Route::patch('/anfragen/{lead}/status', [AdminLeadController::class, 'updateStatus'])->name('leads.update-status');

    Route::get('/artikel', [AdminArticleController::class, 'index'])->name('articles.index');
    Route::get('/artikel/{article}/bearbeiten', [AdminArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/artikel/{article}', [AdminArticleController::class, 'update'])->name('articles.update');
    Route::post('/artikel/{article}/sperren', [AdminArticleController::class, 'block'])->name('articles.block');
    Route::post('/artikel/{article}/entsperren', [AdminArticleController::class, 'unblock'])->name('articles.unblock');
    Route::delete('/artikel/{article}', [AdminArticleController::class, 'destroy'])->name('articles.destroy');

    Route::get('/promotionen', [AdminPromotionController::class, 'index'])->name('promotions.index');
    Route::post('/promotionen/{promotion}/aktivieren', [AdminPromotionController::class, 'activate'])->name('promotions.activate');
    Route::post('/promotionen/{promotion}/beenden', [AdminPromotionController::class, 'expire'])->name('promotions.expire');

    Route::get('/rechnungen', [AdminInvoiceController::class, 'index'])->name('invoices.index');
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

    Route::get('/promotionen', [ExpertPromotionController::class, 'index'])->name('promotions.index');
    Route::post('/promotionen/anfragen', [ExpertPromotionController::class, 'requestPromotion'])->name('promotions.request');
    Route::post('/promotionen/warteliste', [ExpertPromotionController::class, 'joinWaitlist'])->name('promotions.waitlist');
});
