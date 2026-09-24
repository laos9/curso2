# La carpeta docker

Aqui van los archivos que le dicen a Docker como construir y como correr tu proyecto.
Algunos ya vienen escritos porque son mecanicos. Los demas los escribes tu en clase.

| Archivo | De donde sale |
|---|---|
| `angular.nginx.conf` | Ya viene. Es la configuracion del servidor que sirve tu Angular compilado y manda `/api/` a Laravel y `/django/` a Django. Busca a los dos en cada peticion, asi que arranca aunque todavia no existan y los sigue encontrando si se recrean. |
| `postgres/crear-bases.sql` | Ya viene. Crea la segunda base de datos la primera vez que nace el volumen. |
| `variables.env.example` | Ya viene. Sus lineas se **agregan al final de tu `.env`**, el mismo que ya usa Laravel. No es un archivo aparte. |
| `desplegar.sh` | Ya viene. Lo corre tu pipeline: pide el despliegue al servidor, espera a que termine y comprueba que tu direccion responde. Lo usas en la **guia 04**. |
| `angular.Dockerfile` | Lo escribes en la **guia 01**. |
| `laravel.Dockerfile` | Lo escribes en la **guia 02**. |
| `django.Dockerfile` | Lo escribes en la **guia 02**. |
| `../compose.yaml` | Lo completas en la **guia 02**, a partir del esqueleto que ya esta en la raiz. |
| `../.gitlab-ci.yml` | Lo escribes en la **guia 03** y lo terminas en la **guia 04**. |
| `../compose.override.yaml` | Lo creas en la **guia 04**, cuando separas lo que corre en todas partes de lo que solo corre en tu maquina. |
| `php-base.Dockerfile` | Solo si haces el extra D: lo escribes en la **guia 05**, para que tu base de PHP viva en el registro de tu proyecto. |

Si te trabas, pregunta en el canal con la captura del error completo: se te
ensena el bloque que te falta. Lo que se evalua es que puedas explicar que hace
cada linea y por que esta en ese orden.
