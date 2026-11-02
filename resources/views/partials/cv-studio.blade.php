@php
    /** @var array $boot */
@endphp
<div class="cvs" id="cvStudio" data-boot='@json($boot)'>
    <header class="cvs__hero">
        <div class="cvs__hero-copy">
            <p class="cvs__kicker">Lebenslauf</p>
            <h1>CV in 3 Schritten</h1>
            <p>Angaben prüfen → Vorlage wählen → mit KI in unter einer Minute erzeugen und feinschleifen.</p>
        </div>
        <div class="cvs__hero-person">
            <div class="cvs__avatar" data-cvs-avatar>
                @if (!empty($boot['expert']['photo_url']))
                    <img src="{{ $boot['expert']['photo_url'] }}" alt="">
                @else
                    <span>{{ mb_strtoupper(mb_substr($boot['expert']['display_name'] ?? 'E', 0, 1)) }}</span>
                @endif
            </div>
            <div>
                <strong data-cvs-expert-name>{{ $boot['expert']['display_name'] ?? '' }}</strong>
                <span data-cvs-expert-meta>{{ $boot['expert']['group_label'] ?? '' }} · {{ $boot['expert']['company_name'] ?? '' }}</span>
            </div>
        </div>
    </header>

    <div class="cvs__layout">
        <aside class="cvs__rail" aria-label="Navigation">
            <ol class="cvs__steps" data-cvs-nav>
                <li data-step-nav="1" class="is-active"><em>1</em><span>Angaben</span></li>
                <li data-step-nav="2"><em>2</em><span>Design &amp; KI</span></li>
                <li data-step-nav="3"><em>3</em><span>Bearbeiten</span></li>
                <li data-step-nav="library"><em>·</em><span>Meine CVs</span></li>
            </ol>
            <button type="button" class="btn btn--ghost btn--small cvs__rail-explore" data-cvs-open-library>Gespeicherte CVs</button>
        </aside>

        <div class="cvs__stage">
            <section class="cvs__panel is-active" data-cvs-panel="1">
                <div class="cvs__panel-head">
                    <h2>1. Angaben prüfen</h2>
                    <p>Nur die wichtigsten Felder — die KI schreibt den Rest aus diesen Daten.</p>
                </div>
                <form class="cvs__form" data-cvs-form-profile autocomplete="off">
                    <div class="cvs__grid2">
                        <label class="cvs__field"><span>Vollständiger Name</span><input name="full_name" type="text"></label>
                        <label class="cvs__field"><span>Headline / Rolle</span><input name="headline" type="text"></label>
                        <label class="cvs__field"><span>Unternehmen</span><input name="company" type="text"></label>
                        <label class="cvs__field"><span>Standort</span><input name="location" type="text"></label>
                        <label class="cvs__field"><span>E-Mail</span><input name="email" type="email"></label>
                        <label class="cvs__field"><span>Telefon</span><input name="phone" type="text"></label>
                        <label class="cvs__field"><span>Website</span><input name="website" type="text"></label>
                        <label class="cvs__field"><span>Eigenkapital</span><input name="equity_capital" type="text" placeholder="z. B. 200kCHF"></label>
                    </div>
                    <label class="cvs__field"><span>Kurzbeschreibung (Ausgangstext)</span><textarea name="summary" rows="3"></textarea></label>
                    <label class="cvs__field"><span>Leistungen (Ausgangstext)</span><textarea name="services" rows="2"></textarea></label>
                    <div class="cvs__grid2">
                        <label class="cvs__field"><span>Erfahrungsbereiche</span><textarea name="experience_areas" rows="2"></textarea></label>
                        <label class="cvs__field"><span>Diplome</span><textarea name="diplomas" rows="2"></textarea></label>
                    </div>
                    <label class="cvs__field"><span>Hinweise für die KI (optional)</span><textarea name="notes" rows="2" placeholder="z. B. Fokus auf KMU-Nachfolge, 15 Jahre Erfahrung…"></textarea></label>
                    <div class="cvs__actions">
                        @if (!empty($boot['expert']['profile_edit_url']))
                            <a class="btn btn--ghost" href="{{ $boot['expert']['profile_edit_url'] }}" target="_blank" rel="noopener">Profil öffnen</a>
                        @endif
                        <button type="button" class="btn" data-cvs-next="2">Weiter: Design wählen</button>
                    </div>
                </form>
            </section>

            <section class="cvs__panel" data-cvs-panel="2" hidden>
                <div class="cvs__panel-head cvs__panel-head--split">
                    <div>
                        <h2>2. Design wählen &amp; erzeugen</h2>
                        <p>Vorlage und Farbe — Vorschau live. Die KI schreibt den gesamten CV in einem Durchgang (~30–60 Sek.).</p>
                    </div>
                    <button type="button" class="btn btn--ghost btn--small" data-cvs-live-refresh>Vorschau</button>
                </div>
                <div class="cvs__design">
                    <div class="cvs__design-controls">
                        <p class="cvs__mini-label">Vorlage ({{ count($boot['templates'] ?? []) }} Optionen)</p>
                        <div class="cvs__templates" data-cvs-templates role="radiogroup"></div>
                        <p class="cvs__mini-label">Akzentfarbe</p>
                        <div class="cvs__colors" data-cvs-colors role="radiogroup"></div>
                        <div class="cvs__eta-note" role="note">
                            <strong>Schnell: 1 KI-Aufruf ≈ unter 1 Minute</strong>
                            <span>Im Gegensatz zu früher (5 Aufrufe nacheinander) wird der ganze Lebenslauf jetzt auf einmal erzeugt — wie bei ChatGPT.</span>
                        </div>
                        <div class="cvs__actions">
                            <button type="button" class="btn btn--ghost" data-cvs-prev="1">Zurück</button>
                            <button type="button" class="btn btn--ghost" data-cvs-suggest-template><span data-cvs-suggest-label>KI wählt Vorlage</span></button>
                            <button type="button" class="btn btn--ghost" data-cvs-invent-design><span data-cvs-invent-label>KI erstellt Design</span></button>
                        </div>
                        <div class="cvs__actions">
                            <button type="button" class="btn" data-cvs-start-generate>Mit KI erzeugen (~1 Min.)</button>
                            <button type="button" class="btn btn--ghost btn--small" data-cvs-skip-ai>Ohne KI fortfahren</button>
                        </div>
                    </div>
                    <div class="cvs__design-preview">
                        <div class="cvs__preview-wrap cvs__preview-wrap--live">
                            <iframe class="cvs__preview" data-cvs-live-preview title="Live-Design-Vorschau"></iframe>
                        </div>
                        <div class="cvs__gen-overlay" data-cvs-gen-overlay hidden>
                            <div class="cvs__gen-card">
                                <div class="cvs__pulse" aria-hidden="true"><span></span><span></span><span></span></div>
                                <strong data-cvs-progress-title>KI schreibt Ihren Lebenslauf…</strong>
                                <p data-cvs-progress-sub>Ein Durchgang · bitte Seite nicht aktualisieren</p>
                                <div class="cvs__bar"><i data-cvs-bar></i></div>
                                <div class="cvs__pct-row">
                                    <span data-cvs-pct>0%</span>
                                    <span class="cvs__wait-badge" data-cvs-eta-badge>~0:45</span>
                                </div>
                                <p class="cvs__eta-line" data-cvs-eta-text>Geschätzte Dauer: ca. 30–60 Sekunden.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cvs__panel" data-cvs-panel="3" hidden>
                <div class="cvs__panel-head cvs__panel-head--split">
                    <div>
                        <h2>3. Feinschliff &amp; fertigstellen</h2>
                        <p>Vorlage/Farbe ändern, speichern, öffnen oder drucken — Bibliothek zeigt immer die aktuelle Version.</p>
                    </div>
                    <div class="cvs__panel-tools">
                        <button type="button" class="btn btn--ghost btn--small" data-cvs-refresh-preview>Vorschau</button>
                        <button type="button" class="btn btn--ghost btn--small" data-cvs-print>Drucken</button>
                        <button type="button" class="btn btn--small" data-cvs-finalize>Finalisieren</button>
                    </div>
                </div>
                <p class="cvs__mini-label">Vorlage</p>
                <div class="cvs__templates cvs__templates--compact" data-cvs-templates-review role="radiogroup"></div>
                <p class="cvs__mini-label">Akzentfarbe</p>
                <div class="cvs__colors cvs__colors--compact" data-cvs-colors-review></div>
                <div class="cvs__layout-chips" data-cvs-layout-chips hidden>
                    <p class="cvs__mini-label">KI-Layout</p>
                    <div class="cvs__chips" data-cvs-layout-styles></div>
                </div>
                <div class="cvs__split">
                    <form class="cvs__form cvs__form--compact" data-cvs-form-review>
                        <label class="cvs__field"><span>Titel</span><input name="title" type="text"></label>
                        <label class="cvs__field"><span>Zusammenfassung</span><textarea name="summary" rows="5"></textarea></label>
                        <label class="cvs__field"><span>Leistungen</span><textarea name="services" rows="4"></textarea></label>
                        <label class="cvs__field"><span>Berufserfahrung</span><textarea name="experience" rows="5"></textarea></label>
                        <label class="cvs__field"><span>Ausbildung</span><textarea name="education" rows="4"></textarea></label>
                        <label class="cvs__field"><span>Kompetenzen</span><textarea name="skills" rows="3"></textarea></label>
                        <label class="cvs__field"><span>Sprachen</span><textarea name="languages" rows="2"></textarea></label>
                        <div class="cvs__actions">
                            <button type="button" class="btn btn--ghost" data-cvs-prev="2">Design</button>
                            <button type="button" class="btn btn--ghost" data-cvs-save-review>Speichern</button>
                            <button type="button" class="btn btn--ghost" data-cvs-print>Drucken</button>
                            <button type="button" class="btn" data-cvs-finalize>Finalisieren</button>
                        </div>
                    </form>
                    <div class="cvs__preview-wrap">
                        <iframe class="cvs__preview" data-cvs-preview title="CV-Vorschau"></iframe>
                    </div>
                </div>
                <div class="cvs__done cvs__done--inline" data-cvs-done-block hidden>
                    <div class="cvs__done-badge" aria-hidden="true">✓</div>
                    <h2>Lebenslauf bereit</h2>
                    <p data-cvs-done-msg>Öffnen oder als PDF drucken (Browser → Speichern als PDF).</p>
                    <div class="cvs__actions" style="justify-content:center;">
                        <a class="btn" data-cvs-open-final href="#" target="_blank" rel="noopener">CV öffnen</a>
                        <button type="button" class="btn btn--ghost" data-cvs-print>Drucken / PDF</button>
                        <button type="button" class="btn btn--ghost" data-cvs-open-library>Meine CVs</button>
                        <button type="button" class="btn btn--ghost" data-cvs-restart>Neu starten</button>
                    </div>
                </div>
            </section>

            <section class="cvs__panel" data-cvs-panel="library" hidden>
                <div class="cvs__panel-head cvs__panel-head--split">
                    <div>
                        <h2>Meine CVs</h2>
                        <p>Bereits erzeugte Lebensläufe öffnen, ansehen oder löschen.</p>
                    </div>
                    <button type="button" class="btn" data-cvs-restart>Neuen CV starten</button>
                </div>
                <div class="cvs__library" data-cvs-library></div>
            </section>

            <div class="cvs__toast" data-cvs-toast hidden></div>
        </div>
    </div>
</div>
