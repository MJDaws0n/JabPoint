<?php
$routesDir = $fileRoot.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR;

header("Access-Control-Allow-Origin: *");

// Allow specific methods (like GET, POST, etc.)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Allow certain headers in the request (like Content-Type, Authorization, etc.)
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Home
$router->addRoute('get', '/', function() use($routesDir) {
    include_once $routesDir.'home.html';
});

// Login 
$router->addRoute('get', '/login', function() use($routesDir) {
    include_once $routesDir.'login.html';
});
// Login API
$router->addRoute('post', '/login', function() use($routesDir) {
    // Check if the post data contains 'username' and 'password'
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
    } else {
        echo json_encode(array("status" => "error", "message" => "Invalid data"));
        return;
    }

    $url = "https://www.pinpointlearning.co.uk/Student_login.php";
    $data = "First=$username&Second=$password";

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            "content-type: application/x-www-form-urlencoded",
        ],
    ];

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);

    // Get the headers from the response
    $headers = curl_getinfo($ch, CURLINFO_HEADER_OUT);
    curl_close($ch);
    
    $response = substr($response, 5469, -565);

    if($response === "<div class=\"alert alert-danger\">Sorry wrong username and password combination entered.</div>"){
        echo json_encode(array("status" => "error", "message" => "Incorrect username / password combination."));
    } else if ($response === "You are connected") {
        // Extract PHPSESSID from the response headers
        print_r($headers);
        // $session_id = $matches[1] ?? null;

        echo json_encode(array("status" => "success", "session" => 'f'));
    } else {
        echo json_encode(array("status" => "error", "message" => "An error has occurred. Pinpoint has had an update breaking out system. Please try again later or use the original site."));
    }
});

// Dashboard 
$router->addRoute('get', '/dashboard', function() use($routesDir) {
    include_once $routesDir.'dashboard.html';
});