<?php
/**
 * Web de control de servicios
 * 
 * PHP Service Handler v0.2
 */
require_once 'Servicio.class.php';
// Modo estricto.
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Variables iniciales.
const MENSAJES = [
    Servicio::STATUS_RUNNING => 'Activo',
    Servicio::STATUS_KILLING => 'Deteniendo',
    Servicio::STATUS_STOPPED => 'Parado',
];
// Funciones auxiliares.
function generateRandomUUID(mysqli $bbdd) {
    $resultServices = $bbdd->query('SELECT id_servicio FROM servicios;');
    $services = $resultServices->fetch_array(MYSQLI_NUM);
    $newUUID = bin2hex(openssl_random_pseudo_bytes(16));
    if (in_array($newUUID, $services) === true) {
        $newUUID = generateRandomUUID($bbdd);
    }

    return $newUUID;
}

// Inicio del programa.
$bbdd = new mysqli('localhost', 'josegonzalez', '12345678', 'experimentos', '3307');

if (isset($_POST['addService']) === true) {
    $randomUUID = generateRandomUUID($bbdd);
    $newService = new Servicio($randomUUID);
    $newService->run();
}

$resultServices = $bbdd->query('SELECT id_servicio FROM servicios;');
$bbdd->close();
$servicesHTML = [];
while ($idServicioArray = $resultServices->fetch_array(MYSQLI_NUM)) {
    $idServicio = $idServicioArray[0];
    $serviceHTML = '';
    $servicioInicio = new Servicio($idServicio);
    $lastInfo = $servicioInicio->getLastInfo($idServicio);
    $statusMessage = MENSAJES[$lastInfo['status']];
    $timestamp = date('d.m.Y H:i:s', $lastInfo['timestamp']);
    $pid = $lastInfo['pid'] === 0 ? 'N/A' : $lastInfo['pid'];

    if (isset($_POST['idServicio']) === true && $_POST['idServicio'] === $idServicio) {
        if (isset($_POST['actStart']) === true) {
            $salida = $servicioInicio->start();
            $servicio = new Servicio($idServicio);
            $lastInfo = $servicio->getLastInfo($idServicio);
            $statusMessage = 'Iniciando';
        } else if (isset($_POST['actStop']) === true) {
            $servicioInicio->stop($lastInfo['pid']);
            $statusMessage = MENSAJES[Servicio::STATUS_KILLING];
        } else if (isset($_POST['actStopDelete']) === true) {
            $servicioInicio->stop($lastInfo['pid']);
        }
    }

    $serviceHTML .= '<div class="box" id="box_servicio_'.$idServicio.'">';
    $serviceHTML .= '<h1>Id de Servicio: </h1>';
    $serviceHTML .= '<h1>'.$idServicio.'</h1>';
    if (empty($lastInfo) === true) {
        $serviceHTML .= '<h3>Este servicio aún no tiene datos</h3>';
    } else {
        $serviceHTML .= '<h3>PID: '.$pid.'</h3>';
        $serviceHTML .= '<h3>Ultima actualización: '.$timestamp.'</h3>';
        $serviceHTML .= '<h3>Estado de servicio: '.$statusMessage.'</h3>';
    }

    $serviceHTML .= '<form method="POST" action="#">';
    $serviceHTML .= '<input type="hidden" value="'.$idServicio.'" name="idServicio" />';
    $serviceHTML .= '<input type="submit" value="Iniciar" name="actStart" />';
    $serviceHTML .= '<input type="submit" value="Detener" name="actStop" />';
    $serviceHTML .= '<input type="submit" value="Detener y Borrar servicio" name="actStopDelete" />';
    $serviceHTML .= '</form>';
    $serviceHTML .= '</div>';

    $servicesHTML[$idServicio] = $serviceHTML;
    unset($servicioInicio);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba servicios</title>
    <style>
        * {
            font-family: 'Consolas', 'Courier New', Courier, monospace;
        }

        .box {
            border: 1px solid;
            border-radius: 18px;
            padding: 1em;
            width: 35em;
            margin: 1em;    
        }

        .box form {
            display: flex;
            flex-direction: row;
            justify-content: flex-end;
        }

        .box form input {
            margin-left: 0.5em;
        }

        .container {
            display: flex;
        }

    </style>
</head>
<body>
    <form method="POST" action="#">
        <input type="submit" value="Añadir servicio" name="addService" />
        <input type="button" value="Refrescar" onclick="javascript:document.location.href = '<?php $_SERVER['REQUEST_URI']; ?>'" />
    </form>
    <div class="container">
        <?php 
        foreach($servicesHTML as $keyService => $valService) {
            echo $valService;
        }  
        ?>
    </div>
</body>
</html>