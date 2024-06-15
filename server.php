<?php
// Function to update JSON data on the server
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

$GLOBALS['dbservername'] = "localhost";
$GLOBALS['dbusername'] = "";
$GLOBALS['dbpassword'] = "";
$GLOBALS['databasename'] = "";

// Get the endpoint from the request
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

$resource = $uri[1];

switch ($method) {
    case 'POST':
        getData();
        break;
    case 'GET':
        extractMailInformation();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}

function getData()
{
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime("-1 days"));

    $from = $today;
    $to = $today;

    $currentMonth = date('m');
    $firstDayOfMonth = date('Y-m-01');
    $lastDayOfMonth = date('Y-m-t');

    $title = "Today ({$today}) so far";

    $selMonth = $_POST['month'] ?? -1;

    if ($selMonth == 'before') {
        $from = $yesterday;
        $to = $yesterday;

        $title = $yesterday;
    } else if (intval($selMonth) > 0) {
        $selYear = $_POST['year'];
        $start_date = new DateTime("$selYear-$selMonth-01");
        // Get the end of the month
        $last_day_of_month = date('t', strtotime("$selYear-$selMonth"));

        $end_date = new DateTime("$selYear-$selMonth-$last_day_of_month");

        $from = $start_date->format('Y-m-d');
        $to = $end_date->format('Y-m-d');
        $title = "{$from} to {$to}";
    }

    $from .= ' 00:00:00';
    $to .= ' 23:59:59';
    
    $conn = new mysqli($GLOBALS['dbservername'], $GLOBALS['dbusername'], $GLOBALS['dbpassword'], $GLOBALS['databasename']);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // SQL query to retrieve data from the table
    $sql = "SELECT id, TRIM(location) as new_location, type, COUNT(*) AS count FROM data
         WHERE date_time >= '$from' AND date_time <= '$to'
         GROUP BY new_location, type";

    $result = $conn->query($sql);

    // Check if there are results
    if ($result->num_rows > 0) {
        $data = [];

        // Fetch data and add to the response array
        while ($row = $result->fetch_assoc()) {
            $location = $row['new_location'];
            $type = 'type' . $row['type'];
            $count = $row['count'];

            if (!isset($data[trim($location)])) {
                // If not, initialize an array for this location
                $data[trim($location)] = ['location' => trim($location)];
            }

            $data[trim($location)][$type] = $count;
        }

        $data = array_values($data);

        // Close connection
        $conn->close();

        // Output the JSON data
        echo json_encode(['data' => $data, 'title' => $title, 'error' => false]);
        exit;
    } else {
        // No data found
        $conn->close();
        echo json_encode(['error' => true, 'title' => $title]);
    }
}

function extractMailInformation()
{
    // Include the Google API PHP client library
    require_once __DIR__ . '/vendor/autoload.php';

    $credentialsPath = './credentials.json';
    $tokensPath = './tokens.json';

    // Set your credentials
    $client = new Google_Client();
    
    $client->setAuthConfig($credentialsPath);
    $client->addScope(Google_Service_Gmail::GMAIL_READONLY); // Adjust the scope based on your needs
    
    $client->setRedirectUri('http://bdgdash.com/server.php');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');
    
    $tokenInfo = json_decode(file_get_contents($tokensPath), true);
    $refreshToken = $tokenInfo['refresh_token'];
    
    $client->refreshToken($refreshToken);
    
    if (file_exists($tokensPath)) {
        $accessToken = json_decode(file_get_contents($tokensPath), true);
        $client->setAccessToken($accessToken);
    }
    
    // if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($refreshToken);
        $accessToken = $client->getAccessToken();
        file_put_contents($tokensPath, json_encode($client->getAccessToken()));
    // }

    $service = new Google_Service_Gmail($client);
    $user = "me";
    $senderEmails = ['example@mail.com', 'example@mail.com'];
    $searchQuery = implode(' OR ', array_map(function ($email) {
        return "from:$email";
    }, $senderEmails));
    $maxResults = 100;

    $messages = $service->users_messages->listUsersMessages($user, ['q' => $searchQuery, 'maxResults' => $maxResults]);
    processMessages($service, $user, $messages);
}

function processMessages($service, $user, $messages)
{
    $conn = new mysqli($GLOBALS['dbservername'], $GLOBALS['dbusername'], $GLOBALS['dbpassword'], $GLOBALS['databasename']);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $recordExists = false;

    foreach ($messages->getMessages() as $message) {
        if (!$recordExists) {
            $messageId = $message->getId();

            // Get the details of each message
            $messageDetails = $service->users_messages->get($user, $messageId);

            $payload = $messageDetails->getPayload()->getHeaders();
            $dateObject = array_filter($payload, function ($item) {
                return $item['name'] === 'Date';
            });
            $date = array_values($dateObject)[0]['value'] ?? null;
            $receivedDate = DateTime::createFromFormat('d M Y H:i:s O', $date)->format('Y-m-d H:i:s');

            $subjectObject = array_filter($payload, function ($item) {
                return $item['name'] === 'Subject';
            });
            $subject = array_values($subjectObject)[0]['value'] ?? null;
            $subjectInfo = explode('-', $subject);
            $location = ($subjectInfo[1]) ?? '';

            $type = (trim($subjectInfo[0]) == "New Monthly Member Info") ? 1 : ((trim($subjectInfo[0]) == "New 12M Member Info") ? 2 : ((trim($subjectInfo[0]) == "New 12Month(s) Renewal Confirmation") ? 3 : 0));

            if ($type > 0) {
                // Check if record already exists
                $sql = "SELECT * FROM data WHERE location = '$location' AND type = $type AND subject = '{$subjectInfo[0]}' AND date_time = '$receivedDate'";
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    // Record already exists
                    $recordExists = true;
                } else {
                    // Record does not exist, insert new record
                    $sql = "INSERT INTO data (location, type, subject, date_time) VALUES ('$location', $type, '{$subjectInfo[0]}', '$receivedDate')";
                    if ($conn->query($sql) === TRUE) {
                        // echo "New record created successfully";
                    } else {
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }
                }
            }
        }
    }

    $conn->close();
}