<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>QC Multi-stop Travel Map with ETA & Reset</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
  <style>
    body { margin: 0; padding: 0; }
    .container {
      display: flex;
      flex-direction: row;
      height: 600px;
      width: 100%;
    }
    #info-panel {
      width: 35%;
      min-width: 280px;
      max-width: 420px;
      background: #fff;
      border-right: 1px solid #eee;
      padding: 12px;
      box-sizing: border-box;
      font-family: Arial, sans-serif;
      font-size: 14px;
      overflow-y: auto;
    }
    #map {
      flex: 1;
      height: 100%;
      width: 65%;
      position: relative;
    }
    /* Enhanced UI for legend, stops, and directions */
    .stops-control {
      background: #f7fbff;
      border: 1px solid #e3eafc;
      padding: 12px 16px;
      border-radius: 8px;
      max-height: 220px;
      overflow-y: auto;
      font-family: 'Segoe UI', Arial, sans-serif;
      font-size: 14px;
      box-shadow: 0 2px 12px rgba(44,62,80,0.08);
      min-width: 220px;
      width: 260px;
      word-break: break-word;
      margin-top: 10px;
    }
    .stops-control b {
      font-size: 16px;
      color: #2a4d8f;
      margin-bottom: 8px;
      display: block;
    }
    .stops-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .stops-list li {
      margin-bottom: 10px;
      padding: 7px 0;
      border-bottom: 1px solid #eaeaea;
      color: #2d3a4a;
      font-weight: 500;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .stops-list .stop-info {
      font-size: 13px;
      color: #555;
      margin-left: 4px;
    }
    .legend {
      background: #f7fbff;
      border: 1px solid #e3eafc;
      padding: 12px 16px;
      border-radius: 8px;
      font-family: 'Segoe UI', Arial, sans-serif;
      font-size: 14px;
      box-shadow: 0 2px 12px rgba(44,62,80,0.08);
      min-width: 220px;
      width: 260px;
      word-break: break-word;
      margin-bottom: 10px;
    }
    .legend-title {
      font-size: 16px;
      color: #2a4d8f;
      font-weight: bold;
      margin-bottom: 8px;
      display: block;
    }
    .legend-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .legend-list li {
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      font-size: 14px;
      color: #2d3a4a;
    }
    .legend-list img {
      vertical-align: middle;
      width: 22px;
      height: 22px;
      margin-right: 8px;
      border-radius: 4px;
      border: 1px solid #eaeaea;
      background: #fff;
      box-shadow: 0 1px 4px rgba(44,62,80,0.08);
    }
    /* Add style for reset button */
    .reset-btn {
      display: inline-block;
      background: #e74c3c;
      color: #fff;
      padding: 8px 18px;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      margin: 12px 0 0 12px;
      box-shadow: 0 2px 8px rgba(44,62,80,0.08);
      border: none;
      transition: background 0.2s;
    }
    .reset-btn:hover {
      background: #c0392b;
    }
    /* Legend inside map, left side */
    .leaflet-left .legend {
      position: relative;
      margin: 12px 0 0 12px;
      z-index: 800;
    }
    /* Responsive for small screens */
    @media (max-width: 900px) {
      .container { flex-direction: column; height: auto; }
      #info-panel { width: 100%; max-width: none; min-width: 0; }
      #map { width: 100%; height: 400px; }
      .stops-control, .legend { min-width: 120px; width: 100%; }
      .leaflet-left .legend { margin: 8px 0 0 8px; }
    }
  </style>
</head>
<body>

<h2 style="font-size:20px;margin:12px 0 0 12px;">Quezon City Multi-stop Travel Map</h2>
<div class="container">
  <div id="info-panel">
    <div class="reset-btn" id="resetBtn">Reset Map</div>
    <!-- Filter UI -->
    <div style="margin:12px 0;">
      <label for="categoryFilter">Filter by Category:</label>
      <select id="categoryFilter" style="padding:6px;">
        <option value="">All</option>
        <?php
          // Dynamically generate category options based on locations in DB
          $cat_sql = "SELECT DISTINCT category FROM locations";
          $cat_result = $conn->query($cat_sql);
          if ($cat_result && $cat_result->num_rows > 0) {
            while ($cat_row = $cat_result->fetch_assoc()) {
              $cat = htmlspecialchars($cat_row['category']);
              echo "<option value=\"$cat\">".ucfirst($cat)."</option>";
            }
          }
        ?>
      </select>
    </div>
    <!-- Search UI -->
    <div style="margin:12px 0;">
      <input type="text" id="searchInput" placeholder="Search for a place..." style="width:70%;padding:6px;">
      <button id="searchBtn" style="padding:6px 12px;">Search</button>
    </div>
    <div id="searchResults" style="margin-bottom:10px;"></div>
    <h3>Selected Places</h3>
    <div id="selected-places-list">
      <p>No places selected yet.</p>
    </div>
    <hr>
    <h3>Place Info</h3>
    <div id="place-details">
      <p>Click a marker to see details here.</p>
    </div>
  </div>
  <div id="map"></div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
<script>
var map = L.map('map').setView([14.6760, 121.0437], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors'
}).addTo(map);

var startPoint = null;
var routingControl = null;
var selectedWaypoints = []; 

// Custom icons
var icons = {
  tourist: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png', iconSize: [30, 30]}),
  cafe: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/2965/2965567.png', iconSize: [30, 30]}),
  park: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/854/854878.png', iconSize: [30, 30]}),
  shopping: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/1077/1077035.png', iconSize: [30, 30]}),
  user: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/64/64113.png', iconSize: [30, 30]})
};

// Enhanced Stops panel
var stopsControl = L.control({position: 'topright'});
stopsControl.onAdd = function(map) {
  this._div = L.DomUtil.create('div', 'stops-control');
  this.update();
  return this._div;
};
stopsControl.update = function(legs) {
  if (!selectedWaypoints.length || selectedWaypoints.length === 1) {
    this._div.innerHTML = "<b>Stops:</b><br><span style='color:#888;'>No stops selected yet.</span>";
    return;
  }
  var html = "<b>Stops:</b><ul class='stops-list'>";
  var cumulativeTime = 0;
  for (var i = 1; i < selectedWaypoints.length; i++) {
    var name = selectedWaypoints[i].markerName || "Unnamed";
    var distance = "", time = "", cumTime = "";
    if (legs && legs[i-1]) {
      distance = (legs[i-1].distance / 1000).toFixed(2) + " km";
      time = Math.round(legs[i-1].time / 60) + " mins";
      cumulativeTime += legs[i-1].time;
      cumTime = Math.round(cumulativeTime / 60) + " mins total";
    }
    html += `<li>
      <span>${i}. ${name}</span>
      <span class="stop-info">${distance} / ETA: ${time} (${cumTime})</span>
    </li>`;
  }
  html += "</ul>";
  this._div.innerHTML = html;
};
stopsControl.addTo(map);

// --- Legend control inside map, left side ---
var legendControl = L.control({position: 'topleft'});
legendControl.onAdd = function(map) {
  var div = L.DomUtil.create('div', 'legend');
  div.innerHTML = `
    <span class="legend-title">Legend</span>
    <ul class="legend-list">
      <li><img src="https://cdn-icons-png.flaticon.com/512/684/684908.png"> Landmarks and Parks</li>
      <li><img src="https://cdn-icons-png.flaticon.com/512/2965/2965567.png"> Foods and Tambay Spots</li>
      <li><img src="https://cdn-icons-png.flaticon.com/512/854/854878.png"> Arts and Culture</li>
      <li><img src="https://cdn-icons-png.flaticon.com/512/1077/1077035.png"> Malls and Entertainment</li>
      <li><img src="https://cdn-icons-png.flaticon.com/512/64/64113.png"> Your Location</li>
    </ul>
  `;
  return div;
};
legendControl.addTo(map);

function updateRoute(legs) {
  stopsControl.update(legs);
  var cumulativeTime = 0;
  for (var i = 1; i < selectedWaypoints.length; i++) {
    let marker = selectedWaypoints[i].marker;
    if (marker && legs && legs[i-1]) {
      let dist = (legs[i-1].distance / 1000).toFixed(2) + " km";
      let time = Math.round(legs[i-1].time / 60) + " mins";
      cumulativeTime += legs[i-1].time;
      marker.bindPopup(`<b>${selectedWaypoints[i].markerName}</b><br>${dist} / ETA: ${time} (Total: ${Math.round(cumulativeTime/60)} mins)`);
      selectedWaypoints[i].distance = dist;
    }
  }
}

// --- Fix for route lapping/overlapping ---
// Remove duplicate waypoints before updating route
function dedupeWaypoints() {
  const seen = {};
  selectedWaypoints = selectedWaypoints.filter((wp, idx) => {
    const key = wp.latLng.lat + ',' + wp.latLng.lng;
    if (seen[key]) return false;
    seen[key] = true;
    return true;
  });
}

// --- Fit route to map ---
function fitRouteToMap() {
  if (routingControl && routingControl._routes && routingControl._routes.length > 0) {
    var route = routingControl._routes[0];
    var bounds = L.latLngBounds([]);
    route.coordinates.forEach(function(coord) {
      bounds.extend([coord.lat, coord.lng]);
    });
    map.fitBounds(bounds, {padding: [40, 40]});
  }
}

// --- Get user location ---
if (navigator.geolocation) {
  navigator.geolocation.getCurrentPosition(function(position) {
    startPoint = L.latLng(position.coords.latitude, position.coords.longitude);
    var startMarker = L.marker(startPoint, {icon: icons.user}).addTo(map)
      .bindPopup("<b>Your Location</b>").openPopup();
    map.setView(startPoint, 13);

    selectedWaypoints.push({latLng: startPoint, markerName: "Your Location", marker: startMarker});
    routingControl = L.Routing.control({
      waypoints: selectedWaypoints.map(wp => wp.latLng),
      routeWhileDragging: true,
      addWaypoints: false,
      fitSelectedRoutes: true,
      lineOptions: {extendToWaypoints: true, missingRouteTolerance: 0.1}
    }).addTo(map);

    routingControl.on('routesfound', function(e) {
      updateRoute(e.routes[0].legs);
      fitRouteToMap();
    });
  });
}

// 📌 Locations added by Admin (from DB)
var locations = <?php
  $loc_sql = "SELECT name, category, description, latitude, longitude, photo FROM locations";
  $loc_result = $conn->query($loc_sql);
  $loc_data = [];
  if ($loc_result && $loc_result->num_rows > 0) {
      while ($row = $loc_result->fetch_assoc()) {
          $loc_data[] = $row;
      }
  }
  echo json_encode($loc_data);
?>;

// Store markers for filtering
var locationMarkers = [];

function addMarkers(filteredLocations) {
  // Remove existing markers
  locationMarkers.forEach(function(obj) {
    map.removeLayer(obj.marker);
  });
  locationMarkers = [];
  filteredLocations.forEach(function(place) {
    var icon = icons[place.category] || icons.tourist;
    var marker = L.marker([place.latitude, place.longitude], {icon: icon}).addTo(map)
      .bindPopup(`<b>${place.name}</b><br>${place.description || ''}`);
    marker.on('click', function() {
      var dist = "";
      for (var i = 1; i < selectedWaypoints.length; i++) {
        if (selectedWaypoints[i].markerName === place.name && selectedWaypoints[i].distance) {
          dist = selectedWaypoints[i].distance;
          break;
        }
      }
      if (!dist && startPoint) {
        var latlng = marker.getLatLng();
        dist = map.distance(startPoint, latlng);
        dist = (dist / 1000).toFixed(2) + " km (straight line)";
      }
      var details = `
        <h3>${place.name}</h3>
        <span class="category">${place.category}</span>
        <div class="desc">
          ${place.description || 'No description.'}
          <br><b>Distance:</b> ${dist}
        </div>
        ${place.photo && place.photo !== "" ? `<img src='uploads/${place.photo}' alt='${place.name}'>` : ""}
      `;
      var detailsDiv = document.getElementById('place-details');
      detailsDiv.innerHTML = details;

      if (!startPoint) return;
      // --- Fix: Prevent duplicate/lapping waypoints ---
      if (!selectedWaypoints.some(wp => wp.latLng.lat === marker.getLatLng().lat && wp.latLng.lng === marker.getLatLng().lng)) {
        selectedWaypoints.splice(1, 0, {latLng: marker.getLatLng(), markerName: place.name, marker: marker});
        dedupeWaypoints();
        routingControl.setWaypoints(selectedWaypoints.map(wp => wp.latLng));
        updateSelectedPlacesList(); // <-- update list after adding
      }
    });
    locationMarkers.push({marker, place});
  });
}

// Initial marker load
addMarkers(locations);

// --- Filter functionality ---
document.getElementById('categoryFilter').addEventListener('change', function() {
  var cat = this.value;
  if (!cat) {
    addMarkers(locations);
  } else {
    var filtered = locations.filter(function(place) {
      return place.category === cat;
    });
    addMarkers(filtered);
  }
});

// --- Search functionality ---
document.getElementById('searchBtn').addEventListener('click', function() {
  var query = document.getElementById('searchInput').value.trim().toLowerCase();
  var resultsDiv = document.getElementById('searchResults');
  if (!query) {
    resultsDiv.innerHTML = "";
    return;
  }
  var results = locations.filter(function(place) {
    return place.name.toLowerCase().includes(query) ||
           (place.description && place.description.toLowerCase().includes(query)) ||
           (place.category && place.category.toLowerCase().includes(query));
  });
  if (results.length === 0) {
    resultsDiv.innerHTML = "<span style='color:#888;'>No places found.</span>";
    return;
  }
  var html = "<ul style='list-style:none;padding:0;'>";
  results.forEach(function(place, idx) {
    html += `<li style="margin-bottom:8px;">
      <b>${place.name}</b> <span style="color:#555;">(${place.category})</span>
      <button onclick="zoomToPlace(${locations.findIndex(p => p.name === place.name)})" style="margin-left:8px;">Show</button>
    </li>`;
  });
  html += "</ul>";
  resultsDiv.innerHTML = html;
});

// --- Zoom to place and open popup ---
window.zoomToPlace = function(idx) {
  var place = locations[idx];
  if (!place) return;
  var latlng = L.latLng(place.latitude, place.longitude);
  map.setView(latlng, 16);
  // Find marker and open popup
  map.eachLayer(function(layer) {
    if (layer instanceof L.Marker && layer.getLatLng().equals(latlng)) {
      layer.openPopup();
    }
  });
};

// --- Selected Places List UI ---
function updateSelectedPlacesList() {
  var listDiv = document.getElementById('selected-places-list');
  if (selectedWaypoints.length <= 1) {
    listDiv.innerHTML = "<p>No places selected yet.</p>";
    return;
  }
  var html = "<ul style='list-style:none;padding:0;'>";
  for (var i = 1; i < selectedWaypoints.length; i++) {
    var wp = selectedWaypoints[i];
    html += `<li style="margin-bottom:8px;">
      <b>${wp.markerName}</b>
      <button onclick="removeSelectedPlace(${i})" style="margin-left:8px;">Remove</button>
    </li>`;
  }
  html += "</ul>";
  listDiv.innerHTML = html;
}

// --- Remove selected place from route ---
window.removeSelectedPlace = function(idx) {
  if (idx > 0 && idx < selectedWaypoints.length) {
    selectedWaypoints.splice(idx, 1);
    routingControl.setWaypoints(selectedWaypoints.map(wp => wp.latLng));
    stopsControl.update();
    updateSelectedPlacesList();
  }
};

// --- Reset button ---
document.getElementById('resetBtn').addEventListener('click', function() {
  for (var i = selectedWaypoints.length - 1; i > 0; i--) {
    var wp = selectedWaypoints[i];
    if (wp.marker) map.removeLayer(wp.marker);
    selectedWaypoints.splice(i, 1);
  }
  routingControl.setWaypoints([startPoint]);
  stopsControl.update();
  updateSelectedPlacesList(); // <-- update list after reset
  document.getElementById('place-details').innerHTML = "<p>Click a marker to see details here.</p>";
});
</script>
</body>
</html>
