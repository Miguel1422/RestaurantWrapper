<?php
/**
 * A class file to connect to database
 */
class DB_CONNECT
{
    private $conn;
    // constructor
    public function __construct()
    {
        // connecting to database
        $this->connect();
    }

    // destructor
    public function __destruct()
    {
        // closing db connection
        $this->close();
    }

    /**
     * Function to connect with database
     */
    public function connect()
    {
        // import database connection variables
        require_once __DIR__ . '/db_config.php';

        // Connect to sql server using windows authentication
        $connectionInfo = array("Database" => DB_DATABASE, "CharacterSet" => "UTF-8");
        $conn = sqlsrv_connect(DB_SERVER, $connectionInfo);
        // $conn = new PDO("sqlsrv:Server=localhost;Database=Restaurant");

        if (!$conn) {
            die(print_r(sqlsrv_errors(), true));
        }

        // returing connection cursor
        return $conn;
    }

    /**
     * Function to close db connection
     */
    public function close()
    {
        // closing db connection
        // sqlsrv_close($this->conn);
    }

}
