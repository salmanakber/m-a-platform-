<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSuggestion;
use App\Models\Expert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AISuggestionController extends Controller
{
    public function index(): View
    {
        $suggestions = AiSuggestion::query()
            ->with('expert')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.ai-suggestions.index', [
            'suggestions' => $suggestions,
        ]);
    }

    public function apply(AiSuggestion $aiSuggestion): RedirectResponse
    {
        if ($aiSuggestion->status !== 'pending') {
            return back()->with('error', 'Vorschlag wurde bereits bearbeitet.');
        }

        $target = $this->resolveTarget($aiSuggestion);

        if ($target instanceof Expert) {
            $field = $aiSuggestion->field_name;
            if (in_array($field, $target->getFillable(), true)) {
                $target->update([$field => $aiSuggestion->suggested_value]);
            }
        }

        $aiSuggestion->update([
            'status' => 'applied',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Vorschlag angewendet.');
    }

    public function reject(AiSuggestion $aiSuggestion): RedirectResponse
    {
        if ($aiSuggestion->status !== 'pending') {
            return back()->with('error', 'Vorschlag wurde bereits bearbeitet.');
        }

        $aiSuggestion->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Vorschlag abgelehnt.');
    }

    private function resolveTarget(AiSuggestion $suggestion): ?Expert
    {
        $suggestible = $suggestion->suggestible;

        if ($suggestible instanceof Expert) {
            return $suggestible;
        }

        if ($suggestion->expert_id !== null) {
            return $suggestion->expert;
        }

        return null;
    }
}
