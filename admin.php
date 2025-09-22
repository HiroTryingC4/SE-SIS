<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Add Location</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        #map { height: 400px; width: 100%; margin-bottom: 20px; }
        .form-container { padding: 20px; }
        label { font-weight: bold; }
        img { max-width: 150px; display: block; margin-top: 5px; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Admin - Add New Location</h2>

    <!-- Map -->
    <div id="map"></div>

    <!-- Form -->
    <div class="form-container">
        <form method="POST" action="" enctype="multipart/form-data">
            <label>Name:</label><br>
            <input type="text" name="name" required><br><br>

            <label>Category:</label><br>
            <select name="category" id="category" required>
                <option value="landmark">Landmarks and Parks</option>
                <option value="food">Foods and Tambay Spots</option>
                <option value="arts">Arts and Culture</option>
                <option value="mall">Malls and Entertainment</option>
            </select><br><br>

            <label>Description:</label><br>
            <textarea name="description"></textarea><br><br>

            <label>Photo:</label><br>
            <input type="file" name="photo" accept="image/*"><br><br>

            <label>Latitude:</label><br>
            <input type="text" name="latitude" id="lat" required><br><br>

            <label>Longitude:</label><br>
            <input type="text" name="longitude" id="lng" required><br><br>

            <button type="submit" name="submit">Add Location</button>
        </form>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        var map = L.map('map').setView([14.6760, 121.0437], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var marker;
        var icons = {
          landmark: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png', iconSize: [30, 30]}),
          food: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/2965/2965567.png', iconSize: [30, 30]}),
          arts: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/854/854878.png', iconSize: [30, 30]}),
          mall: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/1077/1077035.png', iconSize: [30, 30]})
        };

        map.on('click', function(e) {
            var lat = e.latlng.lat.toFixed(6);
            var lng = e.latlng.lng.toFixed(6);
            document.getElementById("lat").value = lat;
            document.getElementById("lng").value = lng;
            addOrUpdateMarker(lat, lng, document.getElementById("category").value);
        });

        document.getElementById("category").addEventListener("change", function() {
            var lat = document.getElementById("lat").value;
            var lng = document.getElementById("lng").value;
            if (lat && lng) addOrUpdateMarker(lat, lng, this.value);
        });

        function addOrUpdateMarker(lat, lng, category) {
            if (marker) map.removeLayer(marker);
            var iconToUse = icons[category] || L.icon({iconUrl: 'https://unpkg.com/leaflet/dist/images/marker-icon.png'});
            marker = L.marker([lat, lng], {icon: iconToUse}).addTo(map)
                .bindPopup("New Location<br>Lat: " + lat + "<br>Lng: " + lng).openPopup();
        }

        var locations = <?php
            $loc_sql = "SELECT name, category, description, latitude, longitude, photo FROM locations";
            $loc_result = $conn->query($loc_sql);
            $locations = [];
            if ($loc_result && $loc_result->num_rows > 0) {
                while ($row = $loc_result->fetch_assoc()) {
                    $locations[] = $row;
                }
            }
            echo json_encode($locations);
        ?>;

        locations.forEach(function(loc) {
            var iconToUse = icons[loc.category] || L.icon({iconUrl: 'https://unpkg.com/leaflet/dist/images/marker-icon.png'});
            var popupContent = "<b>" + loc.name + "</b><br>" +
                               "Category: " + (
                                   loc.category === "landmark" ? "Landmarks and Parks" :
                                   loc.category === "food" ? "Foods and Tambay Spots" :
                                   loc.category === "arts" ? "Arts and Culture" :
                                   loc.category === "mall" ? "Malls and Entertainment" :
                                   loc.category
                               ) + "<br>" +
                               (loc.description ? loc.description + "<br>" : "") +
                               (loc.photo ? "<img src='uploads/" + loc.photo + "'>" : "") +
                               "Lat: " + loc.latitude + "<br>Lng: " + loc.longitude;
            m = L.marker([loc.latitude, loc.longitude], {icon: iconToUse}).addTo(map);
            m.bindPopup(popupContent);
        });
    </script>
</body>
</html>

<?php
if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    $photoName = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir);
        $photoName = time() . "_" . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoName);
    }

    $sql = "INSERT INTO locations (name, category, description, latitude, longitude, photo)
            VALUES ('$name', '$category', '$description', '$latitude', '$longitude', '$photoName')";

    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green; text-align:center;'>✅ New location added successfully!</p>";
    } else {
        echo "<p style='color:red; text-align:center;'>❌ Error: " . $conn->error . "</p>";
    }
}
?>
