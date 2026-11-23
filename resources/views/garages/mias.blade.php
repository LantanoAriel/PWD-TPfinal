@extends('layouts.app')

@section('titulo', 'Mis cocheras')

@section('contenido')
    <div class="d-flex align-items-center mb-3">
        <h1 class="h3 mb-0">Mis cocheras</h1>
        <a href="{{ route('garages.create') }}" class="btn btn-primary ms-auto">Publicar una cochera</a>
    </div>

    @if ($garages->isEmpty())
        <p class="text-body-secondary">Todavía no publicaste ninguna cochera.</p>
    @else
        <div class="list-group">
            @foreach ($garages as $g)
                <div class="list-group-item d-flex flex-wrap gap-3 align-items-center">
                    @if ($g->fotos->isNotEmpty())
                        <img src="{{ $g->fotos->first()->url }}" alt="" class="miniatura">
                    @else
                        <div class="sin-foto bg-secondary-subtle d-flex align-items-center justify-content-center small text-body-secondary">Sin foto</div>
                    @endif

                    <div class="flex-grow-1">
                        <div class="fw-semibold">
                            {{ $g->titulo }}
                            @unless ($g->disponible)
                                <span class="badge text-bg-warning">No disponible</span>
                            @endunless
                        </div>
                        <div class="small text-body-secondary">{{ $g->direccion }}</div>
                        <div class="small">{{ $g->precio_formateado }}</div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('garages.show', $g) }}" class="btn btn-sm btn-outline-secondary">Ver</a>
                        @can('update', $g)
                            <a href="{{ route('garages.edit', $g) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                        @endcan
                        @can('delete', $g)
                            <form method="POST" action="{{ route('garages.destroy', $g) }}"
                                  onsubmit="return confirm('¿Eliminar esta publicación definitivamente?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
