@extends('layouts.app')

@section('titulo', 'Publicar cochera')

@section('contenido')
    <h1 class="h3 mb-3">Publicar mi cochera</h1>

    @include('garages._form', [
        'garage'  => null,
        'accion'  => route('garages.store'),
        'metodo'  => 'POST',
        'boton'   => 'Publicar',
    ])
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/garages-create.js') }}"></script>
@endpush
