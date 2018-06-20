<?php
// header("content-type: text/html; charset=UTF-8");

/**
 * @author Ravi Tamada
 * @link https://www.androidhive.info/2012/01/android-login-and-registration-with-php-mysql-and-sqlite/ Complete tutorial
 */
function encode_all_strings($arr)
{
    if (!is_array($arr)) {
        if (is_string($arr))
            return utf8_encode($arr);
        return $arr;
    }

    foreach ($arr as $key => $value) {
        $arr[$key] = encode_all_strings($value);
    }
    return $arr;

}

function print_err($msg, $response)
{
    $response["error"] = true;
    $response["error_msg"] = $msg;
    die (json_encode($response));
}

class DB_Functions
{

    private $conn;

    // constructor
    public function __construct()
    {
        require_once 'db_connect.php';
        // connecting to database
        $db = new DB_CONNECT();
        $this->conn = $db->connect();
    }

    // destructor
    public function __destruct()
    {
        $db = new DB_CONNECT();
        $db->close($this->conn);
    }

    /**
     * Get user by email and password
     */
    public function getUserByUsernameAndPassword($username, $password)
    {
        $stmt = sqlsrv_prepare($this->conn,
            "SELECT * FROM [User] AS u " .
            "INNER JOIN Trabajador AS t " .
            "ON u.id_trabajador = t.id_trabajador " .
            "WHERE u.username = ?"
            , array($username));
        $result = sqlsrv_execute($stmt);

        if (!$result) {
            return null;
        }

        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        // verifying user password
        $salt = $user['salt'];
        $encrypted_password = $user['hash'];
        $hash = $this->checkhashSSHA($salt, $password);
        $uuid = md5(uniqid('', true));

        // Una vez logueado crear una api key para la sesion y todas las seesiones posteriores o hasta un nuevo logueo
        $user["api_key"] = $uuid;

        $this->updateApiKey($username, $uuid);

        // check for password equality
        if ($encrypted_password == $hash) {
            // user authentication details are correct
            return $user;
        }
    }

    public function getUserByApiKey($api_key)
    {
        $stmt = sqlsrv_prepare($this->conn,
            "SELECT * FROM [User] AS u " .
            "INNER JOIN Trabajador AS t " .
            "ON u.id_trabajador = t.id_trabajador " .
            "WHERE u.api_key = ?"
            , array($api_key));
        $result = sqlsrv_execute($stmt);

        if (!$result) {
            return null;
        }

        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        // check for password equality
        // user authentication details are correct
        return $user;

    }

    public function getOrden($id_mesa)
    {
        $stmt = sqlsrv_prepare($this->conn, "SELECT * FROM Orden WHERE id_mesa = ? AND activa = 1", array($id_mesa));
        $result = sqlsrv_execute($stmt);
        if (!$result) {
            sqlsrv_free_stmt($stmt);
            return false;
        }
        $orden = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if (!$orden) {
            return false;
        }
        $orden["pedidos"] = array();

        $stmt = sqlsrv_prepare($this->conn, "EXECUTE getPedidosDeOrden @IDOrden = ?", array($orden["id_orden"]));
        $result = sqlsrv_execute($stmt);
        if (!$result) {
            sqlsrv_free_stmt($stmt);
            return false;
        }
        while ($pedido = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // $pedido["variantes"]
            array_push($orden["pedidos"], $pedido);
        }

        return $orden;
    }

    public function agregarOrden($id_mesa, $api_key)
    {
        $trabajador = $this->getUserByApiKey($api_key);

        $stmt = sqlsrv_prepare($this->conn, "INSERT INTO Orden (id_mesa, id_trabajador) VALUES (?, ?)", array($id_mesa, $trabajador["id_trabajador"]));
        $result = sqlsrv_execute($stmt);
        if (!$result) {
            sqlsrv_free_stmt($stmt);
            return false;
        }
        return $this->getOrden($id_mesa);
    }

    public function agregarPedido($id_orden, $id_tipo_producto, $id_variantes, $cantidad, $comentarios)
    {
        $variantes = "";
        foreach ($id_variantes as $variante) {
            $variantes .= trim($variante);
        }
        $stmt = sqlsrv_prepare($this->conn, "EXECUTE agregarOrdenProducto @IDOrden = ?, @IDTipoProducto = ?, @IDVariantes = ?, @Cantidad = ?, @Comentarios = ?",
            array($id_orden, $id_tipo_producto, $variantes, $cantidad, $comentarios));

        $result = sqlsrv_execute($stmt);
        sqlsrv_free_stmt($stmt);
        if (!$result) {
            return false;
        }
        $stmt = sqlsrv_prepare($this->conn, "SELECT IDENT_CURRENT('OrdenProducto')"); // Obtiene el id del prodcucto agregado
        $result = sqlsrv_execute($stmt);
        if (!$result) {
            return false;
        }
        $id_orden_producto = sqlsrv_fetch_array($stmt)[0];

        $result = $this->getOrdenProducto($id_orden_producto);

        return $result;
    }

    public function getOrdenProducto($id_orden_producto)
    {
        $stmt = sqlsrv_prepare($this->conn, "SELECT * FROM OrdenProducto AS OP INNER JOIN TipoProducto TP ON OP.id_tipo_producto = TP.id_tipo_producto INNER JOIN Producto P ON TP.id_producto = P.id_producto INNER JOIN CategoriaProducto AS C ON P.id_categoria = C.id_categoria WHERE id_orden_producto = ?", array($id_orden_producto));

        $result = sqlsrv_execute($stmt);
        if (!$result) {
            return false;
        }
        $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        sqlsrv_free_stmt($stmt);
        $result['variantes'] = "";
        return $result;
    }

    public function editarPedido($id_orden_producto, $id_tipo_producto, $id_variantes, $cantidad, $comentarios, $status)
    {
        $variantes = "";
        foreach ($id_variantes as $variante) {
            $variantes .= trim($variante);
        }
        $stmt = sqlsrv_prepare($this->conn, "EXECUTE editarOrdenProducto @IDOrdenProducto = ?, @IDTipoProducto = ?, @IDVariantes = ?, @Cantidad = ?, @Comentarios = ?, @Status = ?",
            array($id_orden_producto, $id_tipo_producto, $variantes, $cantidad, $comentarios, $status));

        $result = sqlsrv_execute($stmt);
        sqlsrv_free_stmt($stmt);
        return $result;
    }

    public function editarPedidoStatus($id_orden_producto, $status)
    {
        $stmt = sqlsrv_prepare($this->conn, "UPDATE OrdenProducto SET [status] = ? WHERE id_orden_producto = ?",
            array($status, $id_orden_producto));

        $result = sqlsrv_execute($stmt);
        sqlsrv_free_stmt($stmt);
        return $result;
    }

    public function eliminarPedido($id_orden_producto)
    {
        $stmt = sqlsrv_prepare($this->conn, "DELETE FROM OrdenProducto WHERE id_orden_producto = ?", array($id_orden_producto));
        $result = sqlsrv_execute($stmt);
        sqlsrv_free_stmt($stmt);
        return $result;
    }

    public function getMesas($api_key)
    {
        $stmt = sqlsrv_prepare($this->conn,
            "SELECT * FROM Mesa AS M " .
            "INNER JOIN EstadoMesa AS EM " .
            "ON M.id_estado = EM.id_estado_mesa"
        );
        $result = sqlsrv_execute($stmt);

        if (!$result) {
            sqlsrv_free_stmt($stmt);
            return false;
        }
        $mesas = array();
        while ($mesa = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            array_push($mesas, $mesa);
        }
        sqlsrv_free_stmt($stmt);
        return $mesas;
    }

    public function getProductos()
    {
        $query = "SELECT * FROM Producto AS P " .
            "INNER JOIN CategoriaProducto AS CP " .
            "ON P.id_categoria = CP.id_categoria " .
            "ORDER BY CP.nombre_categoria, P.nombre_producto";

        $stmt = sqlsrv_prepare($this->conn, $query);
        $result = sqlsrv_execute($stmt);

        if (!$result) {
            sqlsrv_free_stmt($stmt);
            return false;
        }
        $productos = array();
        while ($producto = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $producto["tipos"] = $this->getTipos($producto["id_producto"]);
            array_push($productos, $producto);
        }
        sqlsrv_free_stmt($stmt);
        return $productos;
    }

    public function getProductoImagen($id_producto)
    {
        $query = "SELECT * FROM ProductoImagen WHERE id_imagen = ?";

        $stmt = sqlsrv_prepare($this->conn, $query, array($id_producto));
        $result = sqlsrv_execute($stmt);
        if (!$result) {
            sqlsrv_free_stmt($stmt);
            return false;
        }
        $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $image = $result["imagen"];
        return $image;
    }

    public function getTrabajadorImagen($id_trabajador)
    {
        $query = "SELECT * FROM TrabajadorImagen WHERE id_trabajador = ?";

        $stmt = sqlsrv_prepare($this->conn, $query, array($id_trabajador));
        $result = sqlsrv_execute($stmt);
        if (!$result) {
            sqlsrv_free_stmt($stmt);
            return false;
        }
        $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $image = $result["imagen"];
        return $image;
    }

    public function getTipos($id_producto)
    {
        $query = "EXECUTE getTipoProductos @IDProducto = ?";

        $stmt = sqlsrv_prepare($this->conn, $query, array($id_producto));
        $result = sqlsrv_execute($stmt);

        if (!$result) {
            sqlsrv_free_stmt($stmt);
            return false;
        }
        $tipos = array();
        while ($tipo = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            array_push($tipos, $tipo);
        }
        sqlsrv_free_stmt($stmt);
        return $tipos;
    }

    public function isValidApiKey($api_key)
    {
        $stmt = sqlsrv_prepare($this->conn, "SELECT api_key from [User] WHERE api_key = ?", array($api_key));
        $result = sqlsrv_execute($stmt);
        if (!$result) {
            return false;
        }
        $asd = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return is_array($asd) && count($asd) > 0;
    }

    public function updateApiKey($username, $uuid)
    {
        $stmt = sqlsrv_prepare($this->conn,
            "UPDATE [User] SET api_key = ? WHERE username = ?"
            , array($uuid, $username));

        $result = sqlsrv_execute($stmt);
        sqlsrv_free_stmt($stmt);
        if (!$result) {
            return false;
        }
        return true;
    }

    /**
     * Check user is existed or not
     */
    public function isUserExisted($user)
    {
        $stmt = sqlsrv_prepare($this->conn, "SELECT username from [User] WHERE username = ?", array($user));
        $result = sqlsrv_execute($stmt);

        if (!$result) {
            die(print_r(sqlsrv_errors(), true));
        }
        $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (count($result) > 0) {
            // user existed
            sqlsrv_free_stmt($stmt);
            return true;
        } else {
            // user not existed
            sqlsrv_free_stmt($stmt);
            return false;
        }
    }

    /**
     * Decrypting password
     * @param salt, password
     * returns hash string
     */
    public function checkhashSSHA($salt, $password)
    {
        $chars = str_split($salt);
        $bin = join($chars);
        $hex = bin2hex($bin);
        $hex = strtoupper($hex);
        $hash = sha1($hex . $password . $hex, true);
        return $hash;
    }





}
