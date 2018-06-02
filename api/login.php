<?php
header('Content-type:application/json;charset=utf-8');
require_once 'include/DB_Functions.php';
$db = new DB_Functions();

// json response array
$response = array("error" => false);

if (isset($_POST['username']) && isset($_POST['password'])) {

    // receiving the post params
    $email = $_POST['username'];
    $password = $_POST['password'];

    // get the user by email and password
    $user = $db->getUserByEmailAndPassword($email, $password);

    if ($user != false) {
        // use is found
        $response["error"] = false;
        $response["user"]["username"] = $user["username"];
        $response["user"]["api_key"] = $user["api_key"];
        $response["trabajador"]["nombre"] = $user["nombre"];
        $response["trabajador"]["apellidos"] = $user["apellidos"];

        $response = array_map("encode_all_strings", $response);
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        // user is not found with the credentials
        $response["error"] = true;
        $response["error_msg"] = "Login credentials are wrong. Please try again!";
        echo json_encode($response);
    }
} else {
    // required post params is missing
    $response["error"] = true;
    $response["error_msg"] = "Required parameters email or password is missing!";
    echo json_encode($response);
}