<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Travel Map - Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f9f9f9; }
        header { background: #2c3e50; color: white; padding: 15px; text-align: center; }
        h1 { margin: 0; font-size: 24px; }
        .container { padding: 20px; }
        .places { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }
        .card:hover { transform: scale(1.03); }
        .card img { width: 100%; height: 150px; object-fit: cover; }
        .card-content { padding: 15px; }
        .card-content h3 { margin: 0 0 10px; }
        .card-content p { font-size: 14px; color: #555; }
        .card a {
            display: inline-block;
            margin-top: 10px;
            text-decoration: none;
            color: #3498db;
            font-weight: bold;
        }
        /* Categories */
        .categories {
            display: flex;
            justify-content: space-around;
            align-items: center;
            background: rgba(255,255,255,0.9);
            padding: 10px;
            border-radius: 12px;
            margin: 15px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            flex-wrap: wrap;
        }
        .category-card {
            text-align: center;
            flex: 1;
            min-width: 100px;
            cursor: pointer;
            transition: transform 0.2s;
            margin: 5px;
        }
        .category-card:hover { transform: scale(1.05); }
        .category-card img {
            width: 40px; 
            height: 40px;
            margin-bottom: 5px;
        }
        .category-card span {
            display: block;
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <header>
        <h1>🌍 Welcome to Quezon City Travel Guide</h1>
    </header>

    <!-- Category Navigation -->
    <div class="categories">
        <div class="category-card" onclick="window.location.href='homepage.php'">
            <img src="https://cdn-icons-png.flaticon.com/512/1533/1533179.png" alt="Recent">
            <span>Most Recent</span>
        </div>
        <div class="category-card" onclick="window.location.href='homepage.php?category=landmark'">
            <img src="https://cdn-icons-png.flaticon.com/512/1533/1533179.png" alt="Landmarks">
            <span>Landmarks & Parks</span>
        </div>
        <div class="category-card" onclick="window.location.href='homepage.php?category=food'">
            <img src="https://cdn-icons-png.flaticon.com/512/2965/2965567.png" alt="Foods">
            <span>Foods & Tambay Spots</span>
        </div>
        <div class="category-card" onclick="window.location.href='homepage.php?category=arts'">
            <img src="https://cdn-icons-png.flaticon.com/512/1076/1076787.png" alt="Arts">
            <span>Arts & Culture</span>
        </div>
        <div class="category-card" onclick="window.location.href='homepage.php?category=mall'">
            <img src="https://cdn-icons-png.flaticon.com/512/1250/1250615.png" alt="Malls">
            <span>Malls & Entertainment</span>
        </div>
    </div>

    <div class="container">
        <?php
        // Map category keys to display names
        $categoryNames = [
            "landmark" => "Landmarks & Parks",
            "food" => "Foods & Tambay Spots",
            "arts" => "Arts & Culture",
            "mall" => "Malls & Entertainment"
        ];

        $where = "WHERE latitude BETWEEN 14.5300 AND 14.8200 
                  AND longitude BETWEEN 121.0000 AND 121.1500"; // ✅ QC bounding box
        $heading = "Most Recent Places";

        if (isset($_GET['category']) && array_key_exists($_GET['category'], $categoryNames)) {
            $category = $_GET['category'];
            $where .= " AND category = '$category'";
            $heading = $categoryNames[$category];
        }

        $sql = "SELECT * FROM locations $where ORDER BY created_at DESC";
        $result = $conn->query($sql);

        echo "<h2>Showing: $heading</h2>";
        ?>
        <div class="places">
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<div class='card'>";
                    if ($row['photo']) {
                        echo "<img src='uploads/{$row['photo']}' alt='".htmlspecialchars($row['name'])."'>";
                    } else {
                        echo "<img src='https://via.placeholder.com/300x150?text=No+Image' alt='No photo'>";
                    }
                    echo "<div class='card-content'>";
                    echo "<h3>".htmlspecialchars($row['name'])."</h3>";
                    echo "<p>" . htmlspecialchars(substr($row['description'], 0, 80)) . "...</p>";
                    echo "<a href='index.php?lat={$row['latitude']}&lng={$row['longitude']}'>View on Map</a>";
                    echo "</div></div>";
                }
            } else {
                echo "<p>No places found for this category.</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>
