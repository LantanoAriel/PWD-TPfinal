<?php

namespace App\Http\Controllers;

use App\Models\Caracteristica;
use App\Models\Garage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GarageController extends Controller
{
    private const MAX_FOTOS = 5;

    //  PÚBLICO (sin sesión)

    // Pantalla del cliente: mapa + lista. Los datos llegan por AJAX (cercanas)
    public function index(): View
    {
        return view('garages.index');
    }

    /**
     * Endpoint JSON para la búsqueda por cercanía (Ep. 7: AJAX + fetch).
     * GET /api/garages/cercanas?lat=..&lng=..&radio=..
     */
    public function cercanas(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'lat'   => ['required', 'numeric', 'between:-90,90'],
            'lng'   => ['required', 'numeric', 'between:-180,180'],
            'radio' => ['nullable', 'numeric', 'between:0.1,50'],
        ]);

        $garages = Garage::disponibles()
            ->cercanas((float) $datos['lat'], (float) $datos['lng'], (float) ($datos['radio'] ?? 5))
            ->with('fotos')          // eager loading: evita N+1 al armar la portada
            ->limit(50)
            ->get();

        // Armamos la respuesta a mano: solo los campos que el cliente necesita.
        return response()->json($garages->map(fn (Garage $g) => [
            'id'                => $g->id,
            'titulo'            => $g->titulo,
            'direccion'         => $g->direccion,
            'latitud'           => $g->latitud,
            'longitud'          => $g->longitud,
            'precio_formateado' => $g->precio_formateado,
            'distancia_km'      => round((float) $g->distancia_km, 2),
            'portada_url'       => $g->fotos->first()?->url,
            'url'               => route('garages.show', $g),
        ])->values());
    }

    public function show(Garage $garage): View
    {
        $garage->load('fotos', 'caracteristicas');

        return view('garages.show', compact('garage'));
    }

    //  CON SESIÓN (middleware auth)

    // Las cocheras del usuario logueado. 
    public function mias(Request $request): View
    {
        // aca cambie el where por la relacion garages() del usuario, hace lo mismo pero usando la relacion
        $garages = $request->user()->garages()
            ->with('fotos')
            ->latest()
            ->get();

        return view('garages.mias', compact('garages'));
    }

    public function create(): View
    {
        return view('garages.create', [
            'caracteristicas' => Caracteristica::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas());

        $garage = DB::transaction(function () use ($datos, $request) {
            $garage = new Garage(Arr::except($datos, ['caracteristicas', 'fotos']));
            // El dueño lo pone el servidor a partir de la sesión: no viene del formulario.
            $garage->user_id = $request->user()->id;
            $garage->save();

            $garage->caracteristicas()->sync($datos['caracteristicas'] ?? []);
            $this->guardarFotos($garage, $request->file('fotos', []));

            return $garage;
        });

        return redirect()->route('garages.show', $garage)->with('ok', 'Publicación creada.');
    }


    //  SOLO EL DUEÑO (middleware auth + Policy)

    public function edit(Garage $garage): View
    {
        Gate::authorize('update', $garage); // 403 si no es el dueño

        $garage->load('fotos', 'caracteristicas');

        return view('garages.edit', [
            'garage'          => $garage,
            'caracteristicas' => Caracteristica::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Garage $garage): RedirectResponse
    {
        Gate::authorize('update', $garage); // la verificación va en el servidor, no alcanza con ocultar el botón

        $datos = $request->validate($this->reglas() + [
            'eliminar_fotos'   => ['nullable', 'array'],
            'eliminar_fotos.*' => ['integer'],
        ]);

        // Solo fotos de ESTA cochera: así no se pueden borrar fotos ajenas mandando ids inventados.
        $aEliminar = $garage->fotos()->whereIn('id', $datos['eliminar_fotos'] ?? [])->get();
        $nuevas = $request->file('fotos', []);

        if ($garage->fotos()->count() - $aEliminar->count() + count($nuevas) > self::MAX_FOTOS) {
            return back()->withInput()->withErrors([
                'fotos' => 'Una cochera puede tener hasta ' . self::MAX_FOTOS . ' fotos en total.',
            ]);
        }

        DB::transaction(function () use ($garage, $datos, $aEliminar, $nuevas) {
            $garage->update(Arr::except($datos, ['caracteristicas', 'fotos', 'eliminar_fotos']));
            $garage->caracteristicas()->sync($datos['caracteristicas'] ?? []);

            foreach ($aEliminar as $foto) {
                $foto->delete();
            }
            $this->guardarFotos($garage, $nuevas);
        });

        Storage::disk('public')->delete($aEliminar->pluck('ruta')->all());

        return redirect()->route('garages.show', $garage)->with('ok', 'Publicación actualizada.');
    }

    public function destroy(Garage $garage): RedirectResponse
    {
        Gate::authorize('delete', $garage);

        $rutas = $garage->fotos()->pluck('ruta')->all();

        DB::transaction(fn () => $garage->delete()); // fotos y pivote caen por cascade
        Storage::disk('public')->delete($rutas);

        return redirect()->route('garages.mias')->with('ok', 'Publicación eliminada.');
    }

    //  Auxiliares

    // Reglas de validación compartidas por store() y update()
    private function reglas(): array
    {
        return [
            'titulo'            => ['required', 'string', 'max:100'],
            'descripcion'       => ['nullable', 'string', 'max:1000'],
            'direccion'         => ['required', 'string', 'max:200'],
            'latitud'           => ['required', 'numeric', 'between:-90,90'],
            'longitud'          => ['required', 'numeric', 'between:-180,180'],
            'precio'            => ['required', 'integer', 'min:0', 'max:100000000'],
            'contacto_nombre'   => ['required', 'string', 'max:80'],
            'contacto_telefono' => ['required', 'string', 'max:30'],
            'disponible'        => ['sometimes', 'boolean'],
            'caracteristicas'   => ['nullable', 'array'],
            'caracteristicas.*' => ['integer', 'exists:caracteristicas,id'],
            'fotos'             => ['nullable', 'array', 'max:' . self::MAX_FOTOS],
            // (Tipo y tamaño validados en el servidor)
            'fotos.*'           => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    // Guarda los archivos con nombre propio (no confiamos en el del cliente) y crea las filas.
    private function guardarFotos(Garage $garage, array $archivos): void
    {
        foreach ($archivos as $archivo) {
            $garage->fotos()->create(['ruta' => $archivo->store('garages', 'public')]);
        }
    }
}
