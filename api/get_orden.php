<?php
header('Content-type:application/json;charset=utf-8');
require_once 'include/DB_Functions.php';
$db = new DB_Functions();

// json response array
$response = array("error" => false);


if (isset($_POST['api_key'])) {
    if (!isset($_GET['id_mesa'])) {
        print_err("No se tiene la mesa", $response);
    }
    // receiving the post params
    $key = $_POST['api_key'];
    $id_mesa = $_GET['id_mesa'];
    // get the user by email and password
    if ($db->isValidApiKey($key)) {
        $response["orden"] = $db->getOrden($id_mesa);
        if(!$response["orden"]) {
            print_err("No existe una orden", $response);
        }
        $response = array_map("encode_all_strings", $response);
        $response["orden"]["fecha"] = $response["orden"]["fecha"]->setTimezone(new DateTimeZone('America/Mexico_City'));
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    } else {
        print_err("Error informacion desactualizada, logueate de nuevo!", $response);
    }
} else {
    // required post params is missing
    print_err("Permiso denegado", $response);
}
