<?php
header('Content-type:application/json;charset=utf-8');
require_once 'include/DB_Functions.php';
$db = new DB_Functions();

// json response array
$response = array("error" => false);


if (isset($_POST['api_key'])) {
    if (!isset($_POST['id_orden_producto']) || !isset($_POST['id_tipo_producto']) || !isset($_POST['id_variantes']) || !isset($_POST['cantidad']) || !isset($_POST['comentarios'])) {
        print_err("Hacen falta datos", $response);
    }
    // receiving the post params
    $key = $_POST['api_key'];

    // receiving the get params
    $id_orden_producto = $_POST['id_orden_producto'];
    $id_tipo_producto = $_POST['id_tipo_producto'];
    $id_variantes = explode("|", $_POST['id_variantes']);
    $cantidad = $_POST['cantidad'];
    $comentarios = $_POST['comentarios'];

    // get the user by email and password
    if ($db->isValidApiKey($key)) {
        $response["pedido_agregado"] = $db->editarPedido($id_orden_producto, $id_tipo_producto, $id_variantes, $cantidad, $comentarios);
        if (!$response["pedido_agregado"]) {
            print_err("No se pudo editar la orden, asegurate de estar conectado a la DB", $response);
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
