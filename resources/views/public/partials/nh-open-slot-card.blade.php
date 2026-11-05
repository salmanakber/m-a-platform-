@php
    /** Open placeholder slot — reusable across Fachgruppen / Kantone / Fachbeiträge */
    $rank = $rank ?? '06';
    $title = $title ?? 'Ihr Platz';
    $subtitle = $subtitle ?? 'Kontakt & Bemerkung';
    $remark = $remark
        ?? 'Platz frei — melden Sie sich bei uns für eine Top-Position auf nachfolge-experten.ch.';
    $mail = $mail ?? ($footerContactEmail ?? 'kontakt@nachfolge-experten.ch');
    $subject = $subject ?? 'Top-Position nachfolge-experten.ch';
    $tag = $tag ?? 'OFFEN';
    $variant = $variant ?? 'card'; // card | row | article
@endphp

@if ($variant === 'row')
    <article class="nh-rankrow nh-rankrow--open">
        <span class="nh-rankrow__n">{{ $rank }}</span>
        <span class="nh-avatar nh-avatar--sm nh-avatar--contact" aria-hidden="true">?</span>
        <div class="nh-rankrow__copy">
            <strong>{{ $title }}</strong>
            <span>{{ \Illuminate\Support\Str::limit($remark, 90) }}</span>
        </div>
        <div class="nh-rankrow__meta"><em>{{ $tag }}</em></div>
        <a class="nh-rankrow__link" href="mailto:{{ $mail }}?subject={{ rawurlencode($subject) }}">Kontakt →</a>
    </article>
@elseif ($variant === 'article')
    <a class="nh-article-card nh-article-card--open" href="mailto:{{ $mail }}?subject={{ rawurlencode($subject) }}">
        <span class="nh-article-card__n">{{ $rank }}</span>
        <h3>{{ $title }}</h3>
        <p>{{ $remark }}</p>
        <em>Kontakt →</em>
    </a>
@else
    <article class="nh-card nh-card--contact">
        <span class="nh-card__rank" aria-hidden="true">{{ $rank }}</span>
        <span class="nh-card__place">{{ $tag }}</span>
        <span class="nh-avatar nh-avatar--contact" aria-hidden="true">?</span>
        <h3>{{ $title }}</h3>
        <p class="nh-card__firm">{{ $subtitle }}</p>
        <p class="nh-card__remark">{{ $remark }}</p>
        <div class="nh-card__foot">
            <span>Direkt melden</span>
            <a href="mailto:{{ $mail }}?subject={{ rawurlencode($subject) }}">Kontakt ↗</a>
        </div>
    </article>
@endif
