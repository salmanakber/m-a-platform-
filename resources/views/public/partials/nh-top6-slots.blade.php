{{--
  Render exactly 6 slots: filled expert/article cards + OFFEN placeholders for gaps.
  @var \Illuminate\Support\Collection|array $cards  items with key "position" or "n"
  @var string $variant  card|row|article
  @var string|null $forceTag
  @var string $remark
  @var string $mail
  @var string $subject
  @var string|null $openTitlePrefix  e.g. "Platz"
--}}
@php
    $variant = $variant ?? 'card';
    $forceTag = $forceTag ?? null;
    $remark = $remark ?? 'Platz frei — melden Sie sich bei uns für eine Top-Position auf nachfolge-experten.ch.';
    $mail = $mail ?? ($footerContactEmail ?? 'kontakt@nachfolge-experten.ch');
    $subject = $subject ?? 'Top-Position · nachfolge-experten.ch';
    $openTitlePrefix = $openTitlePrefix ?? 'Platz';
    $maxSlots = (int) ($maxSlots ?? 6);

    $byPos = collect($cards ?? [])
        ->values()
        ->mapWithKeys(function ($card, int $index) {
            if (! is_array($card)) {
                return [];
            }
            $pos = (int) ($card['position'] ?? $card['n'] ?? 0);
            if ($pos < 1) {
                $pos = $index + 1;
            }

            return [$pos => $card];
        });
@endphp

@for ($i = 1; $i <= $maxSlots; $i++)
    @php $card = $byPos->get($i); @endphp
    @if ($card && $variant === 'card')
        @include('public.partials.nh-expert-card', ['card' => $card, 'forceTag' => $forceTag])
    @elseif ($card && $variant === 'row')
        <article class="nh-rankrow">
            <span class="nh-rankrow__n">{{ $card['n'] }}</span>
            @if (!empty($card['logo']))
                <span class="nh-avatar nh-avatar--sm nh-avatar--logo"><img src="{{ $card['logo'] }}" alt=""></span>
            @else
                <span class="nh-avatar nh-avatar--sm" style="--g:{{ $card['color'] }}">{{ $card['initials'] }}</span>
            @endif
            <div class="nh-rankrow__copy">
                <strong>{{ $card['display_name'] }}</strong>
                @if (!empty($card['person_name']))
                    <span>{{ $card['person_name'] }}</span>
                @endif
            </div>
            <div class="nh-rankrow__meta">
                @if (!empty($card['rating']))
                    <em>★ {{ $card['rating'] }}</em>
                @endif
                @if (!empty($card['deals']))
                    <span>{{ $card['deals'] }} Deals</span>
                @endif
            </div>
            <a class="nh-rankrow__link" href="{{ $card['url'] }}">Profil →</a>
        </article>
    @elseif ($card && $variant === 'article')
        <a class="nh-article-card" href="{{ $card['url'] }}">
            <span class="nh-article-card__n">{{ $card['n'] }}</span>
            <h3>{{ $card['title'] }}</h3>
            <p>{{ $card['excerpt'] ?? '' }}</p>
            <em>Lesen →</em>
        </a>
    @else
        @include('public.partials.nh-open-slot-card', [
            'variant' => $variant === 'card' ? 'card' : $variant,
            'rank' => str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            'title' => $openTitlePrefix.' '.$i,
            'remark' => $remark,
            'promoScope' => $promoScope ?? null,
            'promoCanton' => $promoCanton ?? null,
        ])
    @endif
@endfor
