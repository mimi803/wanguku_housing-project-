@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css'">
@endpush

<div class="farm-map-shell" data-farm-map>
    <script type="application/json" data-map-farms>{!! Illuminate\Support\Js::encode($mapFarmers) !!}</script>
    <script type="application/json" data-map-customer>{!! Illuminate\Support\Js::encode($customerLocation) !!}</script>
    <div class="farm-map-toolbar">
        <form class="farm-map-search" data-map-search role="search"><label class="sr-only" for="farm-map-search">Search a town or area</label><input id="farm-map-search" type="search" name="q" placeholder="Search a town or area" autocomplete="off"><button class="button button-dark" type="submit">Find</button></form>
        <div class="farm-map-legend"><span><i class="map-dot farm-dot"></i> Partner farms</span>@if($customerLocation)<span><i class="map-dot customer-dot"></i> Your saved location</span>@endif</div>
        <div class="farm-map-actions">@if(config('services.resource_map.public_url'))<a class="button button-light bordered" href="{{ config('services.resource_map.public_url') }}" target="_blank" rel="noopener">Open Resource Map ↗</a>@endif<button class="button button-light bordered" type="button" data-locate-customer>Use my current location</button></div>
    </div>
    <div class="farm-map" role="application" aria-label="Interactive map of partner farms and your location"></div>
    <p class="farm-map-message" data-map-message aria-live="polite">Farm locations appear when farmers choose to share their map location.</p>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js'"></script>
<script>
(() => {
    const shell = document.querySelector('[data-farm-map]');
    if (!shell) return;
    const message = shell.querySelector('[data-map-message]');
    if (!window.L) {
        message.textContent = 'The map library did not load. Check your internet connection, then refresh the page.';
        return;
    }

    let farms;
    let customer;
    try {
        farms = JSON.parse(shell.querySelector('[data-map-farms]').textContent || '[]');
        customer = JSON.parse(shell.querySelector('[data-map-customer]').textContent || 'null');
    } catch (error) {
        message.textContent = 'The saved map locations could not be read. Refresh the page and try again.';
        console.error('Farmers Connect map location data could not be parsed.', error);
        return;
    }

    let map;
    try {
        map = L.map(shell.querySelector('.farm-map'), { scrollWheelZoom: false }).setView([-0.02, 37.9], 6);
    } catch (error) {
        message.textContent = 'The map could not be started. Refresh the page and try again.';
        console.error('Farmers Connect map initialization failed.', error);
        return;
    }
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
    }).on('tileerror', () => {
        message.textContent = 'Map tiles could not be downloaded. Check your internet connection and reload the page.';
    }).addTo(map);

    const bounds = [];
    farms.forEach((farm) => {
        const marker = L.circleMarker([farm.lat, farm.lng], { radius: 9, color: '#fff', weight: 3, fillColor: '#2f754b', fillOpacity: 1 }).addTo(map);
        const popup = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = farm.name;
        popup.append(name);
        if (farm.location) {
            const location = document.createElement('div');
            location.textContent = farm.location;
            popup.append(location);
        }
        const marketUrl = farm.market_url;
        if (marketUrl) {
            try {
                const linkUrl = new URL(marketUrl, window.location.href);
                if (['http:', 'https:'].includes(linkUrl.protocol)) {
                    const link = document.createElement('a');
                    link.href = linkUrl.href;
                    link.textContent = farm.products?.length ? `Shop ${farm.products.length} available item${farm.products.length === 1 ? '' : 's'} ↗` : 'Browse this farm ↗';
                    link.target = '_blank';
                    link.rel = 'noopener';
                    popup.append(link);
                }
            } catch (_) {}
        }
        marker.bindPopup(popup);
        bounds.push([farm.lat, farm.lng]);
    });

    let customerMarker = null;
    const addCustomerMarker = (location, label) => {
        if (customerMarker) map.removeLayer(customerMarker);
        customerMarker = L.circleMarker([location.lat, location.lng], { radius: 8, color: '#fff', weight: 3, fillColor: '#337bd1', fillOpacity: 1 })
            .addTo(map).bindPopup(label);
        map.setView([location.lat, location.lng], 13);
        customerMarker.openPopup();
    };

    if (customer) {
        addCustomerMarker(customer, 'Your saved location');
        bounds.push([customer.lat, customer.lng]);
    }
    if (bounds.length > 1) map.fitBounds(bounds, { padding: [28, 28], maxZoom: 13 });
    else if (farms.length) map.setView([farms[0].lat, farms[0].lng], 12);
    else if (!customer) message.textContent = 'No farmers have shared a map location yet. Search any town or use your current location.';
    setTimeout(() => map.invalidateSize(), 250);

    shell.querySelector('[data-locate-customer]').addEventListener('click', () => {
        if (!navigator.geolocation) {
            message.textContent = 'Location is not available in this browser.';
            return;
        }
        if (!window.isSecureContext) {
            message.textContent = 'Browser location requires a secure page. Open Farmers Connect on localhost or an HTTPS address.';
            return;
        }
        message.textContent = 'Waiting for your location permission…';
        navigator.geolocation.getCurrentPosition((position) => {
            customer = { lat: position.coords.latitude, lng: position.coords.longitude };
            addCustomerMarker(customer, 'Your current location (not saved)');
            message.textContent = 'Your current location is shown only in this browser and has not been saved.';
        }, (error) => {
            message.textContent = error.code === 1
                ? 'Location permission was denied. Allow location access in your browser settings and try again.'
                : error.code === 3
                    ? 'Finding your location timed out. Move to an area with better GPS or network signal and retry.'
                    : 'Your device could not determine its location. Check that location services are enabled and retry.';
        }, { enableHighAccuracy: true, timeout: 12000 });
    });

    const searchForm = shell.querySelector('[data-map-search]');
    let searchMarker = null;
    searchForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const query = new FormData(searchForm).get('q')?.toString().trim();
        if (!query) return;
        message.textContent = 'Searching OpenStreetMap…';
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=ke&q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Search unavailable');
            const results = await response.json();
            if (!results.length) { message.textContent = 'No Kenyan place matched that search. Try a nearby town or county.'; return; }
            const place = results[0];
            const location = { lat: Number(place.lat), lng: Number(place.lon) };
            if (!Number.isFinite(location.lat) || !Number.isFinite(location.lng)) throw new Error('Invalid place coordinates');
            map.setView([location.lat, location.lng], 13);
            const popup = document.createElement('span');
            popup.textContent = place.display_name;
            if (searchMarker) map.removeLayer(searchMarker);
            searchMarker = L.marker([location.lat, location.lng]).addTo(map).bindPopup(popup).openPopup();
            message.textContent = `Showing ${place.display_name}.`;
        } catch (_) {
            message.textContent = 'Place search is unavailable. Check your connection or use your current location.';
        }
    });
})();
</script>
@endpush
