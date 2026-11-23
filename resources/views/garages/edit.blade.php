@extends('layouts.app')

@section('titulo', 'Editar cochera')

@section('contenido')
    <h1 class="h3 mb-3">Editar mi cochera</h1>

    @include('garages._form', [
        'garage'  => $garage,
        'accion'  => route('garages.update', $garage),
        'metodo'  => 'PUT',
        'boton'   => 'Guardar cambios',
    ])
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/garages-create.js') }}"></script>
@endpush
