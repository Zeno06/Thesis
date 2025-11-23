<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include '../db_connect.php';

// Get all released vehicles (is_released = 1), exclude archived (is_released = 2)
$query = "SELECT 
    acquisition_id,
    plate_number,
    vehicle_model,
    year_model,
    color,
    make,
    variant,
    fuel_type,
    odometer,
    body_type,
    transmission,
    spare_key,
    exterior_photo,
    dashboard_photo,
    hood_photo,
    interior_photo,
    trunk_photo,
    spare_tires,
    complete_tools,
    original_plate,
    complete_documents,
    selling_price,
    acquired_price,
    released_at
FROM vehicle_acquisition 
WHERE is_released = 1 
ORDER BY released_at DESC 
LIMIT 20";

$result = $conn->query($query);

$vehicles = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Mask plate number (show only last digit)
        $plateNumber = $row['plate_number'];
        $maskedPlate = str_repeat('*', strlen($plateNumber) - 1) . substr($plateNumber, -1);
        
        $vehicles[] = [
            'id' => $row['acquisition_id'],
            'masked_plate' => $maskedPlate,
            'model' => $row['vehicle_model'],
            'year' => $row['year_model'],
            'color' => $row['color'],
            'make' => $row['make'],
            'variant' => $row['variant'],
            'fuel_type' => $row['fuel_type'],
            'odometer' => $row['odometer'],
            'mileage' => $row['odometer'], 
            'body_type' => $row['body_type'],
            'transmission' => $row['transmission'],
            'spare_key' => $row['spare_key'],
            'exterior_photo' => $row['exterior_photo'],
            'dashboard_photo' => $row['dashboard_photo'],
            'hood_photo' => $row['hood_photo'],
            'interior_photo' => $row['interior_photo'],
            'trunk_photo' => $row['trunk_photo'],
            'spare_tires' => $row['spare_tires'],
            'complete_tools' => $row['complete_tools'],
            'original_plate' => $row['original_plate'],
            'complete_documents' => $row['complete_documents'],
            'selling_price' => $row['selling_price'],
            'acquired_price' => $row['acquired_price'],
            'released_at' => $row['released_at']
        ];
    }
}

echo json_encode([
    'success' => true,
    'vehicles' => $vehicles,
    'count' => count($vehicles)
]);

$conn->close();
?>