/*
  Pantalla del cliente: mapa (MapLibre GL JS + OpenFreeMap) y búsqueda por cercanía con fetch (AJAX).
 
  Que hace la libreria (MapLibre): dibujar el mapa vectorial, los marcadores, los popups
  y el control de zoom.

  OpenFreeMap provee los datos del mapa (estilo y tiles).

  Nosotros hicimos: pedir la ubicación, llamar al endpoint, dibujar el círculo del radio,
  mostrar resultados, manejar estados (cargando / vacío / error), debounce y AbortController.
 
  MapLibre usa el orden [longitud, latitud], al revés de lo que solemos escribir.
 */
import * as maplibregl from 'maplibre-gl';

const ESTILO_MAPA = 'https://tiles.openfreemap.org/styles/liberty';
// El centro por defecto es, si no da permisos de localizacion lo manda al centro de Neuquen por defecto.
const CENTRO_POR_DEFECTO = { lat: -38.9516, lng: -68.0591 };

const app = document.querySelector('#app-garages');
const urlCercanas = app.dataset.urlCercanas;
const selectRadio = document.querySelector('#radio');
const btnUbicacion = document.querySelector('#btn-ubicacion');
const aviso = document.querySelector('#aviso-ubicacion');
const estado = document.querySelector('#estado');
const lista = document.querySelector('#lista');

let centro = { ...CENTRO_POR_DEFECTO };
let controlador = null; // AbortController de la búsqueda en curso
let temporizador = null; // debounce
let mapaListo = false; // true cuando el estilo del mapa terminó de cargar
let marcadores = []; // marcadores de resultados (para poder borrarlos)

// Mapa
let mapa;
try {
    mapa = new maplibregl.Map({
        container: 'mapa',
        style: ESTILO_MAPA,
        center: [centro.lng, centro.lat],
        zoom: 13,
    });
} catch (error) {
    // MapLibre dibuja con WebGL: si el navegador no lo soporta, falla acá.
    mostrarAviso('No pudimos iniciar el mapa: el navegador necesita soportar WebGL.');
    throw error;
}
mapa.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');

const marcadorCentro = new maplibregl.Marker({ color: '#0d6efd' })
    .setLngLat([centro.lng, centro.lat])
    .setPopup(new maplibregl.Popup({ offset: 25 }).setText('Punto de búsqueda'))
    .addTo(mapa);

// El círculo del radio se dibuja como un polígono GeoJSON.
    mapa.on('load', () => {
    mapa.addSource('radio', { type: 'geojson', data: circuloGeoJSON() });
    mapa.addLayer({
        id: 'radio-relleno', type: 'fill', source: 'radio',
        paint: { 'fill-color': '#0d6efd', 'fill-opacity': 0.08 },
    });
    mapa.addLayer({
        id: 'radio-borde', type: 'line', source: 'radio',
        paint: { 'line-color': '#0d6efd', 'line-width': 1.5 },
    });
    mapaListo = true;
});

function radioKm() {
    return Number(selectRadio.value);
}

// Creacion del poligono que asemeja a un circulo
function circuloGeoJSON(pasos = 64) {
    const R = 6371; // radio de la Tierra en km
    const d = radioKm() / R;
    const lat1 = (centro.lat * Math.PI) / 180;
    const lng1 = (centro.lng * Math.PI) / 180;
    const coords = [];
    for (let i = 0; i < pasos; i++) {
        const theta = (2 * Math.PI * i) / pasos;
        const lat2 = Math.asin(Math.sin(lat1) * Math.cos(d) + Math.cos(lat1) * Math.sin(d) * Math.cos(theta));
        const lng2 = lng1 + Math.atan2(
            Math.sin(theta) * Math.sin(d) * Math.cos(lat1),
            Math.cos(d) - Math.sin(lat1) * Math.sin(lat2)
        );
        coords.push([(lng2 * 180) / Math.PI, (lat2 * 180) / Math.PI]);
    }
    coords.push(coords[0]); // cerrar el anillo
    return { type: 'Feature', geometry: { type: 'Polygon', coordinates: [coords] } };
}

function actualizarRadio() {
    if (mapaListo) mapa.getSource('radio').setData(circuloGeoJSON());
}

// Ajusta el zoom para que el círculo entre completo en pantalla.
function encuadrarRadio() {
    const coords = circuloGeoJSON().geometry.coordinates[0];
    const limites = coords.reduce(
        (b, c) => b.extend(c),
        new maplibregl.LngLatBounds(coords[0], coords[0])
    );
    mapa.fitBounds(limites, { padding: 40, duration: 600, maxZoom: 16 });
}

// Esto es para ubicar a la persona
function usarMiUbicacion() {
    if (!('geolocation' in navigator)) {
        mostrarAviso('Tu navegador no soporta geolocalización. Hacé clic en el mapa para elegir un punto.');
        return;
    }
    estado.textContent = 'Buscando tu ubicación…';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            ocultarAviso();
            moverCentro(pos.coords.latitude, pos.coords.longitude, true);
        },
        () => {
            mostrarAviso('No pudimos obtener tu ubicación. Mostramos el centro de la ciudad; hacé clic en el mapa para elegir otro punto.');
            moverCentro(CENTRO_POR_DEFECTO.lat, CENTRO_POR_DEFECTO.lng, true);
        },
        { enableHighAccuracy: false, timeout: 8000 }
    );
}
//Mueve el marker del mapita
function moverCentro(lat, lng, encuadrar) {
    centro = { lat, lng };
    marcadorCentro.setLngLat([lng, lat]);
    actualizarRadio();
    if (encuadrar) encuadrarRadio();
    programarBusqueda();
}

function mostrarAviso(texto) {
    aviso.textContent = texto;
    aviso.classList.remove('d-none'); //clase de Bootstrap que oculta un elemento
}
function ocultarAviso() {
    aviso.classList.add('d-none');
}

// Busqueda con fetch
// Debounce, si hay muchos clicks seguidos esperamos unas milesimas 
function programarBusqueda() {
    clearTimeout(temporizador);
    temporizador = setTimeout(buscar, 300);
}

async function buscar() {
    // Se cancela la peticion anterior.
    if (controlador) controlador.abort();
    controlador = new AbortController();

    const params = new URLSearchParams({
        lat: centro.lat,
        lng: centro.lng,
        radio: selectRadio.value,
    });

    estado.textContent = 'Cargando cocheras…';
    try {
        const respuesta = await fetch(`${urlCercanas}?${params}`, {
            headers: { Accept: 'application/json' },
            signal: controlador.signal,
        });
        if (!respuesta.ok) throw new Error(`HTTP ${respuesta.status}`);
        const garages = await respuesta.json();
        mostrarResultados(garages);
    } catch (error) {
        if (error.name === 'AbortError') return; // búsqueda reemplazada: no es un error
        mostrarError();
    }
}


// Render (DOM: textContent, nunca innerHTML con datos de usuarios) 
function borrarMarcadores() {
    marcadores.forEach((m) => m.remove());
    marcadores = [];
}

function mostrarResultados(garages) {
    borrarMarcadores();
    lista.replaceChildren();

    if (garages.length === 0) {
        estado.textContent = 'No hay cocheras en este radio. Probá ampliarlo o elegir otro punto.';
        return;
    }
    estado.textContent = `${garages.length} cochera${garages.length === 1 ? '' : 's'} encontrada${garages.length === 1 ? '' : 's'}, de la más cercana a la más lejana.`;

    garages.forEach((g) => {
        const popup = new maplibregl.Popup({ offset: 25 }).setDOMContent(crearPopup(g));
        const marcador = new maplibregl.Marker()
            .setLngLat([g.longitud, g.latitud]) // [lng, lat]
            .setPopup(popup)
            .addTo(mapa);
        marcadores.push(marcador);

        const item = crearItem(g);
        item.addEventListener('click', () => {
            mapa.flyTo({ center: [g.longitud, g.latitud], zoom: 16 });
            if (!marcador.getPopup().isOpen()) marcador.togglePopup();
        });
        lista.appendChild(item);
    });
}

function crearPopup(g) {
    const caja = document.createElement('div');
    const titulo = document.createElement('strong');
    titulo.textContent = g.titulo;
    const precio = document.createElement('div');
    precio.textContent = g.precio_formateado;
    const dist = document.createElement('div');
    dist.textContent = `A ${g.distancia_km} km`;
    const enlace = document.createElement('a');
    enlace.href = g.url;
    enlace.textContent = 'Ver detalle';

    const ruta = document.createElement('a');
    ruta.href = urlComoLlegar(g);
    ruta.target = '_blank';
    ruta.rel = 'noopener';
    ruta.textContent = 'Cómo llegar';

    caja.append(titulo, precio, dist, enlace, document.createTextNode(' · '), ruta); // aca modifique el append anterior y agregue lo de document
    return caja;
}

function crearItem(g) {
    const item = document.createElement('div');
    item.className = 'list-group-item item-garage d-flex gap-3 align-items-center';

    let miniatura;
    if (g.portada_url) {
        miniatura = document.createElement('img');
        miniatura.className = 'miniatura';
        miniatura.src = g.portada_url;
        miniatura.alt = '';
    } else {
        miniatura = document.createElement('div');
        miniatura.className = 'sin-foto bg-secondary-subtle d-flex align-items-center justify-content-center small text-body-secondary';
        miniatura.textContent = 'Sin foto';
    }

    const info = document.createElement('div');
    info.className = 'flex-grow-1';
    const titulo = document.createElement('div');
    titulo.className = 'fw-semibold';
    titulo.textContent = g.titulo;
    const detalle = document.createElement('div');
    detalle.className = 'small text-body-secondary';
    detalle.textContent = `${g.direccion} · a ${g.distancia_km} km`;
    const precio = document.createElement('div');
    precio.className = 'small';
    precio.textContent = g.precio_formateado;
    info.append(titulo, detalle, precio);

    const ver = document.createElement('a');
    ver.className = 'btn btn-sm btn-outline-secondary';
    ver.href = g.url;
    ver.textContent = 'Ver';
    ver.addEventListener('click', (e) => e.stopPropagation());

    item.append(miniatura, info, ver);
    return item;
}

function mostrarError() {
    lista.replaceChildren();
    borrarMarcadores();
    estado.replaceChildren();
    const texto = document.createElement('span');
    texto.className = 'text-danger me-2';
    texto.textContent = 'No pudimos cargar las cocheras.';
    const reintentar = document.createElement('button');
    reintentar.type = 'button';
    reintentar.className = 'btn btn-sm btn-outline-danger';
    reintentar.textContent = 'Reintentar';
    reintentar.addEventListener('click', buscar);
    estado.append(texto, reintentar);
}

// EVENTOS
selectRadio.addEventListener('change', () => {
    actualizarRadio();
    encuadrarRadio();
    programarBusqueda();
});
btnUbicacion.addEventListener('click', usarMiUbicacion);
mapa.on('click', (e) => {
    // Un clic sobre un marcador o popup no debe mover el punto de búsqueda.
    if (e.originalEvent.target.closest('.maplibregl-marker, .maplibregl-popup')) return;
    moverCentro(e.lngLat.lat, e.lngLat.lng, false);
});

// Arranque: primero intentamos usar la ubicación real.
usarMiUbicacion();

//Funcion nueva agregada para que marcar lo de la ubi al lugar con maps
function urlComoLlegar(g) {
    return `https://www.google.com/maps/dir/?api=1`
        + `&destination=${g.latitud},${g.longitud}`
        + `&travelmode=driving`;
}
