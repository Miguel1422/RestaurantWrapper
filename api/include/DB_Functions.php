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

    }

    /**
     * Get user by email and password
     */
    public function getUserByEmailAndPassword($username, $password)
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
        $uuid = uniqid('', true);

        // Una vez logueado crear una api key para la sesion y todas las seesiones posteriores o hasta un nuevo logueo
        $user["api_key"] = $uuid;

        $this->updateApiKey($username, $uuid);

        // check for password equality
        if ($encrypted_password == $hash) {
            // user authentication details are correct
            return $user;
        }
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

        if(!$orden) {
            return false;
        }
        $orden["pedidos"] = array();

        $stmt = sqlsrv_prepare($this->conn, "EXECUTE getPedidosDeOrden @IDOrden = ?", array($orden["id_orden"]));
        $result = sqlsrv_execute($stmt);
        if (!$result) {
            sqlsrv_free_stmt($stmt);
            return false;
        }
        while($pedido = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // $pedido["variantes"]
            array_push($orden["pedidos"], $pedido);
        }

        return $orden;
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

    public function isValidApiKey($api_key)
    {
        $stmt = sqlsrv_prepare($this->conn, "SELECT api_key from [User] WHERE api_key = ?", array($api_key));
        $result = sqlsrv_execute($stmt);
        if (!$result) {
            return false;
        }
        $asd = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return count($asd) > 0;
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
