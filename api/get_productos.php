<?php
header('Content-type:application/json;charset=utf-8');
require_once 'include/DB_Functions.php';
$db = new DB_Functions();

// json response array
$response = array("error" => false);


if (isset($_POST['api_key'])) {
    // receiving the post params
    $key = $_POST['api_key'];
    if ($db->isValidApiKey($key)) {
        $response["productos"] = $db->getProductos();
        if(!$response["productos"]) {
            print_err("No se pudieron recuperar los productos", $response);
        }
        $response = array_map("encode_all_strings", $response);
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    } else {
        print_err("Error informacion desactualizada, logueate de nuevo!", $response);
    }
} else {
    // required post params is missing
    print_err("Permiso denegado", $response);
}
