<?php
$serverName = "localhost"; //serverName\instanceName

// Puesto que no se han especificado UID ni PWD en el array  $connectionInfo,
// La conexión se intentará utilizando la autenticación Windows.
$connectionInfo = array("Database" => "Restaurant");
$conn = sqlsrv_connect($serverName, $connectionInfo);

if ($conn) {
} else {
    echo "Conexión no se pudo establecer.<br />";
    die(print_r(sqlsrv_errors(), true));
}

// $result = sqlsrv_query($conn, "SELECT * FROM Producto");
$stm = sqlsrv_prepare($conn, "SELECT * FROM Producto");
// $stmt->bind_param("sssss", $uuid, $name)
$result = sqlsrv_execute($stm);

if ($result === false) {
    die(print_r(sqlsrv_errors(), true));
}
if (!empty($result)) {
    $response = array();
    $response["producto"] = array();
    while ($prod = sqlsrv_fetch_array($stm, SQLSRV_FETCH_ASSOC)) {
        $product = array();
        $product["id_producto"] = $prod["id_producto"];
        $product["nombre_producto"] = $prod["nombre_producto"];
        $product["id_categoria"] = $prod["id_categoria"];
        array_push($response["producto"], $product);
    }
    echo json_encode($response);
} else {
    echo "what";
}

sqlsrv_close($conn);
require_once 'include/DB_Functions.php';
$test = new DB_Functions();

$res = $test->getUserByEmailAndPassword('alberto', '123');
if ($res) {
    echo json_encode($res);
} else {
    echo "El usuario no existe";
}
//sqlsrv_free_stmt($stmt);
