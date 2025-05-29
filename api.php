<?php
// Set headers for JSON content type and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // IMPORTANT: For production, replace '*' with your specific frontend origin (e.g., 'http://localhost:3000', 'https://your-frontend-domain.com')
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS requests (preflight for CORS)
// This is necessary for some complex cross-origin requests to determine allowed methods and headers.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection parameters
// Replace with your actual database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kapamalq_db"; // Replace with your database name

// Establish a new database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    // If connection fails, return a JSON error message and exit
    echo json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Get the HTTP request method (GET, POST, PUT, DELETE)
$method = $_SERVER['REQUEST_METHOD'];
// Decode the JSON input from the request body for POST, PUT, DELETE requests
$data = json_decode(file_get_contents('php://input'), true);

// Use a switch statement to handle different HTTP methods
switch ($method) {
    case 'GET':
        // Handle GET requests to retrieve documents
        if (isset($_GET['id'])) {
            // Fetch a single document by ID for editing
            $id = $_GET['id'];

            $stmt = $conn->prepare("SELECT id, received_by, document_title, document_date, control_no, is_favorite FROM documents WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $document = $result->fetch_assoc();
                echo json_encode(["success" => true, "data" => $document]);
            } else {
                echo json_encode(["success" => false, "message" => "Document not found."]);
            }
            $stmt->close();
        } else {
            // Fetch all documents, or filter by is_favorite if specified
            $sql = "SELECT id, received_by, document_title, document_date, control_no, is_favorite FROM documents";
            $params = [];
            $types = "";

            if (isset($_GET['is_favorite'])) {
                // Sanitize and validate is_favorite filter
                $is_favorite_filter = (int)$_GET['is_favorite']; // Cast to integer (0 or 1)
                $sql .= " WHERE is_favorite = ?";
                $params[] = $is_favorite_filter;
                $types .= "i";
            }

            $sql .= " ORDER BY document_date DESC"; // Always order by date

            $stmt = $conn->prepare($sql);

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result) {
                $documents = [];
                while($row = $result->fetch_assoc()) {
                    $documents[] = $row;
                }
                echo json_encode(["success" => true, "data" => $documents]);
            } else {
                echo json_encode(["success" => false, "message" => "Error fetching documents: " . $conn->error]);
            }
            $stmt->close();
        }
        break;

    case 'POST':
        // Handle POST requests to add a new document
        if ($data) {
            $receivedBy = $data['received_by'] ?? null;
            $documentTitle = $data['document_title'] ?? null;
            $documentDate = $data['document_date'] ?? null;
            $controlNo = $data['control_no'] ?? null;
            $isFavorite = $data['is_favorite'] ?? 0; // Default to 0 if not provided

            if (!$receivedBy || !$documentTitle || !$documentDate || !$controlNo) {
                echo json_encode(["success" => false, "message" => "Missing required fields for adding a document."]);
                break;
            }

            $stmt = $conn->prepare("INSERT INTO documents (received_by, document_title, document_date, control_no, is_favorite) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $receivedBy, $documentTitle, $documentDate, $controlNo, $isFavorite);

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "New record created successfully", "id" => $conn->insert_id]);
            } else {
                echo json_encode(["success" => false, "message" => "Error creating record: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "Invalid data for POST request."]);
        }
        break;

    case 'PUT':
        // Handle PUT requests to update an existing document
        if ($data && isset($data['id'])) {
            $id = $data['id'];
            $receivedBy = $data['received_by'] ?? null;
            $documentTitle = $data['document_title'] ?? null;
            $documentDate = $data['document_date'] ?? null;
            $controlNo = $data['control_no'] ?? null;
            $isFavorite = $data['is_favorite'] ?? null; // Can be null if only updating other fields

            // Build the SET clause dynamically for partial updates, or require all for full update
            $updateFields = [];
            $updateValues = [];
            $updateTypes = "";

            if ($receivedBy !== null) {
                $updateFields[] = "received_by = ?";
                $updateValues[] = $receivedBy;
                $updateTypes .= "s";
            }
            if ($documentTitle !== null) {
                $updateFields[] = "document_title = ?";
                $updateValues[] = $documentTitle;
                $updateTypes .= "s";
            }
            if ($documentDate !== null) {
                $updateFields[] = "document_date = ?";
                $updateValues[] = $documentDate;
                $updateTypes .= "s";
            }
            if ($controlNo !== null) {
                $updateFields[] = "control_no = ?";
                $updateValues[] = $controlNo;
                $updateTypes .= "s";
            }
            if ($isFavorite !== null) { // Add is_favorite to update
                $updateFields[] = "is_favorite = ?";
                $updateValues[] = (int)$isFavorite; // Ensure it's an integer
                $updateTypes .= "i";
            }

            if (empty($updateFields)) {
                echo json_encode(["success" => false, "message" => "No fields provided for update."]);
                break;
            }

            $sql = "UPDATE documents SET " . implode(", ", $updateFields) . " WHERE id=?";
            $updateValues[] = $id; // Add ID to the end of values
            $updateTypes .= "i"; // Add 'i' for the ID type

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($updateTypes, ...$updateValues); // Use ... for variadic parameters

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(["success" => true, "message" => "Record updated successfully"]);
                } else {
                    echo json_encode(["success" => false, "message" => "No record found with ID: $id or no changes made."]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "Error updating record: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "Invalid data or missing ID for PUT request."]);
        }
        break;

    case 'DELETE':
        // Handle DELETE requests to remove a document
        if ($data && isset($data['id'])) {
            $id = $data['id'];

            $stmt = $conn->prepare("DELETE FROM documents WHERE id=?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(["success" => true, "message" => "Record deleted successfully"]);
                } else {
                    echo json_encode(["success" => false, "message" => "No record found with ID: $id."]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "Error deleting record: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "Invalid data or missing ID for DELETE request."]);
        }
        break;

    default:
        // Handle unsupported request methods
        echo json_encode(["success" => false, "message" => "Unsupported request method"]);
        break;
}

// Close the database connection at the end of the script execution
$conn->close();
?>