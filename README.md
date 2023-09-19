# PHPServiceHandler 0.2.2

## Info

El proyecto se trata de un controlador de servicios. Con él, se pueden arrancar, monitorizar o parar servicios que corran bajo PHP.

Por ejemplo, si tienes corriendo en infinito un script con un while, puedes meter su lógica dentro de `Servicio.class.php` (o llamarlo) para que haga esa operativa.
Luego, desde `ControlWeb.php` puedes monitorizar su estado. Puedes arrancarlo, pararlo, ver su última actualización

## Versiones
### 0.2.2

Se ha controlado si el sistema es Windows, para utilizar un control de PID que no sea con POSIX. Aunque por lo visto,
todavía falla.

Se ha hecho mejora de código para que cumpla los estándares.

La clase obtiene mas responsabilidad pero con la idea de tener menos dependencia del exterior.
Ahora genera su propio UUID en caso de ser necesario, y la conexión a la BBDD la comparte para no duplicar claves.

### 0.2

Puede gestionar varios servicios a la vez y se ha mejorado el control sobre los mismos.

## Requisitos

- PHP 8
- MySQL
- Apache u otro servidor web.

## TO-DOs

Se podría hacer que en lugar de apoyarse en una BBDD, utilizara un sencillo TXT que pudiera alojarse en `/tmp` por ejemplo. Así se evitaría la dependencia de la BBDD (sobre todo si estamos usando otro SGBD).

Habría que ir añadiendo mas opciones de personalización. Por ejemplo, el sleep está definido a 5 segundos, pero puede que resulte excesivo.

El identificador de servicio es muy básico. Debiera ser algo mas complejo.

ControlShell está muy básico. Debiera aceptar parámetros, sobre todo si es para lanzarlo vía shell.

El ControlWeb debiera tener una interfaz algo mas depurada.

## Licencia

Este software no tiene garantía de ningún tipo. Se entrega tal cual. No se pueden reclamar derechos sobre el uso del mismo. Ni se permite usarlo para hacer el mal.
