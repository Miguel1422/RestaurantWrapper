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


        $temp_array = array();
        foreach ($response["productos"] as $producto) {
            array_push($temp_array, $producto);
        }
        usort($temp_array, function($a, $b)
        {
            return strcmp($a["nombre_producto"], $b["nombre_producto"]);
        });
        foreach ($temp_array as $producto) {
            $producto["id_categoria"] = -1;
            $producto["nombre_categoria"] = "Todas";
            array_push($response["productos"], $producto);
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
