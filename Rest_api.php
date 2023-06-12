<?php
header("Access-Control-Allow-Origin: *");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_Fonteyn";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// get request method
$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'GET') {
	echo "THIS IS A GET REQUEST";

}
if ($method == 'POST'){
    if(validateAccessToken($conn,$_POST["valapi_key"],$_POST["valmacadress"]) == true){        
        if(processPostValues()){
                if($_POST["protocol"] == "addmacadress"){
                    insert_macadress($conn,$_POST["macadress"]);;
                }else if($_POST["protocol"] == "generate-api-key"){
                    generateApiKey($conn);
                }else if($_POST["protocol"] == "add-monitor-data"){
                    insert($conn);
                }else if($_POST["protocol"] == "add-alert"){
                    sendalert($conn);
                }
                else if($_POST["protocol"] == "get-server"){
                    lookupserver($conn);
                }
                else if($_POST["protocol"] == "add-request"){
                    addrequest($conn);
                }
                
                
        }
    }   

    //echo $_POST["status"];
   // echo validateAccessToken($_POST["api_key"],$_POST["macaddress"]);
    //handlerequest($_POST["status"],$_POST["api_key"]);
	//echo "THIS IS A POST REQUEST";
    // Retrieve the parameters from the POST request
    //$macaddress = $_POST["macaddress"];
    //echo $macaddress;
    //$timestamp = $_POST['timestamp'];
    //echo $timestamp;
    //getCredentialsByMacAdress($macaddress);
    //$cpu = $_POST['CPU'];
    //$disk = $_POST['Disk'];
    //$memory = $_POST['memory'];
    
    // Call the insert function to process the received data
    //insert($conn,$timestamp, $cpu, $disk, $memory);
}
if ($method == 'PUT') {
	echo "THIS IS A PUT REQUEST";
}
if ($method == 'DELETE') {
	echo "THIS IS A DELETE REQUEST";
}
#insert the data into the database
function insert($conn) {
    $stmt = $conn->prepare("INSERT INTO tb_specs (cpu, disk, memory,server_ip) VALUES (?, ?, ?,?)");
    $stmt->bind_param("ssss", $_POST['cpu'], $_POST['disk'], $_POST['memory'],$_POST["servernumber"]);
    if ($stmt->execute()) {
    } else {
        echo "Error inserting reservation data: " . $stmt->error;
    }
}
function addrequest($conn) {

    $stmt = $conn->prepare("INSERT INTO requests (server, status, speed) VALUES (?, ?, ?)");
    $stmt->bind_param("sss",$_POST['server'],$_POST['status'],$_POST['speed']);
    if ($stmt->execute()) {
    } else {
        echo "Error inserting reservation data: " . $stmt->error;
    }
}
function lookupserver($conn) {
    $id ="";
    $stmt = $conn->prepare("SELECT id FROM servers WHERE ip = ?");
    if (!$stmt) {
        echo "Error preparing SQL statement: " . $conn->error;
        return null;
    }
    
    $stmt->bind_param("s", $_POST["ip"]);
    
    if (!$stmt->execute()) {
        echo "Error executing SQL statement: " . $stmt->error;
        return null;
    }
    
    $stmt->bind_result($id);
    
    if ($stmt->fetch()) {
    } else {
        echo "No matching record found";
    }
    echo $id;
}
function insert_macadress($conn,$data) {

    $id = "";
    $stmt = $conn->prepare("INSERT INTO tb_macadress (macadress) VALUES (?)");
    $stmt->bind_param("s", $data);
    if ($stmt->execute()) {
        $stmt->bind_result($id);
        if ($stmt->fetch()) {
            echo "Reservation data inserted successfully. ID: " . $id;
        } else {
            echo "No matching record found.";
        }
    } else {
        echo "Error inserting reservation data: " . $stmt->error;
    }
    echo $id;
    return $id;
    
}
#validates the api ussage with the help of the mac adress and the api key
function validateAccessToken($conn,$api_key, $macaddress){
    // Retrieve the credentials from the storage based on the provided MAC address
    // Check if the credentials exist and match the provided username and password
    if (getCredentialsByMacAdress($conn,$macaddress) == true && getApiKey($conn,$api_key) == true){
        return true; // Authentication succeeded
    }else{
        $errormessage ="Someone tried to acces our api with the wrong credentials api key: " . strval($api_key) . " mac adress: " . strval($macaddress);
        sendalert($errormessage,2);
        echo "Function has been executed";
        return false; // Authentication failed
    }
}
function getCredentialsByMacAdress($conn,$macaddress){
    // Prepare the MySQL query
    $query = "SELECT * FROM tb_macadress WHERE macadress = ?";
    $stmt = $conn->prepare($query);
    // Bind the parameter and execute the query
    $stmt->bind_param("s", $macaddress);
    $success = $stmt->execute();
    if ($success === false) {
        die("Error executing query: " . $stmt->error);
    }
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        return true;
    }else{
        return false;
    }
}
function getApiKey($conn,$apikey) {
    // Prepare the MySQL query
    $query = "SELECT * FROM tb_apikey WHERE apikey = ?";
    $stmt = $conn->prepare($query);
    // Bind the parameter and execute the query
    $stmt->bind_param("s", $apikey);
    $success = $stmt->execute();
    if ($success === false) {
        die("Error executing query: " . $stmt->error);
    }
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return true;
    } else {
        return false;
    }
}
function generateApiKey($conn) {
    $uuid = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
    $stmt = $conn->prepare("INSERT INTO tb_apikey (apikey) VALUES (?)");
    $stmt->bind_param("s", $uuid);
    $stmt->execute();
    return $uuid;
}
function sendalert($conn){
    $stmt = $conn->prepare("INSERT INTO tb_alerts (alert,status,server_id) VALUES (?,?,?)");
    $stmt->bind_param("sss", $_POST["message"],$_POST["status"],$_POST["servernumber"]);
    if ($stmt->execute()) {
    } else {
        echo "Error inserting reservation data: " . $stmt->error;
    }
}
function regexfunction($variable){
    $newvariable = strip_tags($variable, '<p><a>');
    $pattern = '/^[a-zA-Z0-9\/:.-]+$/';
    if (preg_match($pattern, $newvariable)) {
        return true;
    } else {
        sendalert("Possible sqli detected!!! api_key: " . $_POST["valapi_key"] . " Macaddress: " . $_POST["valmacadress"] . "the sqli injection" . $variable, 1);
        return false;
    }
}
function processPostValues() {
    foreach ($_POST as $value) {
        if (!regexfunction($value)) {
            echo $value;
            return false;
        }
    }
    return true;
}