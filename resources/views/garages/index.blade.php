@extends('layouts.app')

@section('titulo', 'Cocheras cerca tuyo')

@section('contenido')
    <div class="d-flex flex-wrap align-items-end gap-3 mb-3">
        <div>
            <h1 class="h3 mb-0">Cocheras cerca tuyo</h1>
            <small class="text-body-secondary">Hacé clic en el mapa para buscar desde otro punto.</small>
        </div>
        <div class="ms-auto d-flex align-items-end gap-2">
            <div>
                <label for="radio" class="form-label mb-0 small">Radio</label>
                <select id="radio" class="form-select form-select-sm">
                    <option value="1">1 km</option>
                    <option value="2">2 km</option>
                    <option value="5" selected>5 km</option>
                    <option value="10">10 km</option>
                    <option value="25">25 km</option>
                </select>
            </div>
            <button id="btn-ubicacion" type="button" class="btn btn-outline-primary btn-sm">Usar mi ubicación</button>
        </div>
    </div>

    <div id="aviso-ubicacion" class="alert alert-warning py-2 d-none"></div>

    <div class="row g-3" id="app-garages"
         data-url-cercanas="{{ route('garages.cercanas') }}">
        <div class="col-lg-7 order-lg-2">
            <div id="mapa" class="mapa"></div>
        </div>
        <div class="col-lg-5 order-lg-1">
            {{-- Estados de la interfaz (Ep. 7): cargando / sin resultados / error --}}
            <div id="estado" class="text-body-secondary mb-2"></div>
            <div id="lista" class="list-group"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/garages-index.js') }}"></script>
@endpush
