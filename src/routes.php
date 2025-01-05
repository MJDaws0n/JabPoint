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
    header('Content-Type: application/json; charset=utf-8');
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
        echo json_encode(array("status" => "error", "message" => "An error has occurred. Pinpoint has had an update breaking our system. Please try again later or use the original site."));
    }
});

// Dashboard 
$router->addRoute('get', '/dashboard', function() use($routesDir) {
    include_once $routesDir.'dashboard.html';
});

// API for getting user infomation
$router->addRoute('get', '/user', function() use($routesDir) {
    header('Content-Type: application/json; charset=utf-8');
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
        return;
    }

    // Suppress libxml errors for invalid HTML as pinpoint has a lot of them
    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXPath($dom);

    // Names
    {
        // XPath query equivalent to the JS selector
        $query = '//div[@style="background:url(images/bg5.png) !important"]/h1/font';
        $node = $xpath->query($query)->item(0);

        // Get the text content if the node exists
        if (!$node) {
            echo json_encode(["status" => "error", "error" => 'An error has occurred. Pinpoint has had an update breaking our system. Please try again later or use the original site.' ]);
            return;
        }

        $nameStr = substr($node->textContent, 4, -1);
        $names = explode(' ', $nameStr);
    }

    // Papers
    {
        // XPath query to select all <option> elements in the targeted <select>
        $query = '//select[@onchange="this.form.submit()"][@name="dropdown"]/option';

        $options = $xpath->query($query);

        $paperNames = [];
        $paperValues = [];

        foreach ($options as $option) {
            /** @var DOMElement $option */ // <- Stop VSCode crying
            $paperNames[] = $option->textContent;
            $paperValues[] = $option->getAttribute('value');
        }
    }

    echo json_encode(["status" => "success", "fname" => ucfirst(strtolower($names[1])), "lname" => ucfirst(strtolower($names[0])), "papers" => $paperNames, "paperValues" => $paperValues]);
});

// API for getting paper information
$router->addRoute('get', '/paper', function() use($routesDir) {
    header('Content-Type: application/json; charset=utf-8');
    // Check if the post data contains 'username' and 'password'
    if (isset($_COOKIE['session'])) {
        $session = $_COOKIE['session'];
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid session"]);
        return;
    }

    // Get paper is set
    if (isset($_GET['paper'])) {
        $paper = $_GET['paper'];
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid paper"]);
        return;
    }

    $url = "https://www.pinpointlearning.co.uk/mainn.php";

    $data = "dropdown=".$paper;

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $data,
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
        return;
    }

    // Suppress libxml errors for invalid HTML as pinpoint has a lot of them
    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXPath($dom);

    // Table thing
    {
        $query = '//h2/div[@class="row"]/div[@class="col-md-8"]/table[@class="table table-bordered"]';
        $node = $xpath->query($query)->item(0);

        // Get the text content if the node exists
        if (!$node) {
            echo json_encode(["status" => "error", "error" => 'An error has occurred. Pinpoint has had an update breaking our system. Please try again later or use the original site.' ]);
            return;
        }

        $table = $node->C14N();
    }

    // Total marks
    {
        $query = '//h2/div[@class="row"]/div[@class="col-md-8"]/h2';

        $node = $xpath->query($query)->item(0);

        // Get the text content if the node exists
        if (!$node) {
            echo json_encode(["status" => "error", "error" => 'An error has occurred. Pinpoint has had an update breaking our system. Please try again later or use the original site.' ]);
            return;
        }

        $marks = explode(' ', substr($node->textContent, 72))[0];
    }

    // Topics
    {
        $query = '//h2/div[@class="row"]/div[@class="col-md-8"]/h2/h2/div[@class="list-group"]/a';

        $topicsNodes = $xpath->query($query);

        // Get the text content if the node exists
        if (!$node) {
            echo json_encode(["status" => "error", "error" => 'An error has occurred. Pinpoint has had an update breaking our system. Please try again later or use the original site.' ]);
            return;
        }

        $topicNames = [];
        $topicLinks = [];

        foreach ($topicsNodes as $topicsNode) {
            /** @var DOMElement $topicsNode */ // <- Stop VSCode crying
            if(preg_replace('/\s+/', ' ', str_replace(["\n", "\r"], '', trim($topicsNode->textContent))) === 'Your Pinpoint Topics And Video Lessons'){
                continue;
            }
            $topicNames[] = preg_replace('/\s+/', ' ', str_replace(["\n", "\r"], '', trim($topicsNode->textContent)));
            $topicLinks[] = trim($topicsNode->getAttribute('href'));
        }
    }

    echo json_encode(["status" => "success", "table" => $table, "marks" => $marks, "topics" => $topicNames, "topicLinks" => $topicLinks]);
});

// API for downloading personal booklet
$router->addRoute('get', '/booklet', function() use($routesDir) {
    header('Content-Type: application/json; charset=utf-8');


    // You MUST first ping the paper page as it's the only way
    if (isset($_COOKIE['session'])) {
        $session = $_COOKIE['session'];
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid session"]);
        return;
    }

    // Get paper is set
    if (isset($_GET['paper'])) {
        $paper = $_GET['paper'];
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid paper"]);
        return;
    }

    $url = "https://www.pinpointlearning.co.uk/Questions_newwer.php?slick=1&cc=1&AOtype=AO1";

    $data = "dropdown=".$paper;

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $data,
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
        return;
    }

    // Force download
    $url = 'https://www.pinpointlearning.co.uk/testpdf_newer.php';


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=$session");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        // Set headers to trigger file download in the browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="PinPoint(JabPoint)_paper_'.$paper.'.pdf"');
        echo $response;
    } else {
        echo json_encode(["status" => "error"]);
    }
});

// View paper
$router->addRoute('get', '/paper-view', function() use($routesDir) {
    include_once $routesDir.'view-paper.html';
});

// Upload
$router->addRoute('get', '/upload', function() use($routesDir) {
    include_once $routesDir.'upload.html';
});