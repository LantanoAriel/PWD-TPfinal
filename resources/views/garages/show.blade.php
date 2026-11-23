@extends('layouts.app')

@section('titulo', $garage->titulo)

@section('contenido')
    <a href="{{ route('garages.index') }}" class="btn btn-link px-0 mb-2">&larr; Volver a la búsqueda</a>

    <div class="row g-4">
        <div class="col-lg-7">
            <h1 class="h3">{{ $garage->titulo }}</h1>
            <p class="fs-5 mb-1">{{ $garage->precio_formateado }}</p>
            <p class="text-body-secondary">{{ $garage->direccion }}</p>

            @if ($garage->fotos->isNotEmpty())
                <div class="row g-2 mb-3">
                    @foreach ($garage->fotos as $foto)
                        <div class="col-6 col-md-4">
                            <a href="{{ $foto->url }}" target="_blank" rel="noopener">
                                <img src="{{ $foto->url }}" alt="Foto de la cochera" class="img-fluid rounded">
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-body-secondary">Esta publicación no tiene fotos.</p>
            @endif

            @if ($garage->descripcion)
                {{-- {{ }} escapa el HTML: evita XSS con texto cargado por usuarios (Ep. 7 Bis) --}}
                <p style="white-space: pre-line">{{ $garage->descripcion }}</p>
            @endif

            @if ($garage->caracteristicas->isNotEmpty())
                <p>
                    @foreach ($garage->caracteristicas as $c)
                        <span class="badge text-bg-secondary">{{ $c->nombre }}</span>
                    @endforeach
                </p>
            @endif

            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="h6">Contacto</h2>
                    <div>{{ $garage->contacto_nombre }}</div>
                    <div>{{ $garage->contacto_telefono }}</div>

                    
                    @if ($garage->whatsapp_url)
                    <a href="{{ $garage->whatsapp_url }}" target="_blank" rel="noopener"
                    class="btn btn-success btn-sm mt-2">Escribir por WhatsApp</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div id="mapa-detalle" class="mapa-chico mb-2"
                 data-lat="{{ $garage->latitud }}" data-lng="{{ $garage->longitud }}"
                 data-titulo="{{ $garage->titulo }}"></div>
            <a class="btn btn-outline-primary"
                 href="https://www.google.com/maps/dir/?api=1&destination={{ $garage->latitud }},{{ $garage->longitud }}&travelmode=driving"
                 target="_blank" rel="noopener">
                   Cómo llegar
            </a>
            <a class="small" target="_blank" rel="noopener"
               href="https://www.openstreetmap.org/?mlat={{ $garage->latitud }}&mlon={{ $garage->longitud }}#map=17/{{ $garage->latitud }}/{{ $garage->longitud }}">
                Abrir en OpenStreetMap
            </a>

            {{-- Solo el dueño ve estas acciones. Ocultarlas es comodidad: la seguridad real
                 está en el servidor (Gate::authorize en el controller). --}}
            @can('update', $garage)
                <div class="card mt-4">
                    <div class="card-body">
                        <h2 class="h6">Es tu publicación</h2>
                        <div class="d-flex gap-2">
                            <a href="{{ route('garages.edit', $garage) }}" class="btn btn-outline-primary">Editar</a>
                            <form method="POST" action="{{ route('garages.destroy', $garage) }}"
                                  onsubmit="return confirm('¿Eliminar esta publicación definitivamente?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        import * as maplibregl from 'maplibre-gl';

        const el = document.querySelector('#mapa-detalle');
        const lat = parseFloat(el.dataset.lat);
        const lng = parseFloat(el.dataset.lng);

        try {
            const mapa = new maplibregl.Map({
                container: el,
                style: 'https://tiles.openfreemap.org/styles/liberty',
                center: [lng, lat], // MapLibre: [longitud, latitud]
                zoom: 16,
            });
            mapa.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');

            // setText usa textContent: el título lo cargó un usuario y no debe interpretarse como HTML.
            new maplibregl.Marker()
                .setLngLat([lng, lat])
                .setPopup(new maplibregl.Popup({ offset: 25 }).setText(el.dataset.titulo))
                .addTo(mapa);
        } catch (error) {
            el.textContent = 'No pudimos mostrar el mapa: el navegador necesita soportar WebGL.';
        }
    </script>
@endpush
