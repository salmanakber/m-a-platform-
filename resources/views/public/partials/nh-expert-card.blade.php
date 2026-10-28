@php
    /** @var array $card */
    $forceTag = $forceTag ?? null;
@endphp
<article class="nh-card">
    <span class="nh-card__rank" aria-hidden="true">{{ $card['n'] }}</span>
    @if (!empty($card['canton_code']))
        <span class="nh-card__place">{{ $card['canton_code'] }}@if(!empty($card['city'])) · {{ mb_strtoupper($card['city']) }}@endif</span>
    @endif

    @if (!empty($card['logo']))
        <span class="nh-avatar nh-avatar--logo"><img src="{{ $card['logo'] }}" alt=""></span>
    @else
        <span class="nh-avatar" style="--g:{{ $card['color'] }}">{{ $card['initials'] }}</span>
    @endif

    <h3>{{ $card['display_name'] }}</h3>
    @if (!empty($card['person_name']))
        <p class="nh-card__firm">{{ $card['person_name'] }}</p>
    @endif

    <div class="nh-tags">
        @if ($forceTag)
            <span>{{ $forceTag }}</span>
        @else
            <span>{{ $card['group_label'] }}</span>
            @if (!empty($card['offers_buy']))
                <span>Kauf</span>
            @endif
            @if (!empty($card['offers_sell']))
                <span>Verkauf</span>
            @endif
        @endif
    </div>

    <div class="nh-card__foot">
        <span>
            @if (!empty($card['rating']))
                ★ {{ $card['rating'] }}
            @endif
            @if (!empty($card['deals']))
                @if (!empty($card['rating'])) · @endif{{ $card['deals'] }} Transaktionen
            @endif
            @if (empty($card['rating']) && empty($card['deals']))
                {{ $card['place_line'] }}
            @endif
        </span>
        <a href="{{ $card['url'] }}">Profil ↗</a>
    </div>
</article>
