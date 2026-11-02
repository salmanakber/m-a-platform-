@extends('layouts.admin')

@section('title', 'CV Studio — '.$expert->company_name)

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <a href="{{ route('admin.experts.index') }}">Experten</a>
    <span>/</span>
    <a href="{{ route('admin.experts.show', $expert) }}">{{ $expert->company_name }}</a>
    <span>/</span>
    <span>CV Studio</span>
@endsection

@section('content')
@include('partials.cv-studio', ['boot' => $boot])
@endsection

@push('scripts')
<script src="{{ asset('js/cv-studio.js') }}?v=3"></script>
@endpush
