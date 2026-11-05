@php /** @var array $boot */ @endphp
<div class="ins" id="invoiceStudio" data-boot='@json($boot)'>
    <header class="ins__hero">
        <div>
            <p class="ins__kicker">Rechnungen</p>
            <h1>Rechnung erstellen</h1>
            <p>Experte suchen → Produkt → prüfen → Bestätigung &amp; Versand. Kein Payment-Gateway.</p>
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
                <li data-ins-step="1" class="is-active"><em>1</em><span>Experte</span></li>
                <li data-ins-step="2"><em>2</em><span>Produkt</span></li>
                <li data-ins-step="3"><em>3</em><span>Bearbeiten</span></li>
                <li data-ins-step="4"><em>4</em><span>Versand</span></li>
                <li data-ins-step="5"><em>5</em><span>Fertig</span></li>
                <li data-ins-step="library"><em>·</em><span>Bibliothek</span></li>
            </ol>
            <button type="button" class="btn btn--ghost btn--small" style="width:100%;margin-top:.75rem;" data-ins-restart>Neue Rechnung</button>
        </aside>

        <div class="ins__stage">
            <section class="ins__panel is-active" data-ins-panel="1">
                <div class="ins__head">
                    <h2>1. Experte wählen</h2>
                    <p>Suche unter {{ number_format((int) ($boot['expert_count'] ?? 0), 0, '.', "'") }} Experten — tippen, nicht scrollen.</p>
                </div>

                <div class="ins__expert-picker" data-ins-expert-picker>
                    <div class="ins__selected" data-ins-selected hidden>
                        <div class="ins__selected-body">
                            <span class="ins__selected-kicker">Ausgewählt</span>
                            <strong data-ins-selected-name>—</strong>
                            <span data-ins-selected-meta>—</span>
                        </div>
                        <div class="ins__selected-actions">
                            <button type="button" class="btn" data-ins-goto="2">Weiter</button>
                            <button type="button" class="btn btn--ghost btn--small" data-ins-clear-expert>Ändern</button>
                        </div>
                    </div>

                    <div class="ins__search" data-ins-search-wrap>
                        <label class="ins__search-label" for="insExpertSearch">Experte suchen</label>
                        <div class="ins__search-row">
                            <input id="insExpertSearch" class="ins__search-input" type="search" data-ins-expert-q
                                   placeholder="Firma, E-Mail, Kontaktperson oder ID…"
                                   autocomplete="off" spellcheck="false">
                            <span class="ins__search-status" data-ins-search-status hidden></span>
                        </div>
                        <p class="ins__search-hint" data-ins-search-hint>Mindestens 2 Zeichen — max. 12 Treffer live.</p>
                        <div class="ins__suggest" data-ins-experts role="listbox" hidden></div>
                    </div>

                    <div class="ins__recent" data-ins-recent-wrap>
                        <p class="ins__mini-label">Zuletzt verrechnet</p>
                        <div class="ins__recent-list" data-ins-recent></div>
                    </div>
                </div>
            </section>

            <section class="ins__panel" data-ins-panel="2" hidden>
                <div class="ins__head">
                    <h2>2. Produkt wählen</h2>
                    <p>Preise aus dem Katalog — Betrag später noch änderbar.</p>
                </div>
                <div class="ins__chosen" data-ins-chosen-expert hidden>
                    <span>Für</span>
                    <strong data-ins-chosen-name>—</strong>
                    <button type="button" class="btn btn--ghost btn--small" data-ins-goto="1">Experte ändern</button>
                </div>
                <div class="ins__products" data-ins-products></div>
                <div class="ins__actions">
                    <button type="button" class="btn btn--ghost" data-ins-goto="1">Zurück</button>
                    <button type="button" class="btn" data-ins-create disabled>Entwurf erstellen</button>
                </div>
            </section>

            <section class="ins__panel" data-ins-panel="3" hidden>
                <div class="ins__head"><h2>3. Rechnung bearbeiten</h2><p>Zeitraum, Menge × Preis, MwSt., Rechnungsadresse und E-Mail — alles vor dem Versand anpassbar. Bank/Absender unter Einstellungen → Rechnung / Bank.</p></div>
                <form class="ins__form" data-ins-form>
                    <div class="ins__grid2">
                        <label class="ins__field"><span>Rechnungsnr.</span><input name="invoice_number" type="text" readonly></label>
                        <label class="ins__field"><span>Datum</span><input name="invoice_date" type="date"></label>
                        <label class="ins__field"><span>Zeitraum von</span><input name="period_start" type="date"></label>
                        <label class="ins__field"><span>Zeitraum bis</span><input name="period_end" type="date"></label>
                    </div>
                    <div class="ins__calc" data-ins-calc>
                        <div class="ins__grid3">
                            <label class="ins__field"><span>Menge (Stk.)</span><input name="quantity" type="number" step="0.01" min="0.01" value="1" data-ins-calc-input></label>
                            <label class="ins__field"><span>Einzelpreis CHF</span><input name="unit_price" type="number" step="0.01" min="0" data-ins-calc-input></label>
                            <label class="ins__field"><span>MwSt. %</span><input name="vat_rate" type="number" step="0.01" min="0" max="100" placeholder="leer = 0" data-ins-calc-input></label>
                        </div>
                        <div class="ins__sum" aria-live="polite">
                            <span>Zwischensumme <strong data-ins-net>0.00</strong> CHF</span>
                            <span>MwSt. <strong data-ins-vat>0.00</strong> CHF</span>
                            <span class="ins__sum-total">Total <strong data-ins-total>0.00</strong> CHF</span>
                        </div>
                        <input type="hidden" name="amount" value="0">
                    </div>
                    <div class="ins__grid2">
                        <label class="ins__field"><span>Rechnungs-E-Mail</span><input name="billing_email" type="email" placeholder="Buchhaltung / abweichend"></label>
                        <label class="ins__field"><span>Rechnungsempfänger (Name)</span><input name="billing_name" type="text"></label>
                        <label class="ins__field"><span>Rechnungsadresse</span><input name="billing_address_line" type="text"></label>
                        <label class="ins__field"><span>PLZ / Ort</span>
                            <span class="ins__inline2">
                                <input name="billing_postal_code" type="text" placeholder="PLZ" style="max-width:5.5rem;">
                                <input name="billing_city" type="text" placeholder="Ort">
                            </span>
                        </label>
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
                <div class="ins__head">
                    <h2>4. E-Mails senden</h2>
                    <p>Ein Klick sendet Bestätigung und Rechnung an die Rechnungs-E-Mail. Dieselbe Firma darf beliebig viele Produkte / Rechnungen erhalten.</p>
                </div>
                <div class="ins__progress" data-ins-progress>
                    <div class="ins__progress-top">
                        <div class="cvs__pulse" data-ins-pulse hidden aria-hidden="true"><span></span><span></span><span></span></div>
                        <div>
                            <strong data-ins-progress-title>Bereit zum Senden</strong>
                            <p data-ins-progress-sub>Button unten klicken</p>
                        </div>
                        <div class="ins__pct"><strong data-ins-pct>0%</strong></div>
                    </div>
                    <div class="ins__bar"><i data-ins-bar></i></div>
                    <ol class="ins__gen-steps">
                        <li data-ins-mail="confirm"><i></i><div><strong>Bestätigung</strong><em>Kurz-Mail vor der Rechnung</em></div></li>
                        <li data-ins-mail="invoice"><i></i><div><strong>Rechnung</strong><em>Offizielle Rechnung mit Betrag / IBAN</em></div></li>
                    </ol>
                    <div class="ins__feed" data-ins-feed aria-live="polite"></div>
                </div>
                <div class="ins__actions">
                    <button type="button" class="btn btn--ghost" data-ins-goto="3">Zurück</button>
                    <button type="button" class="btn btn--ghost" data-ins-send-confirm>Nur Bestätigung</button>
                    <button type="button" class="btn" data-ins-send-invoice>Rechnung senden</button>
                </div>
            </section>

            <section class="ins__panel" data-ins-panel="5" hidden>
                <div class="ins__done">
                    <div class="ins__done-badge">✓</div>
                    <h2>Rechnung verarbeitet</h2>
                    <p data-ins-done-msg>Status und Versandzeiten sind gespeichert.</p>
                    <div class="ins__actions" style="justify-content:center;">
                        <button type="button" class="btn" data-ins-another-product>Weiteres Produkt · gleiche Firma</button>
                        <button type="button" class="btn btn--ghost" data-ins-goto="library">Zur Bibliothek</button>
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
