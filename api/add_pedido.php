<?php
header('Content-type:application/json;charset=utf-8');
require_once 'include/DB_Functions.php';
$db = new DB_Functions();

// json response array
$response = array("error" => false);


if (isset($_POST['api_key'])) {
    if (!isset($_POST['id_orden']) || !isset($_POST['id_tipo_producto']) || !isset($_POST['id_variantes']) || !isset($_POST['cantidad']) || !isset($_POST['comentarios']) || !isset($_POST['uid'])) {
        print_err("Hacen falta datos", $response);
    }
    // receiving the post params
    $key = $_POST['api_key'];
    $id_orden = $_POST['id_orden'];
    $id_tipo_producto = $_POST['id_tipo_producto'];
    $id_variantes = explode("|", $_POST['id_variantes']);
    $cantidad = $_POST['cantidad'];
    $comentarios = $_POST['comentarios'];
    $uid = $_POST['uid'];

    // get the user by email and password
    if ($db->isValidApiKey($key)) {

        if($db->isVerificadorInserted($uid)) {
            $response["pedido_agregado"] = $db->getPedidoByVerificador($uid);
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {

            $response["pedido_agregado"] = $db->agregarPedido($id_orden, $id_tipo_producto, $id_variantes, $cantidad, $comentarios, $uid);
            if (!$response["pedido_agregado"]) {
                print_err("No se pudo crear la orden", $response);
            }
            // $response = array_map("encode_all_strings", $response);
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } else {
        print_err("Error informacion desactualizada, logueate de nuevo!", $response);
    }
} else {
    // required post params is missing
    print_err("Permiso denegado", $response);
}
