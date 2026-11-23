/**
    Formulario del vendedor: elegir la ubicación de la cochera.

    MapLibre GL JS dibuja el mapa y el marcador arrastrable (los datos del mapa vienen de OpenFreeMap).
    Nominatim (OpenStreetMap) convierte una dirección escrita en coordenadas (geocodificación).
    Nosotros: conectamos todo con los campos latitud/longitud que viajan al servidor.

    Ojo: MapLibre usa el orden [longitud, latitud]; Nominatim devuelve lat y lon por separado.
 
    Nota: Nominatim es un servicio público con política de uso razonable
    (máx. 1 consulta por segundo, sin autocompletado). Por eso solo consultamos
    cuando la persona hace clic en "Ubicar en el mapa".
 */
import * as maplibregl from 'maplibre-gl';

const ESTILO_MAPA = 'https://tiles.openfreemap.org/styles/liberty';
const CENTRO_POR_DEFECTO = { lat: -38.9516, lng: -68.0591 };

const inputLat = document.querySelector('#latitud');
const inputLng = document.querySelector('#longitud');
const inputDireccion = document.querySelector('#direccion');
const btnBuscar = document.querySelector('#btn-buscar-direccion');
const btnUbicacion = document.querySelector('#btn-mi-ubicacion');
const estado = document.querySelector('#estado-mapa');
const form = document.querySelector('#form-garage');

const latInicial = parseFloat(inputLat.value);
const lngInicial = parseFloat(inputLng.value);
const hayPunto = Number.isFinite(latInicial) && Number.isFinite(lngInicial);

let mapa;
try {
    mapa = new maplibregl.Map({
        container: 'mapa-form',
        style: ESTILO_MAPA,
        center: hayPunto ? [lngInicial, latInicial] : [CENTRO_POR_DEFECTO.lng, CENTRO_POR_DEFECTO.lat],
        zoom: hayPunto ? 17 : 13,
    });
} catch (error) {
    // MapLibre necesita WebGL.
    estado.textContent = 'No pudimos iniciar el mapa: el navegador necesita soportar WebGL.';
    throw error;
}
mapa.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');

let marcador = null;

function ponerMarcador(lat, lng, centrar) {
    if (!marcador) {
        marcador = new maplibregl.Marker({ draggable: true }).setLngLat([lng, lat]).addTo(mapa);
        marcador.on('dragend', () => {
            const p = marcador.getLngLat();
            guardarCoordenadas(p.lat, p.lng);
        });
    } else {
        marcador.setLngLat([lng, lat]);
    }
    guardarCoordenadas(lat, lng);
    if (centrar) mapa.flyTo({ center: [lng, lat], zoom: 17 });
}

function guardarCoordenadas(lat, lng) {
    inputLat.value = lat.toFixed(7);
    inputLng.value = lng.toFixed(7);
}

if (hayPunto) ponerMarcador(latInicial, lngInicial, false);

mapa.on('click', (e) => {
    // Un clic sobre el marcador (por ejemplo al arrastrarlo) no debe reubicarlo.
    if (e.originalEvent.target.closest('.maplibregl-marker')) return;
    ponerMarcador(e.lngLat.lat, e.lngLat.lng, false);
});

// GEOCODIFICACION DE Nominatim
btnBuscar.addEventListener('click', async () => {
    const texto = inputDireccion.value.trim();
    if (texto === '') {
        estado.textContent = 'Escribí una dirección primero.';
        return;
    }
    estado.textContent = 'Buscando…';
    btnBuscar.disabled = true;
    try {
        const params = new URLSearchParams({ format: 'json', limit: '1', countrycodes: 'ar', q: texto });
        const respuesta = await fetch(`https://nominatim.openstreetmap.org/search?${params}`, {
            headers: { Accept: 'application/json' },
        });
        if (!respuesta.ok) throw new Error(`HTTP ${respuesta.status}`);
        const resultados = await respuesta.json();
        if (resultados.length === 0) {
            estado.textContent = 'No encontramos esa dirección. Marcá el punto a mano en el mapa.';
            return;
        }
        ponerMarcador(parseFloat(resultados[0].lat), parseFloat(resultados[0].lon), true);
        estado.textContent = 'Ubicación encontrada. Ajustá el marcador si hace falta.';
    } catch (error) {
        estado.textContent = 'No pudimos buscar la dirección ahora. Marcá el punto a mano en el mapa.';
    } finally {
        btnBuscar.disabled = false;
    }
});

// Ubicacion Actual (api)
btnUbicacion.addEventListener('click', () => {
    if (!('geolocation' in navigator)) {
        estado.textContent = 'Tu navegador no soporta geolocalización.';
        return;
    }
    estado.textContent = 'Buscando tu ubicación…';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            ponerMarcador(pos.coords.latitude, pos.coords.longitude, true);
            estado.textContent = 'Usamos tu ubicación actual.';
        },
        () => {
            estado.textContent = 'No pudimos obtener tu ubicación. Marcá el punto en el mapa.';
        },
        { timeout: 8000 }
    );
});

// Ayuda de usabilidad (no reemplaza la validación del servidor).
// Validación en el navegador que avisa al vendedor si 
// olvidó marcar la ubicación, antes de que el formulario se envíe al servidor
form.addEventListener('submit', (e) => {
    if (inputLat.value === '' || inputLng.value === '') {
        e.preventDefault();
        estado.textContent = 'Marcá la ubicación de la cochera en el mapa antes de publicar.';
        document.querySelector('#mapa-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    //el usuario se entera del problema al instante y sin recargar la página
});
