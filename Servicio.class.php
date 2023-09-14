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
    private int $_idServicio;
    private string $_status;
    private string $_timestamp;
    private int $_pid;

    public function __construct(int $id_servicio)
    {
        $this->_idServicio = $id_servicio;
    }

    private function conexion() {
        try {
            $this->_database = new mysqli('localhost', 'josegonzalez', '12345678', 'experimentos', '3307');
        } catch (Exception $ex) {
            echo "Error grave: {$ex->getMessage()}.";exit;
        }
        
        return $this->_database;
    }

    public function log(mixed $mensaje, bool $timestamp=true) {
        $fecha = ($timestamp === true) ? date('h:i:s').' ' : '';

        print_r("# {$fecha}").print_r($mensaje).print_r("\n");
    }

    public function run()
    {
        $this->_status = self::STATUS_RUNNING;
        $counter = 0;
        while($this->_status == self::STATUS_RUNNING) {
            $bbdd = $this->conexion();
            $this->_pid = $pid = posix_getpid();
            $this->log('PID:'.$this->_pid);
            $tmpResult = $bbdd->query('SELECT status FROM servicios WHERE id_servicio = '.$this->_idServicio);
            $mensajeServicio = $tmpResult->fetch_array();
            if (empty($mensajeServicio) === true || $mensajeServicio === false) {
                $this->log('Mensaje servicio es falso');
                $this->_database->query(
                    'INSERT INTO servicios (id_servicio, status, timestamp, pid) VALUES('.$this->_idServicio.', "'.self::STATUS_RUNNING.'", "'.time().'", '.$pid.')'
                );
            } else {
                $mensajeServicio = $mensajeServicio['status'];
                $this->log($mensajeServicio);
                $this->_timestamp = time();
                switch ($mensajeServicio) {
                    case self::STATUS_STOPPED:
                        $bbdd->query(
                            'UPDATE servicios SET status = "'.self::STATUS_RUNNING.'", timestamp = "'.time().'", pid = '.$pid.' WHERE id_servicio = '.$this->_idServicio.';'
                        );
                        break;
                    case self::STATUS_KILLING:
                        $this->_status = self::STATUS_STOPPED;
                        $bbdd->query(
                            'UPDATE servicios SET pid = 0, status = "'.self::STATUS_STOPPED.'", timestamp = "'.time().'" WHERE id_servicio = '.$this->_idServicio.';'
                        );
                        $this->log("Servicio {$this->_idServicio} detenido.");
                        break 2;
                    default:
                        $bbdd->query(
                            'UPDATE servicios SET timestamp = "'.time().'", pid = '.$pid.' WHERE id_servicio = '.$this->_idServicio.';'
                        );
                        break;
                }
            }

            $bbdd->close();

            if ($counter++ === 0) {
                $this->log("Servicio {$this->_idServicio} iniciado con PID {$pid}.");
            }

            sleep(5);
        }
    }

    public function start()
    {
        $output = [];
        $returnCode = '';
        exec('php ControlShell.php > /dev/null 2>&1 &', $output, $returnCode);
        return [$output, $returnCode];
    }

    public function stop()
    {
        $ownData = $this->getLastInfo($this->_idServicio);
        $bbdd = $this->conexion();
        $bbdd->query(
            'UPDATE servicios SET status = "'.self::STATUS_KILLING.'", timestamp = "'.time().'" WHERE id_servicio = '.$this->_idServicio.' AND pid = '.$ownData[0]['pid'].';'
        );
        $bbdd->close();
    }
    
    public function getLastInfo(int $id_servicio) {
        $output = [];

        $bbdd = $this->conexion();
        $result = $bbdd->query('SELECT * FROM servicios WHERE id_servicio = '.$id_servicio);
        if ($result !== false) {
            while ($salida = $result->fetch_array()) {
                $output[] = $salida;
            }
        }
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