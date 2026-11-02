<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Expert;
use App\Models\ExpertCv;
use App\Services\Cv\CvGeneratorService;
use App\Support\TextLines;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait HandlesCvStudio
{
    abstract protected function cvService(): CvGeneratorService;

    /** @return array<string, mixed> */
    protected function studioBoot(Expert $expert, $existingCvs): array
    {
        $seed = $this->cvService()->seedFromExpert($expert);

        return [
            'expert' => [
                'id' => $expert->id,
                'company_name' => $expert->company_name,
                'display_name' => $expert->publicDisplayName(),
                'email' => $expert->email,
                'phone' => $expert->phone,
                'website' => $expert->website,
                'photo_url' => $expert->contact_photo_path ? asset('storage/'.$expert->contact_photo_path) : null,
                'group_label' => \App\Support\ProfessionalGroup::label($expert->professional_group ?? 'ma_specialist'),
                'profile_edit_url' => null,
            ],
            'seed' => $seed,
            'templates' => CvGeneratorService::TEMPLATES,
            'template_hints' => CvGeneratorService::TEMPLATE_HINTS,
            'colors' => CvGeneratorService::COLORS,
            'steps' => CvGeneratorService::generationSteps(),
            'cvs' => $existingCvs->map(fn (ExpertCv $cv) => $this->cvService()->toStudioArray($cv))->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    protected function parsePayload(Request $request, Expert $expert): array
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'template_key' => ['nullable', 'in:'.implode(',', array_keys(CvGeneratorService::TEMPLATES))],
            'full_name' => ['nullable', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'website' => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:8000'],
            'services' => ['nullable', 'string', 'max:8000'],
            'equity_capital' => ['nullable', 'string', 'max:120'],
            'experience_areas' => ['nullable'],
            'diplomas' => ['nullable'],
            'criteria' => ['nullable'],
            'experience' => ['nullable'],
            'education' => ['nullable'],
            'skills' => ['nullable'],
            'languages' => ['nullable'],
            'photo_path' => ['nullable', 'string', 'max:500'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'layout' => ['nullable', 'array'],
            'layout.style' => ['nullable', 'string', 'max:40'],
            'layout.density' => ['nullable', 'string', 'max:20'],
            'layout.type_mood' => ['nullable', 'string', 'max:20'],
        ]);

        $list = static function ($value): array {
            if (is_array($value)) {
                return array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $value)));
            }

            return TextLines::fromInput(is_string($value) ? $value : null);
        };

        return [
            'full_name' => $validated['full_name'] ?? '',
            'headline' => $validated['headline'] ?? '',
            'company' => $validated['company'] ?? '',
            'email' => $validated['email'] ?? '',
            'phone' => $validated['phone'] ?? '',
            'website' => $validated['website'] ?? '',
            'location' => $validated['location'] ?? '',
            'summary' => $validated['summary'] ?? '',
            'services' => $validated['services'] ?? '',
            'equity_capital' => $validated['equity_capital'] ?? '',
            'experience_areas' => $list($validated['experience_areas'] ?? null),
            'diplomas' => $list($validated['diplomas'] ?? null),
            'criteria' => $list($validated['criteria'] ?? null),
            'experience' => $list($validated['experience'] ?? null),
            'education' => $list($validated['education'] ?? null),
            'skills' => $list($validated['skills'] ?? null),
            'languages' => $list($validated['languages'] ?? null),
            'photo_path' => $validated['photo_path'] ?? $expert->contact_photo_path,
            'accent_color' => $validated['accent_color'] ?? '#0E4971',
            'layout' => is_array($validated['layout'] ?? null)
                ? $validated['layout']
                : ['style' => 'centered', 'density' => 'normal', 'type_mood' => 'sans'],
            '_notes' => $validated['notes'] ?? '',
        ];
    }

    protected function jsonCv(ExpertCv $cv, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'message' => $message,
            'cv' => $this->cvService()->toStudioArray($cv),
        ], $status);
    }
}
