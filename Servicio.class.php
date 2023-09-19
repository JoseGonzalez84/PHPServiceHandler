<?php
/**
 * Clase Servicio
 *
 * PHP Service Handler v0.1
 *
 * @category Servicios
 * @package  Sin_Definir
 * @author   José González Silva <josegs84@gmail.com>
 * @license  Sin garantía. Libre uso siempre que no sea hacer el mal.
 * @version  Release: <package_version>
 * @link     n/a
 */

class Servicio
{
    public const STATUS_RUNNING = 1;
    public const STATUS_KILLING = 9;
    public const STATUS_STOPPED = 0;
    private const MYSQL_VALUES = [
        'host' => '127.0.0.1',
        'user' => 'josegonzalez',
        'pass' => '12345678',
        'ddbb' => 'experimentos',
        'port' => '3306'
    ];

    private bool $_isWindows;

    private mysqli $_database;
    private string $_idServicio;
    private string $_status;
    private string $_timestamp;
    private int $_pid;

    /**
     * Constructor.
     *
     * @param string $idServicio Identificador del servicio.
     */
    public function __construct(string $idServicio='')
    {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
        $this->_isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
        var_dump($this->_isWindows);
        // Iniciamos la conexion.
        $bbdd = $this->conexion();
        // Si no viene informado $idServicio, se genera un nuevo UUID.
        $this->_idServicio = (empty($idServicio) === true) ? $this->_generateUUID($bbdd) : $idServicio;
        // Primera comprobación de que el servicio está corriendo.
        $ownData = $this->getLastInfo($this->_idServicio);
        // 30 segundos de cortesía por si hubiera algún delay, pero el proceso debe estar ya parado.
        if (empty($ownData) === false && (int) $ownData['status'] === self::STATUS_RUNNING && $ownData['timestamp'] < time() - 30) {
            $bbdd->query(
                'UPDATE servicios SET pid = 0, status = "'.self::STATUS_STOPPED.'", timestamp = "'.time().'" WHERE id_servicio = "'.$this->_idServicio.'";'
            );
            $this->log("El Servicio {$this->_idServicio} debió pararse sin control.");
        }
        // Cerramos la conexion.
        $bbdd->close();
    }

    /**
     * Conexión con la BBDD.
     *
     * @return mysqli
     */
    static function conexion()
    {
        try {
            $database = new mysqli(
                self::MYSQL_VALUES['host'],
                self::MYSQL_VALUES['user'],
                self::MYSQL_VALUES['pass'],
                self::MYSQL_VALUES['ddbb'],
                self::MYSQL_VALUES['port']
            );
        } catch (Exception $ex) {
            Servicio::log("Error grave con la BBDD: {$ex->getMessage()}.");
            exit;
        }

        return $database;
    }


    /**
     * Log de sucesos.
     *
     * @param mixed $mensaje   Mensaje a informar.
     * @param bool  $timestamp Mostrar el momento del mensaje. True por defecto.
     *
     * @return void.
     */
    static function log(mixed $mensaje, bool $timestamp=true)
    {
        $fecha = ($timestamp === true) ? date('h:i:s').' ' : '';

        print_r("# {$fecha}").print_r($mensaje).print_r("\n");
    }


    /**
     * Genera un UUID aleatorio criptográficamente seguro. Recursiva.
     *
     * @param mysqli $bbdd Base de datos para hacer consultas.
     *
     * @return string
     */
    private function _generateUUID(mysqli $bbdd)
    {
        $resultServices = $bbdd->query('SELECT id_servicio FROM servicios;');
        $services = $resultServices->fetch_array(MYSQLI_NUM);
        var_dump($services);
        $newUUID = bin2hex(openssl_random_pseudo_bytes(16));
        if (empty($services) === false && in_array($newUUID, $services) === true) {
            $newUUID = $this->_generateUUID($bbdd);
        }

        return $newUUID;
    }


    /**
     * Funcion que hace correr el servicio.
     *
     * @return void.
     */
    public function run()
    {
        // Se establece el estado por defecto de ARRANCADO.
        $this->_status = self::STATUS_RUNNING;
        $this->_pid = $this->_isWindows === true ? getmypid() : posix_getpid();
        $this->log("Servicio {$this->_idServicio} iniciado con PID {$this->_pid}.");
        while ($this->_status == self::STATUS_RUNNING) {
            try {
                // Información del PID.
                $this->log('PID:'.$this->_pid);
                // Obtenemos la conexion a BBDD.
                $bbdd = Servicio::conexion();
                $tmpResult = $bbdd->query('SELECT status FROM servicios WHERE id_servicio = "'.$this->_idServicio.'";');
                $mensajeServicio = $tmpResult->fetch_array();
                if (empty($mensajeServicio) === true || $mensajeServicio === false) {
                    $this->log("Es la primera vez que se registra el servicio {$this->_idServicio}.");
                    $bbdd->query(
                        'INSERT INTO servicios (id_servicio, status, timestamp, pid) VALUES("'.$this->_idServicio.'", "'.self::STATUS_RUNNING.'", "'.time().'", '.$this->_pid.')'
                    );
                } else {
                    $mensajeServicio = $mensajeServicio['status'];
                    $this->log($mensajeServicio);
                    $this->_timestamp = time();
                    switch ($mensajeServicio) {
                    case self::STATUS_STOPPED:
                        $bbdd->query(
                            'UPDATE servicios SET status = "'.self::STATUS_RUNNING.'", timestamp = "'.time().'", pid = '.$this->_pid.' WHERE id_servicio = "'.$this->_idServicio.'";'
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
                            'UPDATE servicios SET timestamp = "'.time().'", pid = '.$this->_pid.' WHERE id_servicio = "'.$this->_idServicio.'";'
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


    /**
     * Inicia un servicio.
     *
     * @return array.
     */
    public function start()
    {
        $ownData = $this->getLastInfo();
        $output = [];
        $returnCode = '';
        if (empty($ownData) === false && $ownData['status'] !== self::STATUS_RUNNING) {
            exec('php ControlShell.php '.$ownData['id_servicio'].' > /dev/null 2>&1 &', $output, $returnCode);
        } else {
            $output[] = 'Ya arrancado';
            $returnCode = 0;
        }
        return [$output, $returnCode];
    }


    /**
     * Detiene un servicio.
     *
     * @return void.
     */
    public function stop()
    {
        $ownData = $this->getLastInfo();
        $bbdd = $this->conexion();
        echo 'UPDATE servicios SET status = "'.self::STATUS_KILLING.'", timestamp = "'.time().'" WHERE id_servicio = "'.$this->_idServicio.'" AND pid = '.$ownData['pid'].';'; 
        $bbdd->query(
            'UPDATE servicios SET status = "'.self::STATUS_KILLING.'", timestamp = "'.time().'" WHERE id_servicio = "'.$this->_idServicio.'" AND pid = '.$ownData['pid'].';'
        );
        $bbdd->close();
    }


    /**
     * Obtiene la última información del servicio.
     *
     * @param string $idServicio Opcional. Indica el servicio del que queremos información.
     *
     * @return array.
     */
    public function getLastInfo(string $idServicio='')
    {
        if (empty($idServicio) === true) {
            $idServicio = $this->_idServicio;
        }

        $bbdd = $this->conexion();
        $result = $bbdd->query('SELECT * FROM servicios WHERE id_servicio = "'.$idServicio.'" LIMIT 1');
        $output = ($result !== false) ? $result->fetch_array(MYSQLI_ASSOC) : [];
        $bbdd->close();

        return $output;
    }
}
