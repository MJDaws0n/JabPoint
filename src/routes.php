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
        echo json_encode(["status" => "error", "message" => "Invalid data"]);
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
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    $response = substr($body, 5469, -565);

    if($response === "<div class=\"alert alert-danger\">Sorry wrong username and password combination entered.</div>"){
        echo json_encode(array("status" => "error", "message" => "Incorrect username / password combination."));
    } else if ($response === "You are connected") {
        // Extract PHPSESSID from the response headers
        preg_match('/PHPSESSID=([a-zA-Z0-9]+);/', $header, $matches);
        $session_id = $matches[1] ?? null;

        // Set the session cookie
        if ($session_id) {
            setcookie("session", $session_id, time() + (30 * 24 * 60 * 60), "/", "", false, true);
        }

        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => "An error has occurred. Pinpoint has had an update breaking out system. Please try again later or use the original site."));
    }
});

// Dashboard 
$router->addRoute('get', '/dashboard', function() use($routesDir) {
    include_once $routesDir.'dashboard.html';
});

// API for getting user infomation
$router->addRoute('get', '/user', function() use($routesDir) {
    // Check if the post data contains 'username' and 'password'
    if (isset($_COOKIE['session'])) {
        $session = $_COOKIE['session'];
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid session"]);
        return;
    }

    $url = "https://www.pinpointlearning.co.uk/mainn.php";

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "content-type: application/x-www-form-urlencoded",
        ],
        CURLOPT_COOKIE => "PHPSESSID=$session",
    ];
    

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    curl_close($ch);

    if(strlen($body) === 280) {
        echo json_encode(["status"=> "error","message"=> "Please login"]);
    }

    preg_match('/^(.*?)<\/h1>/', substr($body, 3002), $matches);
    $nameStr = $matches[1];
    $names = explode(' ', $nameStr);

    echo json_encode(array("status" => "success", "fname" => ucfirst(strtolower($names[1])), "lname" => ucfirst(strtolower($names[0]))));
});