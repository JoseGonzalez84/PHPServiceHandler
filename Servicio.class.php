<?php
/**
 * Clase Servicio
 * 
 * PHP Service Handler v0.1
 */
class Servicio {

    public const STATUS_RUNNING = 1;
    public const STATUS_KILLING = 9;
    public const STATUS_STOPPED = 0;

    private mysqli $_database;
    private string $_idServicio;
    private string $_status;
    private string $_timestamp;
    private int $_pid;

    public function __construct(string $idServicio)
    {
        $this->_idServicio = $idServicio;
        // First check if this service is running.
        $ownData = $this->getLastInfo($this->_idServicio);
        // 30 segundos de cortesía por si hubiera algún delay, pero el proceso debe estar ya parado.
        if (empty($ownData) === false && (int) $ownData['status'] === self::STATUS_RUNNING && $ownData['timestamp'] < time() - 30) {
            $bbdd = $this->conexion();
            $bbdd->query(
                'UPDATE servicios SET pid = 0, status = "'.self::STATUS_STOPPED.'", timestamp = "'.time().'" WHERE id_servicio = "'.$this->_idServicio.'";'
            );
            $this->log("El Servicio {$this->_idServicio} debió pararse sin control.");
            $bbdd->close();
        }
    }

    private function conexion() {
        try {
            $this->_database = new mysqli('localhost', 'josegonzalez', '12345678', 'experimentos', '3307');
        } catch (Exception $ex) {
            $this->log("Error grave con la BBDD: {$ex->getMessage()}.");exit;
        }
        
        return $this->_database;
    }

    public function log(mixed $mensaje, bool $timestamp=true) {
        $fecha = ($timestamp === true) ? date('h:i:s').' ' : '';

        print_r("# {$fecha}").print_r($mensaje).print_r("\n");
    }

    /**
     * Funcion que hace correr el servicio.
     */
    public function run()
    {
        // Se establece el estado por defecto de ARRANCADO.
        $this->_status = self::STATUS_RUNNING;
        $this->_pid = $pid = posix_getpid();
        $this->log("Servicio {$this->_idServicio} iniciado con PID {$pid}.");
        while($this->_status == self::STATUS_RUNNING) {
            try {
                // Información del PID.
                $this->log('PID:'.$this->_pid);
                // Obtenemos la conexion a BBDD.
                $bbdd = $this->conexion();
                $tmpResult = $bbdd->query('SELECT status FROM servicios WHERE id_servicio = "'.$this->_idServicio.'";');
                $mensajeServicio = $tmpResult->fetch_array();
                if (empty($mensajeServicio) === true || $mensajeServicio === false) {
                    $this->log("Es la primera vez que se registra el servicio {$this->_idServicio}.");
                    $bbdd->query(
                        'INSERT INTO servicios (id_servicio, status, timestamp, pid) VALUES("'.$this->_idServicio.'", "'.self::STATUS_RUNNING.'", "'.time().'", '.$pid.')'
                    );
                } else {
                    $mensajeServicio = $mensajeServicio['status'];
                    $this->log($mensajeServicio);
                    $this->_timestamp = time();
                    switch ($mensajeServicio) {
                        case self::STATUS_STOPPED:
                            $bbdd->query(
                                'UPDATE servicios SET status = "'.self::STATUS_RUNNING.'", timestamp = "'.time().'", pid = '.$pid.' WHERE id_servicio = "'.$this->_idServicio.'";'
                            );
                            break;
                        case self::STATUS_KILLING:
                            $this->_status = self::STATUS_STOPPED;
                            $bbdd->query(
                                'UPDATE servicios SET pid = 0, status = "'.self::STATUS_STOPPED.'", timestamp = "'.time().'" WHERE id_servicio = "'.$this->_idServicio.'";'
                            );
                            $this->log("Servicio {$this->_idServicio} detenido.");
                            break 2;
                        default:
                            $bbdd->query(
                                'UPDATE servicios SET timestamp = "'.time().'", pid = '.$pid.' WHERE id_servicio = "'.$this->_idServicio.'";'
                            );
                            break;
                    }
                }
    
                // Cerramos conexion.
                $bbdd->close();
                // Lapso de tiempo para ejecutar.
                sleep(5);
            } catch (Exception $ex) {
                $this->log("Error fatal al correr el servicio: {$ex->getMessage()}");
                exit;
            }
            
        }
    }

    public function start()
    {
        $ownData = $this->getLastInfo();
        $output = [];
        $returnCode = '';
        if (empty($ownData) === false && $ownData['status'] !== self::STATUS_RUNNING) {
            exec('php ControlShell.php '.$this->_idServicio.' > /dev/null 2>&1 &', $output, $returnCode);
        } else {
            $output[] = 'Ya arrancado';
            $returnCode = 0;
        }
        
        return [$output, $returnCode];
    }

    public function stop()
    {
        $ownData = $this->getLastInfo();
        $bbdd = $this->conexion();
        $bbdd->query(
            'UPDATE servicios SET status = "'.self::STATUS_KILLING.'", timestamp = "'.time().'" WHERE id_servicio = "'.$this->_idServicio.'" AND pid = '.$ownData['pid'].';'
        );
        $bbdd->close();
    }
    
    public function getLastInfo(string $idServicio='') {
        if (empty($idServicio) === true) {
            $idServicio = $this->_idServicio;
        }

        $bbdd = $this->conexion();
        $result = $bbdd->query('SELECT * FROM servicios WHERE id_servicio = "'.$idServicio.'" LIMIT 1');
        $output = ($result !== false) ? $result->fetch_array() : [];
        $bbdd->close();

        return $output;
    }

    public function getId() {
        return $this->_idServicio;
    }

    public function getTimestamp() {
        return $this->_timestamp;
    }

    public function getPID() {
        return $this->_pid;
    }
}