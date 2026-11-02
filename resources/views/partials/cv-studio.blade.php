@php
    /** @var array $boot */
@endphp
<div class="cvs" id="cvStudio" data-boot='@json($boot)'>
    <header class="cvs__hero">
        <div class="cvs__hero-copy">
            <p class="cvs__kicker">CV Control Room</p>
            <h1>Lebenslauf steuern &amp; gestalten</h1>
            <p>Profil prüfen, Design &amp; Farbe live wählen, KI-Abschnitte erzeugen und bestehende CVs erkunden — alles ohne Seitenreload.</p>
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
                <li data-step-nav="1" class="is-active"><em>01</em><span>Profil</span></li>
                <li data-step-nav="2"><em>02</em><span>Design</span></li>
                <li data-step-nav="3"><em>03</em><span>KI</span></li>
                <li data-step-nav="4"><em>04</em><span>Feinschliff</span></li>
                <li data-step-nav="5"><em>05</em><span>Fertig</span></li>
                <li data-step-nav="library"><em>▦</em><span>Bibliothek</span></li>
            </ol>
            <button type="button" class="btn btn--ghost btn--small cvs__rail-explore" data-cvs-open-library>CVs erkunden</button>
        </aside>

        <div class="cvs__stage">
            <section class="cvs__panel is-active" data-cvs-panel="1">
                <div class="cvs__panel-head">
                    <h2>Profil prüfen</h2>
                    <p>Diese Daten fliessen in den Lebenslauf. Ergänzen Sie sie, bevor Design und KI starten.</p>
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
                    <label class="cvs__field"><span>Kurzbeschreibung</span><textarea name="summary" rows="4"></textarea></label>
                    <label class="cvs__field"><span>Leistungen</span><textarea name="services" rows="3"></textarea></label>
                    <div class="cvs__grid2">
                        <label class="cvs__field"><span>Erfahrungsbereiche</span><textarea name="experience_areas" rows="3"></textarea></label>
                        <label class="cvs__field"><span>Diplome</span><textarea name="diplomas" rows="3"></textarea></label>
                    </div>
                    <label class="cvs__field"><span>Hinweise für die KI</span><textarea name="notes" rows="2" placeholder="z. B. Fokus auf KMU-Nachfolge…"></textarea></label>
                    <div class="cvs__actions">
                        @if (!empty($boot['expert']['profile_edit_url']))
                            <a class="btn btn--ghost" href="{{ $boot['expert']['profile_edit_url'] }}" target="_blank" rel="noopener">Profil öffnen</a>
                        @endif
                        <button type="button" class="btn" data-cvs-next="2">Weiter zum Design</button>
                    </div>
                </form>
            </section>

            <section class="cvs__panel" data-cvs-panel="2" hidden>
                <div class="cvs__panel-head cvs__panel-head--split">
                    <div>
                        <h2>Design Control Room</h2>
                        <p>Vorlage und Farbe wählen — die Vorschau aktualisiert sich live.</p>
                    </div>
                    <button type="button" class="btn btn--ghost btn--small" data-cvs-live-refresh>Vorschau neu laden</button>
                </div>
                <div class="cvs__design">
                    <div class="cvs__design-controls">
                        <p class="cvs__mini-label">Vorlage</p>
                        <div class="cvs__templates" data-cvs-templates role="radiogroup"></div>
                        <p class="cvs__mini-label">Akzentfarbe</p>
                        <div class="cvs__colors" data-cvs-colors role="radiogroup"></div>
                        <div class="cvs__eta-note" role="note">
                            <strong>Dauer ca. 2–3 Minuten</strong>
                            <span>Die KI schreibt 5 Abschnitte nacheinander (Zusammenfassung, Leistungen, Erfahrung, Ausbildung, Kompetenzen). Bitte Seite nicht neu laden und Tab nicht schliessen.</span>
                        </div>
                        <div class="cvs__actions">
                            <button type="button" class="btn btn--ghost" data-cvs-prev="1">Zurück</button>
                            <button type="button" class="btn btn--ghost" data-cvs-suggest-template><span data-cvs-suggest-label>KI-Vorlage</span></button>
                            <button type="button" class="btn" data-cvs-start-generate>Mit KI erzeugen (~2,5 Min.)</button>
                        </div>
                        <button type="button" class="btn btn--ghost btn--small" data-cvs-skip-ai>Nur Entwurf ohne KI</button>
                    </div>
                    <div class="cvs__design-preview">
                        <div class="cvs__preview-wrap cvs__preview-wrap--live">
                            <iframe class="cvs__preview" data-cvs-live-preview title="Live-Design-Vorschau"></iframe>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cvs__panel" data-cvs-panel="3" hidden>
                <div class="cvs__panel-head">
                    <h2>KI schreibt Ihren Lebenslauf</h2>
                    <p>Jeder Abschnitt erscheint live. Danach Feinschliff und Farbe jederzeit änderbar.</p>
                </div>
                <div class="cvs__wait-banner" data-cvs-wait-banner>
                    <div>
                        <strong>Bitte warten — ca. 2,5 Minuten</strong>
                        <p data-cvs-eta-text>Geschätzte Restzeit: ca. 2 Min. 30 Sek. · Seite nicht aktualisieren oder schliessen.</p>
                    </div>
                    <span class="cvs__wait-badge" data-cvs-eta-badge>~2:30</span>
                </div>
                <div class="cvs__why" data-cvs-why>
                    <strong>Warum dauert das?</strong>
                    <ul>
                        <li>5 Textabschnitte werden <em>nacheinander</em> von der KI erzeugt (nicht parallel).</li>
                        <li>Jeder Abschnitt braucht einen separaten KI-Aufruf (Qualität vor Geschwindigkeit).</li>
                        <li>Netzwerk und gewählter KI-Anbieter können die Dauer etwas verlängern.</li>
                    </ul>
                </div>
                <div class="cvs__progress">
                    <div class="cvs__progress-top">
                        <div class="cvs__pulse" aria-hidden="true"><span></span><span></span><span></span></div>
                        <div>
                            <strong data-cvs-progress-title>Vorbereitung…</strong>
                            <p data-cvs-progress-sub>Bitte einen Moment.</p>
                        </div>
                        <div class="cvs__pct"><strong data-cvs-pct>0%</strong></div>
                    </div>
                    <div class="cvs__bar"><i data-cvs-bar></i></div>
                    <ol class="cvs__gen-steps" data-cvs-gen-steps></ol>
                    <div class="cvs__feed" data-cvs-feed aria-live="polite"></div>
                </div>
                <div class="cvs__actions" data-cvs-gen-actions hidden>
                    <button type="button" class="btn" data-cvs-next="4">Zum Feinschliff</button>
                </div>
            </section>

            <section class="cvs__panel" data-cvs-panel="4" hidden>
                <div class="cvs__panel-head cvs__panel-head--split">
                    <div>
                        <h2>Feinschliff</h2>
                        <p>Texte, Farbe und Design anpassen. Autosave + Live-Vorschau.</p>
                    </div>
                    <div class="cvs__panel-tools">
                        <div class="cvs__colors cvs__colors--compact" data-cvs-colors-review></div>
                        <button type="button" class="btn btn--ghost btn--small" data-cvs-refresh-preview>Vorschau</button>
                        <button type="button" class="btn btn--small" data-cvs-finalize>Finalisieren</button>
                    </div>
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
                            <button type="button" class="btn" data-cvs-finalize>Finalisieren</button>
                        </div>
                    </form>
                    <div class="cvs__preview-wrap">
                        <iframe class="cvs__preview" data-cvs-preview title="CV-Vorschau"></iframe>
                    </div>
                </div>
            </section>

            <section class="cvs__panel" data-cvs-panel="5" hidden>
                <div class="cvs__done">
                    <div class="cvs__done-badge" aria-hidden="true">✓</div>
                    <h2>Lebenslauf bereit</h2>
                    <p data-cvs-done-msg>Finalisierte Version zum Öffnen und Drucken (PDF über Browser).</p>
                    <div class="cvs__actions" style="justify-content:center;">
                        <a class="btn" data-cvs-open-final href="#" target="_blank" rel="noopener">CV öffnen</a>
                        <button type="button" class="btn btn--ghost" data-cvs-next="4">Weiter bearbeiten</button>
                        <button type="button" class="btn btn--ghost" data-cvs-open-library>Bibliothek</button>
                        <button type="button" class="btn btn--ghost" data-cvs-restart>Neu starten</button>
                    </div>
                </div>
            </section>

            <section class="cvs__panel" data-cvs-panel="library" hidden>
                <div class="cvs__panel-head cvs__panel-head--split">
                    <div>
                        <h2>CV-Bibliothek</h2>
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
