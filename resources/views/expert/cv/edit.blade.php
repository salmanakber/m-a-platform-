@extends('layouts.expert')

@section('title', 'CV bearbeiten')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <a href="{{ route('expert.cv.index') }}">Lebenslauf</a>
    <span>/</span>
    <span>Bearbeiten</span>
@endsection

@section('content')
@include('partials.cv-editor', [
    'cv' => $cv,
    'payload' => $payload,
    'templates' => $templates,
    'formAction' => route('expert.cv.update', $cv),
    'aiAction' => route('expert.cv.generate-ai', $cv),
    'previewUrl' => route('expert.cv.preview', $cv),
    'finalizeAction' => route('expert.cv.finalize', $cv),
    'backUrl' => route('expert.cv.index'),
])
@endsection
