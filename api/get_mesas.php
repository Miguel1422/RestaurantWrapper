<?php
header('Content-type:application/json;charset=utf-8');
require_once 'include/DB_Functions.php';
$db = new DB_Functions();

// json response array
$response = array("error" => false);

if (isset($_POST['api_key'])) {
    // receiving the post params
    $key = $_POST['api_key'];
    // get the user by email and password

    if ($db->isValidApiKey($key)) {
        $response["mesas"] = array();
        $mesas = $db->getMesas($key);
        if (count($mesas) == 0) {
            $response["error"] = true;
            $response["error_msg"] = "No se ha podido recuperar la informacion de las mesas!";
            echo json_encode($response);
        } else {
            foreach ($mesas as $mesa) {
                array_push($response["mesas"], $mesa);
            }
            $response = array_map("encode_all_strings", $response);
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } else {
        $response["error"] = true;
        $response["error_msg"] = "Error informacion desactualizada, logueate de nuevo!";
        echo json_encode($response);
    }
} else {
    // required post params is missing
    $response["error"] = true;
    $response["error_msg"] = "Permiso denegado";
    echo json_encode($response);
}
