bro add that here 

  <?php include 'db_connect.php'; ?>
  <!DOCTYPE html>
  <html>
  <head>
    <title>QC Multi-stop Travel Map</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <style>
      body {
        margin: 0; padding: 0;
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #f4f7fb;
      }
      h2 {
        font-size: 22px;
        margin: 12px 0 0 12px;
        color: #2c3e50;
      }
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
        border-right: 1px solid #ddd;
        padding: 15px;
        box-sizing: border-box;
        overflow-y: auto;
        box-shadow: 2px 0 8px rgba(0,0,0,0.05);
      }
      #map {
        flex: 1;
        height: 100%;
        width: 65%;
        position: relative;
      }
      .section-title {
        margin: 15px 0 8px;
        font-size: 16px;
        font-weight: bold;
        color: #34495e;
        border-bottom: 1px solid #eaeaea;
        padding-bottom: 4px;
      }
      .reset-btn {
        display: inline-block;
        background: #e74c3c;
        color: #fff;
        padding: 8px 18px;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        margin: 8px 0 12px;
        box-shadow: 0 2px 8px rgba(44,62,80,0.1);
        border: none;
        transition: background 0.2s;
      }
      .reset-btn:hover { background: #c0392b; }
      select, input[type="text"], button {
        border: 1px solid #ccc;
        border-radius: 5px;
        padding: 6px 10px;
        font-size: 14px;
      }
      button {
        background: #3498db;
        color: white;
        cursor: pointer;
        transition: background 0.2s;
      }
      button:hover { background: #2980b9; }
      #searchResults ul {
        list-style: none;
        padding: 0;
        margin: 5px 0;
      }
      #searchResults li {
        margin-bottom: 6px;
        padding: 5px;
        border-bottom: 1px solid #eee;
      }
      #selected-places-list ul {
        list-style: none;
        padding: 0;
        margin: 0;
      }
      #selected-places-list li {
        margin-bottom: 8px;
        background: #f9f9f9;
        padding: 6px 10px;
        border-radius: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      #place-details {
        background: #f9f9f9;
        border-radius: 8px;
        padding: 10px;
        margin-top: 8px;
        box-shadow: inset 0 1px 4px rgba(0,0,0,0.05);
      }
      #place-details h3 {
        margin: 0 0 4px;
        color: #2c3e50;
      }
      #place-details span {
        font-size: 13px;
        color: #7f8c8d;
      }
      .book-btn {
        display: inline-block;
        margin-top: 10px;
        margin-right: 8px;
        padding: 8px 14px;
        border: none;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.2s;
      }
      .book-driver {
        background: #27ae60;
        color: #fff;
      }
      .book-driver:hover { background: #1e8449; }
      .rent-car {
        background: #f39c12;
        color: #fff;
      }
      .rent-car:hover { background: #d68910; }
      .stops-control, .legend {
        background: #fff;
        border: 1px solid #e3eafc;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 14px;
        box-shadow: 0 2px 12px rgba(44,62,80,0.08);
        min-width: 230px;
        width: 260px;
        margin-top: 10px;
      }
      .stops-control b, .legend-title {
        font-size: 15px;
        color: #2a4d8f;
        font-weight: bold;
        margin-bottom: 8px;
        display: block;
      }
      .stops-list, .legend-list {
        list-style: none;
        padding: 0;
        margin: 0;
      }
      .stops-list li, .legend-list li {
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        font-size: 13px;
      }
      .legend-list li {
        justify-content: flex-start;
        align-items: center;
      }
      .legend-list img {
        width: 22px; height: 22px;
        margin-right: 8px;
        border-radius: 4px;
        border: 1px solid #eaeaea;
        background: #fff;
      }
      @media (max-width: 900px) {
        .container { flex-direction: column; height: auto; }
        #info-panel { width: 100%; max-width: none; min-width: 0; }
        #map { width: 100%; height: 400px; }
      }
    </style>
  </head>
  <body>

  <h2>Quezon City Multi-stop Travel Map</h2>
  <div class="container">
    <div id="info-panel">
      <button class="reset-btn" id="resetBtn">Reset Map</button>

      <div class="section-title">Filter</div>
      <label for="categoryFilter">Category:</label>
      <select id="categoryFilter">
        <option value="">All</option>
        <?php
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

      <div class="section-title">Search</div>
      <input type="text" id="searchInput" placeholder="Search for a place..." style="width:65%;">
      <button id="searchBtn">Search</button>
      <div id="searchResults"></div>

      <div class="section-title">Selected Places</div>
      <div id="selected-places-list"><p>No places selected yet.</p></div>

      <div class="section-title">Place Info</div>
      <div id="place-details"><p>Click a marker to see details here.</p></div>
    </div>

    <div id="map"></div>
  </div>

  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
  <script src="https://unpkg.com/@turf/turf/turf.min.js"></script>

  <script>
  var map = L.map('map').setView([14.6760, 121.0437], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
    attribution: '© OpenStreetMap contributors' 
  }).addTo(map);

  var startPoint = null, routingControl = null, selectedWaypoints = [], locationMarkers = [];

  // Custom icons
  var icons = {
    tourist: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png', iconSize: [30, 30]}),
    cafe: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/2965/2965567.png', iconSize: [30, 30]}),
    park: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/854/854878.png', iconSize: [30, 30]}),
    shopping: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/1077/1077035.png', iconSize: [30, 30]}),
    user: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/64/64113.png', iconSize: [30, 30]})
  };

  // Stops panel
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
      var name = selectedWaypoints[i].markerName || "Unnamed", distance = "", time = "", cumTime = "";
      if (legs && legs[i-1]) {
        distance = (legs[i-1].distance / 1000).toFixed(2) + " km";
        time = Math.round(legs[i-1].time / 60) + " mins";
        cumulativeTime += legs[i-1].time; 
        cumTime = Math.round(cumulativeTime / 60) + " mins total";
      }
      html += `<li><span>${i}. ${name}</span><span>${distance} / ${time} (${cumTime})</span></li>`;
    }
    html += "</ul>"; 
    this._div.innerHTML = html;
  };
  stopsControl.addTo(map);

  // Legend
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
      </ul>`;
    return div;
  };
  legendControl.addTo(map);

  // Locations from DB
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

  // Load QC boundary
  fetch("https://nominatim.openstreetmap.org/search.php?q=Quezon+City&polygon_geojson=1&format=json")
    .then(res => res.json()).then(data => {
      let geo = data[0].geojson;
      let qcBoundary = L.geoJSON(geo, { style: { color: "red", weight: 2, fill: false } }).addTo(map);
      map.fitBounds(qcBoundary.getBounds());
      let qcArea = turf.polygon(geo.coordinates);

      function addMarkers(filteredLocations) {
        locationMarkers.forEach(obj => map.removeLayer(obj.marker));
        locationMarkers = [];
        filteredLocations.forEach(function(place) {
          var icon = icons[place.category] || icons.tourist;
          var point = turf.point([place.longitude, place.latitude]);
          if (!turf.booleanPointInPolygon(point, qcArea)) return;
          
          var marker = L.marker([place.latitude, place.longitude], {icon: icon}).addTo(map)
            .bindPopup(`<b>${place.name}</b><br>${place.description || ''}`);

          // Enhanced place info with two booking buttons
          marker.on('click', function() {
            var detailsDiv = document.getElementById('place-details');
            detailsDiv.innerHTML = `
              <h3>${place.name}</h3>
              <span>${place.category}</span><br>
              <p>${place.description || ''}</p>
              <button class="book-btn book-driver" onclick="alert('Booking with driver at ${place.name}')">
                🚖 Book with Driver
              </button>
              <button class="book-btn rent-car" onclick="alert('Rent without driver at ${place.name}')">
                🚗 Rent Without Driver
              </button>
            `;

            // Add to route if start point exists
            if (!startPoint) return;
            if (!selectedWaypoints.some(wp => wp.latLng.lat === marker.getLatLng().lat &&
                                            wp.latLng.lng === marker.getLatLng().lng)) {
              selectedWaypoints.splice(1, 0, {
                latLng: marker.getLatLng(), 
                markerName: place.name, 
                marker: marker
              });
              dedupeWaypoints();
              routingControl.setWaypoints(selectedWaypoints.map(wp => wp.latLng));
              updateSelectedPlacesList();
            }
          });
          locationMarkers.push({marker, place});
        });
      }
      addMarkers(locations);

      // Category filter
      document.getElementById('categoryFilter').addEventListener('change', function() {
        var cat = this.value;
        addMarkers(!cat ? locations : locations.filter(p => p.category === cat));
      });

      // Search functionality
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
        var html = "<ul>";
        results.forEach(function(place) {
          html += `<li>
            <b>${place.name}</b> <span style="color:#555;">(${place.category})</span>
            <button onclick="zoomToPlace(${place.latitude}, ${place.longitude}, '${place.name}')" style="margin-left:8px;">
              Show
            </button>
          </li>`;
        });
        html += "</ul>"; 
        resultsDiv.innerHTML = html;
      });
    });

  // Route management functions
  function dedupeWaypoints() {
    const seen = {}; 
    selectedWaypoints = selectedWaypoints.filter(wp => {
      const key = wp.latLng.lat + ',' + wp.latLng.lng; 
      if (seen[key]) return false; 
      seen[key] = true; 
      return true;
    });
  }

  function updateSelectedPlacesList() {
    var listDiv = document.getElementById('selected-places-list');
    if (selectedWaypoints.length <= 1) { 
      listDiv.innerHTML = "<p>No places selected yet.</p>"; 
      return; 
    }
    var html = "<ul>";
    for (var i = 1; i < selectedWaypoints.length; i++) {
      var wp = selectedWaypoints[i];
      html += `<li>
        <b>${wp.markerName}</b>
        <button onclick="removeSelectedPlace(${i})">Remove</button>
      </li>`;
    }
    html += "</ul>"; 
    listDiv.innerHTML = html;
  }

  // Global functions for UI interactions
  window.removeSelectedPlace = function(idx) {
    if (idx > 0 && idx < selectedWaypoints.length) {
      selectedWaypoints.splice(idx, 1); 
      routingControl.setWaypoints(selectedWaypoints.map(wp => wp.latLng));
      stopsControl.update(); 
      updateSelectedPlacesList();
    }
  };

  window.zoomToPlace = function(lat, lng, name) {
    map.setView([lat, lng], 15);
    // Highlight the marker
    locationMarkers.forEach(obj => {
      if (obj.place.latitude === lat && obj.place.longitude === lng) {
        obj.marker.openPopup();
      }
    });
  };

  // Reset functionality
  document.getElementById('resetBtn').addEventListener('click', function() {
    for (var i = selectedWaypoints.length - 1; i > 0; i--) {
      selectedWaypoints.splice(i, 1);
    }
    if (routingControl) {
      routingControl.setWaypoints([startPoint]); 
    }
    stopsControl.update(); 
    updateSelectedPlacesList();
    document.getElementById('place-details').innerHTML = "<p>Click a marker to see details here.</p>";
  });

  // User location detection and routing setup
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
      startPoint = L.latLng(position.coords.latitude, position.coords.longitude);
      var startMarker = L.marker(startPoint, {icon: icons.user})
        .addTo(map)
        .bindPopup("<b>Your Location</b>")
        .openPopup();
      
      map.setView(startPoint, 13);
      selectedWaypoints.push({
        latLng: startPoint, 
        markerName: "Your Location", 
        marker: startMarker
      });
      
      routingControl = L.Routing.control({
        waypoints: selectedWaypoints.map(wp => wp.latLng), 
        routeWhileDragging: true, 
        addWaypoints: false,
        fitSelectedRoutes: true, 
        lineOptions: {
          extendToWaypoints: true, 
          missingRouteTolerance: 0.1
        }
      }).addTo(map);
      
      routingControl.on('routesfound', function(e) { 
        stopsControl.update(e.routes[0].legs); 
      });
    }, function(error) {
      console.error("Geolocation error:", error);
      alert("Unable to get your location. Using default QC view.");
    });
  }
  </script>
  </body>
  </html>
