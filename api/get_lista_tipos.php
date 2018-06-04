<?php
header('Content-type:application/json;charset=utf-8');
require_once 'include/DB_Functions.php';
$db = new DB_Functions();

// json response array
$response = array("error" => false);

if (isset($_POST['api_key'])) {

    if (!isset($_GET['id_producto'])) {
        print_err('No se ha ingresado el producto', $response);
    }
    // receiving the post params
    $key = $_POST['api_key'];
    $id_producto = $_GET['id_producto'];

    if ($db->isValidApiKey($key)) {
        $response["tipos"] = $db->getTipos($id_producto);
        if (!$response["tipos"]) {
            print_err("No se pudieron recuperar los tipos", $response);
        }
        // $response = array_map("encode_all_strings", $response);
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    } else {
        print_err("Error informacion desactualizada, logueate de nuevo!", $response);
    }
} else {
    // required post params is missing
    print_err("Permiso denegado", $response);
}
