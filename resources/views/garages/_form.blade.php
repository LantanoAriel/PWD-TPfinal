{{--
    Formulario compartido por "publicar" y "editar".
    Variables: $accion (URL), $metodo ('POST' o 'PUT'), $boton (texto), $caracteristicas,
               $garage (null al publicar; la cochera al editar).
--}}
@if ($errors->any())
    <div class="alert alert-danger">Revisá los campos marcados: hay datos que no son válidos.</div>
@endif

<form method="POST" action="{{ $accion }}" enctype="multipart/form-data" id="form-garage" novalidate>
    @csrf
    @if ($metodo !== 'POST')
        @method($metodo)
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="mb-3">
                <label for="titulo" class="form-label">Título</label>
                <input type="text" id="titulo" name="titulo" maxlength="100"
                       class="form-control @error('titulo') is-invalid @enderror"
                       value="{{ old('titulo', $garage?->titulo) }}" required>
                @error('titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción (opcional)</label>
                <textarea id="descripcion" name="descripcion" rows="3" maxlength="1000"
                          class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $garage?->descripcion) }}</textarea>
                @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="precio" class="form-label">Precio por mes ($)</label>
                <input type="number" id="precio" name="precio" min="0" step="1"
                       class="form-control @error('precio') is-invalid @enderror"
                       value="{{ old('precio', $garage?->precio) }}" required>
                @error('precio') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-check mb-3">
                {{-- El campo oculto envía "0" cuando la casilla está desmarcada (un checkbox sin marcar no envía nada) --}}
                <input type="hidden" name="disponible" value="0">
                <input class="form-check-input" type="checkbox" id="disponible" name="disponible" value="1"
                       @checked(old('disponible', $garage?->disponible ?? true))>
                <label class="form-check-label" for="disponible">Disponible (visible en las búsquedas)</label>
            </div>

            <div class="mb-3">
                <span class="form-label d-block">Características</span>
                @php
                    $marcadas = old('caracteristicas', $garage ? $garage->caracteristicas->pluck('id')->all() : []);
                @endphp
                @foreach ($caracteristicas as $c)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="caracteristicas[]"
                               id="car-{{ $c->id }}" value="{{ $c->id }}"
                               @checked(in_array($c->id, $marcadas))>
                        <label class="form-check-label" for="car-{{ $c->id }}">{{ $c->nombre }}</label>
                    </div>
                @endforeach
                @error('caracteristicas.*') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            @if ($garage && $garage->fotos->isNotEmpty())
                <div class="mb-3">
                    <span class="form-label d-block">Fotos actuales (marcá las que quieras quitar)</span>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach ($garage->fotos as $foto)
                            <label class="text-center small">
                                <img src="{{ $foto->url }}" alt="Foto de la cochera" class="miniatura d-block mb-1">
                                <input type="checkbox" name="eliminar_fotos[]" value="{{ $foto->id }}"
                                       @checked(in_array($foto->id, old('eliminar_fotos', [])))> Quitar
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mb-3">
                <label for="fotos" class="form-label">
                    {{ $garage ? 'Agregar fotos' : 'Fotos' }} (hasta 5 en total, JPG/PNG/WEBP, máx. 2 MB c/u)
                </label>
                <input type="file" id="fotos" name="fotos[]" multiple accept="image/jpeg,image/png,image/webp"
                       class="form-control @error('fotos') is-invalid @enderror @error('fotos.*') is-invalid @enderror">
                @error('fotos') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @error('fotos.*') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <hr>

            <div class="row g-3">
                <div class="col-sm-6">
                    <label for="contacto_nombre" class="form-label">Nombre de contacto</label>
                    <input type="text" id="contacto_nombre" name="contacto_nombre" maxlength="80"
                           class="form-control @error('contacto_nombre') is-invalid @enderror"
                           value="{{ old('contacto_nombre', $garage?->contacto_nombre ?? auth()->user()->name) }}" required>
                    @error('contacto_nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-6">
                    <label for="contacto_telefono" class="form-label">Teléfono</label>
                    <input type="text" id="contacto_telefono" name="contacto_telefono" maxlength="30"
                           class="form-control @error('contacto_telefono') is-invalid @enderror"
                           value="{{ old('contacto_telefono', $garage?->contacto_telefono) }}" required>
                    @error('contacto_telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <label for="direccion" class="form-label">Dirección</label>
            <div class="input-group mb-2">
                <input type="text" id="direccion" name="direccion" maxlength="200"
                       class="form-control @error('direccion') is-invalid @enderror"
                       value="{{ old('direccion', $garage?->direccion) }}" required>
                <button type="button" id="btn-buscar-direccion" class="btn btn-outline-secondary">Ubicar en el mapa</button>
                @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex gap-2 mb-2">
                <button type="button" id="btn-mi-ubicacion" class="btn btn-outline-primary btn-sm">Usar mi ubicación actual</button>
                <span id="estado-mapa" class="small text-body-secondary align-self-center"></span>
            </div>

            <div id="mapa-form" class="mapa-chico mb-2"></div>
            <div class="form-text mb-2">Hacé clic en el mapa o arrastrá el marcador para ajustar el punto exacto.</div>

            <div class="row g-2">
                <div class="col">
                    <label for="latitud" class="form-label small mb-0">Latitud</label>
                    <input type="text" id="latitud" name="latitud" readonly
                           class="form-control form-control-sm @error('latitud') is-invalid @enderror"
                           value="{{ old('latitud', $garage?->latitud) }}">
                </div>
                <div class="col">
                    <label for="longitud" class="form-label small mb-0">Longitud</label>
                    <input type="text" id="longitud" name="longitud" readonly
                           class="form-control form-control-sm @error('longitud') is-invalid @enderror"
                           value="{{ old('longitud', $garage?->longitud) }}">
                </div>
            </div>
            @if ($errors->has('latitud') || $errors->has('longitud'))
                <div class="text-danger small mt-1">Marcá la ubicación de la cochera en el mapa.</div>
            @endif
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">{{ $boton }}</button>
        <a href="{{ $garage ? route('garages.show', $garage) : route('garages.mias') }}" class="btn btn-link">Cancelar</a>
    </div>
</form>
