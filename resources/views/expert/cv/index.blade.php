@extends('layouts.expert')

@section('title', 'CV Studio')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Lebenslauf</span>
@endsection

@section('content')
@include('partials.cv-studio', ['boot' => $boot])
@endsection

@push('scripts')
<script src="{{ asset('js/cv-studio.js') }}?v=6"></script>
@endpush
