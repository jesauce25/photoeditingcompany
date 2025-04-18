<?php
/**
 * Fetch Companies API
 * Retrieves company records from the database
 */
session_start();
include("../includes/db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'Failed to retrieve companies',
    'data' => []
];

// Get filter parameters
$country = isset($_GET['country']) ? $_GET['country'] : '';
$year = isset($_GET['year']) ? $_GET['year'] : '';
$monthYear = isset($_GET['monthYear']) ? $_GET['monthYear'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the base query
$query = "SELECT * FROM tbl_companies WHERE 1=1";
$params = [];
$types = "";

// Apply filters
if (!empty($country)) {
    $query .= " AND country = ?";
    $params[] = $country;
    $types .= "s";
}

if (!empty($year)) {
    $query .= " AND YEAR(date_signed_up) = ?";
    $params[] = $year;
    $types .= "s";
}

if (!empty($monthYear)) {
    $query .= " AND DATE_FORMAT(date_signed_up, '%Y-%m') = ?";
    $params[] = $monthYear;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (company_name LIKE ? OR email LIKE ? OR person_in_charge LIKE ? OR address LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ssss";
}

// Add order by
$query .= " ORDER BY date_created DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);

// Bind parameters if they exist
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    $companies = [];

    while ($row = $result->fetch_assoc()) {
        // Format logo path
        if (!empty($row['logo_path'])) {
            $row['logo_path'] = '../' . $row['logo_path'];
        } else {
            $row['logo_path'] = '../dist/img/company-placeholder.png';
        }

        // Format date for display
        $row['formatted_date'] = date('M d, Y', strtotime($row['date_signed_up']));

        $companies[] = $row;
    }

    $response = [
        'status' => 'success',
        'message' => 'Companies retrieved successfully',
        'data' => $companies,
        'total' => count($companies)
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

// Close connections
$stmt->close();
$conn->close();
?>