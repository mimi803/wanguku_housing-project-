@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

<div class="farm-map-shell" data-farm-map>
    <script type="application/json" data-map-farms>{{ Illuminate\Support\Js::encode($mapFarmers) }}</script>
    <script type="application/json" data-map-customer>{{ Illuminate\Support\Js::encode($customerLocation) }}</script>
    <div class="farm-map-toolbar">
        <div class="farm-map-legend"><span><i class="map-dot farm-dot"></i> Partner farms</span>@if($customerLocation)<span><i class="map-dot customer-dot"></i> Your saved location</span>@endif</div>
        <button class="button button-light bordered" type="button" data-locate-customer>Use my current location</button>
    </div>
    <div class="farm-map" role="application" aria-label="Interactive map of partner farms and your location"></div>
    <p class="farm-map-message" data-map-message aria-live="polite">Farm locations appear when farmers choose to share their map location.</p>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(() => {
    const shell = document.querySelector('[data-farm-map]');
    if (!shell || !window.L) return;

    const farms = JSON.parse(shell.querySelector('[data-map-farms]').textContent || '[]');
    let customer = JSON.parse(shell.querySelector('[data-map-customer]').textContent || 'null');
    const mapElement = shell.querySelector('.farm-map');
    const message = shell.querySelector('[data-map-message]');
    const map = L.map(mapElement, { scrollWheelZoom: false }).setView([-1.1, 37.0], 8);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
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
    else if (!customer) message.textContent = 'No farmers have shared a map location yet. You can still use the map to find your current location.';

    shell.querySelector('[data-locate-customer]').addEventListener('click', () => {
        if (!navigator.geolocation) {
            message.textContent = 'Location is not available in this browser.';
            return;
        }
        message.textContent = 'Waiting for your location permission…';
        navigator.geolocation.getCurrentPosition((position) => {
            customer = { lat: position.coords.latitude, lng: position.coords.longitude };
            addCustomerMarker(customer, 'Your current location (not saved)');
            message.textContent = 'Your current location is shown only in this browser and has not been saved.';
        }, () => {
            message.textContent = 'Location was not available. Allow browser location access and try again.';
        }, { enableHighAccuracy: true, timeout: 12000 });
    });
})();
</script>
@endpush
