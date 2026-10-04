/**
 * خريطة Leaflet لتحديد موقع طلب المساعدة — يُحمَّل مع dashboard612
 */
window.map612 = null;
window.map612Marker = null;

window.initMap612 = function initMap612() {
    const host = document.getElementById('mapHelp612');
    if (!host || typeof L === 'undefined') return;
    if (window.map612) {
        window.map612.invalidateSize();
        return;
    }
    const lat = parseFloat(document.getElementById('helpLat612').value) || 24.7136;
    const lng = parseFloat(document.getElementById('helpLng612').value) || 46.6753;
    window.map612 = L.map('mapHelp612').setView([lat, lng], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }).addTo(window.map612);
    window.map612Marker = L.marker([lat, lng], { draggable: true }).addTo(window.map612);
    window.map612Marker.on('dragend', () => {
        const p = window.map612Marker.getLatLng();
        document.getElementById('helpLat612').value = p.lat.toFixed(8);
        document.getElementById('helpLng612').value = p.lng.toFixed(8);
    });
    window.map612.on('click', (e) => {
        window.map612Marker.setLatLng(e.latlng);
        document.getElementById('helpLat612').value = e.latlng.lat.toFixed(8);
        document.getElementById('helpLng612').value = e.latlng.lng.toFixed(8);
    });
};
