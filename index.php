<!DOCTYPE html>
<?php
if(isset($_GET['asd'])) {
    echo 'Hola';
}
if(isset($_POST["test"])) {
    echo 'Hola es valido';
} else {
    echo 'Not set';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Restaurant</title>
</head>
<body>
    <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" name="start">
        <input type="text" name = "test">
        <input type="Submit" name="aceptar" value="INGRESAR"/>	
        
    </form>
</body>
</html>