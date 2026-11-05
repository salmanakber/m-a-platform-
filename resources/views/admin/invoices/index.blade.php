@extends('layouts.admin')

@section('title', 'Rechnungs-Studio')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <span>Rechnungen</span>
@endsection

@section('content')
@include('partials.invoice-studio', ['boot' => $boot])
@endsection

@push('scripts')
<script src="{{ asset('js/invoice-studio.js') }}?v=4"></script>
@endpush
