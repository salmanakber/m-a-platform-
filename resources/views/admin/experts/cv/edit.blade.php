@extends('layouts.admin')

@section('title', 'CV bearbeiten')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <a href="{{ route('admin.experts.cv.index', $expert) }}">CV</a>
    <span>/</span>
    <span>Bearbeiten</span>
@endsection

@section('content')
@include('partials.cv-editor', [
    'cv' => $cv,
    'payload' => $payload,
    'templates' => $templates,
    'formAction' => route('admin.experts.cv.update', [$expert, $cv]),
    'aiAction' => route('admin.experts.cv.generate-ai', [$expert, $cv]),
    'previewUrl' => route('admin.experts.cv.preview', [$expert, $cv]),
    'finalizeAction' => route('admin.experts.cv.finalize', [$expert, $cv]),
    'backUrl' => route('admin.experts.cv.index', $expert),
])
@endsection
