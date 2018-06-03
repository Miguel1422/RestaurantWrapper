<?php
header('Content-type:application/json;charset=utf-8');
require_once 'include/DB_Functions.php';
$db = new DB_Functions();

// json response array
$response = array("error" => false);


if (isset($_POST['api_key'])) {
    if (!isset($_GET['id_orden_producto'])) {
        print_err("No se tiene el producto", $response);
    }
    // receiving the post params
    $key = $_POST['api_key'];
    $id_orden_producto = $_GET['id_orden_producto'];
    // get the user by email and password
    if ($db->isValidApiKey($key)) {
        $response["ordenEliminada"] = $db->eliminarPedido($id_orden_producto);
        if(!$response["ordenEliminada"]) {
            print_err("No se pudo eliminar la orden", $response);
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
