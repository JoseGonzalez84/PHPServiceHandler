<?php
/**
 * Web de control de servicios
 * 
 * PHP Service Handler v0.1
 */
require_once 'Servicio.class.php';

$idServicio = 1;
$servicio = new Servicio($idServicio);
$mensajes = [
    Servicio::STATUS_RUNNING => 'Activo',
    Servicio::STATUS_KILLING => 'Deteniendo',
    Servicio::STATUS_STOPPED => 'Parado',
];

$lastInfo = $servicio->getLastInfo($idServicio);
if (empty($lastInfo) === false) {
    var_dump($lastInfo[0]);
}


if (isset($_POST['actStart']) === true) {
    $salida = $servicio->start();
    print_r($salida);
    $servicio = new Servicio($idServicio);
    $lastInfo = $servicio->getLastInfo($idServicio);
} else if (isset($_POST['actStop']) === true) {
    $servicio->stop($lastInfo[0]['pid']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba servicios</title>
</head>
<body>
    <?php if (empty($lastInfo) === false): ?>
        <h1>Id de Servicio <?php echo $lastInfo[0]['id_servicio']; ?></h1>
        <h3>PID: <?php echo $lastInfo[0]['pid']; ?></h3>
        <h3>Timestamp: <?php echo $lastInfo[0]['timestamp']; ?></h3>
        <h3>Mensaje de Estado: <?php echo $mensajes[$lastInfo[0]['status']]; ?></h3>
    <?php endif; ?>
    <form method="POST" action="#">
        <input type="submit" value="Iniciar" name="actStart" />
        <input type="submit" value="Detener" name="actStop" />
        <input type="button" value="Refrescar" onclick="javascript:document.location.href = '<?php $_SERVER['REQUEST_URI']; ?>'" />
    </form>
</body>
</html>