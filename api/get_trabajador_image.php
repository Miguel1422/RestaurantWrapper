<?php
header('Content-type:application/json;charset=utf-8');
require_once 'include/DB_Functions.php';
$db = new DB_Functions();

// json response array
$response = array("error" => false);


if (true || isset($_POST['api_key'])) {
    if (!isset($_GET['id_trabajador'])) {
        print_err("No se tiene el trabajador", $response);
    }
    // receiving the post params
    $key = $_POST['api_key'];
    $id_trabajador = $_GET['id_trabajador'];
    // get the user by email and password
    if (true || $db->isValidApiKey($key)) {
        $response["image"] = $db->getTrabajadorImagen($id_trabajador);
        if(!$response["image"]) {
            print_err("No existe esa imagen", $response);
        }
        /* header('Content-Type: image/png');
        $data = $response['image'];
        $im = imagecreatefromstring($data);
        imagejpeg($im);
        imagedestroy($im);
        */
        $response['image'] = base64_encode($response['image']);
        echo json_encode($response);
    } else {
        print_err("Error informacion desactualizada, logueate de nuevo!", $response);
    }
} else {
    // required post params is missing
    print_err("Permiso denegado", $response);
}
