<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'GarageMap')</title>

    <!-- Esto es para el icono del navegador -->
    <link rel="icon" href="{{ asset('icono.png') }}" type="image/png">

    {{-- Bootstrap (estilos) y MapLibre GL JS (mapas) desde CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@6.15.0/dist/maplibre-gl.css">

    {{-- MapLibre 6 se distribuye como módulo ES. El import map define el nombre
         "maplibre-gl" y la versión en UN solo lugar; los scripts hacen
         `import * as maplibregl from 'maplibre-gl'`. --}}
    <script type="importmap">
        { "imports": { "maplibre-gl": "https://unpkg.com/maplibre-gl@6.15.0/dist/maplibre-gl.mjs" } }
    </script>

    <style>
        .mapa { height: 70vh; min-height: 360px; border-radius: .5rem; }
        .mapa-chico { height: 320px; border-radius: .5rem; }
        .item-garage { cursor: pointer; }
        .item-garage:hover { background: var(--bs-light); }
        .miniatura { width: 72px; height: 72px; object-fit: cover; border-radius: .375rem; }
        .sin-foto { width: 72px; height: 72px; border-radius: .375rem; }
    </style>
</head>
<body class="bg-body-tertiary">
    <nav class="navbar navbar-expand bg-dark navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('garages.index') }}">GarageMap</a>
            <div class="navbar-nav ms-auto align-items-center">
                <a class="nav-link" href="{{ route('garages.index') }}">Buscar cocheras</a>

                @auth
                    <a class="nav-link" href="{{ route('garages.mias') }}">Mis cocheras</a>
                    <a class="nav-link" href="{{ route('garages.create') }}">Publicar</a>
                    {{-- {{ }} escapa el nombre: lo cargó un usuario --}}
                    <span class="navbar-text mx-2 d-none d-md-inline">{{ auth()->user()->name }}</span>
                    {{-- Salir es un POST con CSRF: un simple enlace permitiría cerrar la sesión desde otro sitio --}}
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light">Salir</button>
                    </form>
                @else
                    <a class="nav-link" href="{{ route('login') }}">Ingresar</a>
                    <a class="nav-link" href="{{ route('register') }}">Registrarme</a>
                @endauth
            </div>
        </div>
    </nav>

    <main class="container pb-5">
        @if (session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif

        @yield('contenido')
    </main>

    @stack('scripts')
</body>
</html>
