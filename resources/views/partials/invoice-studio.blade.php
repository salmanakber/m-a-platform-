@php /** @var array $boot */ @endphp
<div class="ins" id="invoiceStudio" data-boot='@json($boot)'>
    <header class="ins__hero">
        <div>
            <p class="ins__kicker">Invoice Control Room</p>
            <h1>Rechnungen ohne Reload</h1>
            <p>Produkt wählen → Angaben prüfen → Bestätigung → Rechnung senden. Kein Payment-Gateway — nur Generierung &amp; E-Mail.</p>
        </div>
        <div class="ins__hero-meta">
            <span>Fallback Monatspreis</span>
            <strong>{{ number_format((float) ($boot['monthly_price'] ?? 0), 2, '.', "'") }} CHF</strong>
            <a class="btn btn--ghost btn--small" href="{{ $boot['urls']['products'] ?? '#' }}">Produkte pflegen</a>
        </div>
    </header>

    <div class="ins__layout">
        <aside class="ins__rail">
            <ol class="ins__steps" data-ins-nav>
                <li data-ins-step="1" class="is-active"><em>01</em><span>Experte</span></li>
                <li data-ins-step="2"><em>02</em><span>Produkt</span></li>
                <li data-ins-step="3"><em>03</em><span>Bearbeiten</span></li>
                <li data-ins-step="4"><em>04</em><span>Versand</span></li>
                <li data-ins-step="5"><em>05</em><span>Fertig</span></li>
                <li data-ins-step="library"><em>▦</em><span>Bibliothek</span></li>
            </ol>
            <button type="button" class="btn btn--ghost btn--small" style="width:100%;margin-top:.75rem;" data-ins-restart>Neue Rechnung</button>
        </aside>

        <div class="ins__stage">
            <section class="ins__panel is-active" data-ins-panel="1">
                <div class="ins__head"><h2>Experte wählen</h2><p>An wen geht die Rechnung?</p></div>
                <input class="form-control" type="search" data-ins-expert-q placeholder="Suche Firma oder E-Mail…">
                <div class="ins__pick-list" data-ins-experts></div>
            </section>

            <section class="ins__panel" data-ins-panel="2" hidden>
                <div class="ins__head"><h2>Produkt wählen</h2><p>Preise aus dem Katalog — Betrag später noch änderbar.</p></div>
                <div class="ins__products" data-ins-products></div>
                <div class="ins__actions">
                    <button type="button" class="btn btn--ghost" data-ins-goto="1">Zurück</button>
                    <button type="button" class="btn" data-ins-create disabled>Entwurf erstellen</button>
                </div>
            </section>

            <section class="ins__panel" data-ins-panel="3" hidden>
                <div class="ins__head"><h2>Rechnung bearbeiten</h2><p>Alle Felder vor dem Versand anpassbar — inkl. abweichende Rechnungs-E-Mail.</p></div>
                <form class="ins__form" data-ins-form>
                    <div class="ins__grid2">
                        <label class="ins__field"><span>Rechnungsnr.</span><input name="invoice_number" type="text" readonly></label>
                        <label class="ins__field"><span>Datum</span><input name="invoice_date" type="date"></label>
                        <label class="ins__field"><span>Betrag CHF</span><input name="amount" type="number" step="0.01" min="0"></label>
                        <label class="ins__field"><span>Rechnungs-E-Mail</span><input name="billing_email" type="email"></label>
                    </div>
                    <label class="ins__field"><span>Leistungstitel</span><input name="service_title" type="text"></label>
                    <label class="ins__field"><span>Leistungsdetail</span><textarea name="service_detail" rows="3"></textarea></label>
                    <label class="ins__field"><span>Notiz auf Rechnung</span><textarea name="notes" rows="2"></textarea></label>
                    <label class="ins__field"><span>Interne Admin-Notiz</span><textarea name="admin_notes" rows="2"></textarea></label>
                    <div class="ins__actions">
                        <button type="button" class="btn btn--ghost" data-ins-goto="2">Zurück</button>
                        <button type="button" class="btn btn--ghost" data-ins-save>Speichern</button>
                        <button type="button" class="btn" data-ins-goto="4">Weiter zum Versand</button>
                    </div>
                </form>
            </section>

            <section class="ins__panel" data-ins-panel="4" hidden>
                <div class="ins__head"><h2>Bestätigung &amp; Versand</h2><p>Zuerst Bestätigungs-E-Mail, danach die eigentliche Rechnung — wie besprochen.</p></div>
                <div class="ins__progress">
                    <div class="ins__progress-top">
                        <div class="cvs__pulse" aria-hidden="true"><span></span><span></span><span></span></div>
                        <div>
                            <strong data-ins-progress-title>Bereit zum Versand</strong>
                            <p data-ins-progress-sub>Schritt für Schritt wählen</p>
                        </div>
                        <div class="ins__pct"><strong data-ins-pct>0%</strong></div>
                    </div>
                    <div class="ins__bar"><i data-ins-bar></i></div>
                    <ol class="ins__gen-steps">
                        <li data-ins-mail="confirm"><i></i><div><strong>1) Bestätigungs-E-Mail</strong><em>Vor der Rechnung — Angaben prüfen lassen</em></div></li>
                        <li data-ins-mail="invoice"><i></i><div><strong>2) Rechnung senden</strong><em>Offizielle Rechnung an billing e-mail</em></div></li>
                    </ol>
                    <div class="ins__feed" data-ins-feed aria-live="polite"></div>
                </div>
                <div class="ins__actions">
                    <button type="button" class="btn btn--ghost" data-ins-goto="3">Zurück</button>
                    <button type="button" class="btn" data-ins-send-confirm>1) Bestätigung senden</button>
                    <button type="button" class="btn" data-ins-send-invoice>2) Rechnung senden</button>
                </div>
            </section>

            <section class="ins__panel" data-ins-panel="5" hidden>
                <div class="ins__done">
                    <div class="ins__done-badge">✓</div>
                    <h2>Rechnung verarbeitet</h2>
                    <p data-ins-done-msg>Status und Versandzeiten sind gespeichert.</p>
                    <div class="ins__actions" style="justify-content:center;">
                        <button type="button" class="btn" data-ins-goto="library">Zur Bibliothek</button>
                        <button type="button" class="btn btn--ghost" data-ins-restart>Neue Rechnung</button>
                    </div>
                </div>
            </section>

            <section class="ins__panel" data-ins-panel="library" hidden>
                <div class="ins__head"><h2>Rechnungs-Bibliothek</h2><p>Zuletzt erstellte Rechnungen öffnen und weiterbearbeiten.</p></div>
                <div class="ins__library" data-ins-library></div>
            </section>

            <div class="ins__toast" data-ins-toast hidden></div>
        </div>
    </div>
</div>
