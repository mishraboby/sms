<?php
// Error reporting chalu karein taaki exact error dikhe
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "mysql-1d13f01a-mishraboby-bca5.d.aivencloud.com";
$port = 11828;
$user = "avnadmin";
$pass = "AVNS_blWCD2yG11afE5k8fME"; 
$db   = "defaultdb";

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

if (!@mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT)) {
    echo json_encode(["success" => false, "message" => "DB Connect Error: " . mysqli_connect_error()]);
    exit();
}

// Table create karein agar nahi hai
$tableSql = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roll_no VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    mother_name VARCHAR(100),
    dob DATE NULL,
    gender VARCHAR(10),
    mobile VARCHAR(15) NOT NULL,
    email VARCHAR(100),
    course VARCHAR(20) NOT NULL,
    semester VARCHAR(10),
    admission_year INT DEFAULT 2026,
    address TEXT,
    photo_url VARCHAR(255),
    signature_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $tableSql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rollNo        = trim($_POST['rollNo'] ?? '');
    $name          = trim($_POST['name'] ?? '');
    $fatherName    = trim($_POST['fatherName'] ?? '');
    $motherName    = trim($_POST['motherName'] ?? '');
    $dob           = !empty($_POST['dob']) ? $_POST['dob'] : NULL;
    $gender        = trim($_POST['gender'] ?? '');
    $mobile        = trim($_POST['mobile'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $course        = trim($_POST['course'] ?? '');
    $semester      = trim($_POST['semester'] ?? '');
    $admissionYear = !empty($_POST['admissionYear']) ? (int)$_POST['admissionYear'] : 2026;
    $address       = trim($_POST['address'] ?? '');

    // Required validation
    if (empty($rollNo) || empty($name)) {
        echo json_encode(["success" => false, "message" => "Roll No aur Name zaroori hai!"]);
        exit();
    }

    // Uploads folder ensure karein
    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Photo Upload
    $photoUrl = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoExt = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photoName = 'photo_' . time() . '_' . rand(1000, 9999) . '.' . $photoExt;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoName)) {
            $photoUrl = "uploads/" . $photoName;
        }
    }

    // Signature Upload
    $sigUrl = null;
    if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
        $sigExt = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION);
        $sigName = 'sig_' . time() . '_' . rand(1000, 9999) . '.' . $sigExt;
        if (move_uploaded_file($_FILES['signature']['tmp_name'], $uploadDir . $sigName)) {
            $sigUrl = "uploads/" . $sigName;
        }
    }

    $stmt = $conn->prepare("INSERT INTO students (roll_no, name, father_name, mother_name, dob, gender, mobile, email, course, semester, admission_year, address, photo_url, signature_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Prepare Error: " . $conn->error]);
        exit();
    }

    $stmt->bind_param("ssssssssssisss", $rollNo, $name, $fatherName, $motherName, $dob, $gender, $mobile, $email, $course, $semester, $admissionYear, $address, $photoUrl, $sigUrl);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Student Registered Successfully!"]);
    } else {
        if ($conn->errno === 1062) {
            echo json_encode(["success" => false, "message" => "Ye Roll Number pehle se registered hai!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Execute Error: " . $stmt->error]);
        }
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Only POST method allowed"]);
}

$conn->close();
?>
