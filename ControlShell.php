<?php
/**
 * Script de arranque manual de servicios
 * Se requiere de al menos 1 parametro para el id de servicio.
 * 
 * PHP Service Handler v0.1
 */
$idServicio = $argv[1];
if (empty($idServicio) === false) {
    require_once 'Servicio.class.php';
    $servicio = new Servicio($idServicio);
    $servicio->run();
} else {
    echo "Error. No se ha indicado un ID de Servicio.";
    exit(99);
}

