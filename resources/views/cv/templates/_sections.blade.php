@php
    $mode = $mode ?? 'main';
    $maps = [
        'main' => [
            'summary' => 'Profil',
            'services' => 'Leistungen',
            'experience' => 'Berufserfahrung',
            'experience_areas' => 'Schwerpunkte',
            'education' => 'Ausbildung',
        ],
        'side' => [
            'skills' => 'Kompetenzen',
            'languages' => 'Sprachen',
            'diplomas' => 'Diplome',
            'criteria' => 'Kriterien',
        ],
        'left' => [
            'summary' => 'Profil',
            'services' => 'Leistungen',
            'experience' => 'Berufserfahrung',
        ],
        'right' => [
            'education' => 'Ausbildung',
            'skills' => 'Kompetenzen',
            'languages' => 'Sprachen',
            'experience_areas' => 'Schwerpunkte',
            'diplomas' => 'Diplome',
        ],
    ];
    $sections = $maps[$mode] ?? $maps['main'];
@endphp
@foreach ($sections as $key => $label)
    @if ($key === 'summary' && !empty($payload['summary']))
        <h2>{{ $label }}</h2>
        <p>{!! nl2br(e($payload['summary'])) !!}</p>
    @elseif ($key === 'services' && !empty($payload['services']))
        <h2>{{ $label }}</h2>
        <p>{!! nl2br(e($payload['services'])) !!}</p>
    @elseif (!empty($payload[$key]) && is_array($payload[$key]))
        <h2>{{ $label }}</h2>
        <ul>@foreach ($payload[$key] as $item)<li>{{ $item }}</li>@endforeach</ul>
    @endif
@endforeach
