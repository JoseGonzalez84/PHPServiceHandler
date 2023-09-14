<?php
/**
 * Script de arranque manual de servicios
 * 
 * PHP Service Handler v0.1
 */
require_once 'Servicio.class.php';
$servicio = new Servicio(1);
$servicio->run();
