function getCity() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            // Using a free reverse geocoding API
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
                .then(response => response.json())
                .then(data => {
                    if (data.address && data.address.city) {
                        document.getElementById('city').value = data.address.city;
                    }
                })
                .catch(err => console.error("Error fetching city:", err));
        });
    }
}
window.onload = getCity;
