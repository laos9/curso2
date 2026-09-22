# Django por dentro (la versión escrita de la clase)

Esta lectura desarrolla lo que la clase ve de pasada. La clase va rápido porque en dos horas hay que escribir una API completa; aquí está lo que se queda en el tintero, con el detalle que no cabe en un slide.

No hace falta leerla de corrido. Está pensada para dos momentos: **antes de la clase**, las secciones 1 a 6, para llegar sabiendo qué es cada archivo y de dónde sale la configuración; y **cuando algo no cuadre**, buscando la sección que toca.

Todo lo que aparece aquí es código que vas a tener enfrente, no ejemplos inventados.

---

## Índice

1. [Python, lo justo para leer Django](#1-python-lo-justo-para-leer-django)
2. [El entorno virtual, a fondo](#2-el-entorno-virtual-a-fondo)
3. [La estructura de un proyecto, archivo por archivo](#3-la-estructura-de-un-proyecto-archivo-por-archivo)
4. [Proyecto y app: la división que Laravel no tiene](#4-proyecto-y-app-la-división-que-laravel-no-tiene)
5. [`settings.py`, sección por sección](#5-settingspy-sección-por-sección)
6. [La configuración que cambia entre máquinas](#6-la-configuración-que-cambia-entre-máquinas)
7. [El viaje de una petición](#7-el-viaje-de-una-petición)
8. [Modelos y migraciones](#8-modelos-y-migraciones)
9. [El serializer](#9-el-serializer)
10. [Vistas, ViewSets y el router](#10-vistas-viewsets-y-el-router)
11. [Permisos](#11-permisos)
12. [Glosario](#12-glosario)

---

## 1. Python, lo justo para leer Django

Para leer un archivo de Django sin tropezar bastan seis cosas. Estas son.

### 1.1 Lo que desaparece

```php
<?php
$titulo = "Simulacro";
$activo = true;
if ($activo) {
    echo strtoupper($titulo);
}
```

```python
titulo = "Simulacro"
activo = True
if activo:
    print(titulo.upper())
```

Se van el `$`, el `;` y las llaves. Llegan los dos puntos al final de la línea que abre un bloque, y la sangría.

`True`, `False` y `None` van con mayúscula inicial. `None` es el `null` de PHP.

### 1.2 La sangría es sintaxis

En PHP la sangría es cortesía: el código funciona igual sin ella. En Python **la sangría es lo que define dónde empieza y termina un bloque**. Cambiarla cambia el programa.

```python
total = 0
for aviso in avisos:
    total = total + 1
    print(total)      # dentro del for: imprime en cada vuelta
```

```python
total = 0
for aviso in avisos:
    total = total + 1
print(total)          # fuera del for: imprime una sola vez, al final
```

Son dos programas distintos y la única diferencia son cuatro espacios. Si mezclas tabuladores y espacios, Python te responde `IndentationError` o, peor, `TabError`. La convención es **cuatro espacios**, nunca tabuladores, y VS Code ya lo hace por ti en archivos `.py`.

### 1.3 Listas y diccionarios

PHP tiene un solo tipo, el array, que sirve para las dos cosas. Python los separa:

```python
roles = ["admin", "autor", "lector"]          # lista: orden, se accede por posicion
roles[0]                                       # "admin"

aviso = {"titulo": "Simulacro", "id": 9}      # diccionario: se accede por clave
aviso["titulo"]                                # "Simulacro"
```

| PHP | Python |
|---|---|
| `['a', 'b']` | `["a", "b"]` (lista) |
| `['k' => 'v']` | `{"k": "v"}` (diccionario) |
| `count($x)` | `len(x)` |
| `$x[] = 'nuevo'` | `x.append("nuevo")` |

En `settings.py` vas a ver las dos todo el tiempo: `INSTALLED_APPS` es una lista y `DATABASES` es un diccionario de diccionarios.

### 1.4 Módulos, paquetes y el archivo vacío que confunde a todos

Un **módulo** es un archivo `.py`. Un **paquete** es una carpeta de módulos. Para importar de un módulo:

```python
from django.db import models        # del modulo django.db, trae "models"
from .models import Aviso           # del models.py de esta misma carpeta
import os                           # el modulo completo, se usa como os.getenv(...)
```

El punto de `from .models` significa "de aquí mismo". Es lo que en PHP sería un `use App\Models\Post;` pero relativo a la carpeta.

Y ahora el archivo que desconcierta a todo el mundo: **`__init__.py`**. Vas a encontrar dos, los dos **vacíos, de cero bytes**:

```
avisos/__init__.py      0 bytes
config/__init__.py      0 bytes
```

No están de adorno ni son basura. Su presencia es lo que le dice a Python **"esta carpeta es un paquete, puedes importar de ella"**. Sin `avisos/__init__.py`, la línea `from avisos.models import Aviso` falla con `ModuleNotFoundError`, aunque el archivo `models.py` esté ahí.

Es el equivalente conceptual del `autoload` PSR-4 de tu `composer.json`: la manera de declarar que una carpeta contiene código importable. La diferencia es que Laravel lo declara una vez en un archivo de configuración, y Python lo declara con un archivo vacío en cada carpeta.

En Python 3 hay paquetes que funcionan sin `__init__.py`, pero Django los crea siempre y tú también deberías. Si creas una carpeta nueva de código y los imports fallan sin razón aparente, ese archivo es lo primero que hay que revisar.

### 1.5 Clases, `self` y `__init__`

```php
class Aviso
{
    public $titulo;

    public function resumen()
    {
        return substr($this->titulo, 0, 40);
    }
}
```

```python
class Aviso:
    titulo = ""

    def resumen(self):
        return self.titulo[:40]
```

Tres diferencias que importan:

1. **`self` se escribe.** En PHP `$this` aparece por arte de magia dentro del método; en Python **el primer parámetro de todo método es el objeto**, y por convención se llama `self`. No es opcional: si lo olvidas, Python responde `TypeError: resumen() takes 0 positional arguments but 1 was given`. Es de los errores más comunes al empezar.
2. **`self.` es obligatorio para leer los atributos.** `titulo` a secas sería una variable local; `self.titulo` es el atributo del objeto.
3. **`__init__` es el constructor.** Los nombres rodeados de dos guiones bajos son métodos especiales que Python llama solo. `__str__` es otro que sí vas a usar: define cómo se ve el objeto cuando se imprime, y es lo que hace que el admin muestre el título del aviso en vez de `Aviso object (1)`.

### 1.6 Decoradores

Un decorador es una línea que empieza con `@` justo encima de una función o una clase, y que **envuelve** lo que sigue para agregarle comportamiento.

```python
@action(detail=False)
def mios(self, request):
    ...
```

Ya los conoces de otro lado: son los **atributos de PHP 8**, los que en Laravel se ven así:

```php
#[Route('/avisos')]
public function index() { ... }
```

En la sesión de Angular viste la misma idea con `@Component` y `@Injectable`. La mecánica es idéntica en los tres lenguajes: una anotación encima de algo, que un framework lee para decidir qué hacer con ello.

En el código de hoy aparecen pocos: `@action` en el ViewSet y `@property` en los modelos. Basta con reconocerlos.

---

## 2. El entorno virtual, a fondo

### 2.1 El problema que resuelve

En PHP, cada proyecto tiene su `vendor/` y ahí viven sus dependencias, con sus versiones. Dos proyectos con distinta versión de Laravel conviven sin enterarse uno del otro.

Python, de fábrica, **no hace eso**. `pip install django` instala Django para **todo el sistema**. Si tienes un proyecto con Django 4.2 y otro con Django 5.2, el segundo rompe al primero. Ese es el problema.

El **entorno virtual** es la solución: una carpeta con su propio Python y sus propios paquetes, aislada del sistema y de los demás proyectos. Es tu `vendor/`, con una diferencia importante que viene ahora.

### 2.2 Crearlo

```bash
python3 -m venv .venv
```

`python3 -m venv` significa "corre el módulo `venv` del Python del sistema", y `.venv` es el nombre de la carpeta que va a crear. El punto inicial la hace oculta; el nombre es convención, no obligación.

**Aquí está la diferencia real con Composer**: `composer install` crea `vendor/` solo, porque lee tu `composer.json` y sabe qué hacer. En Python **tú creas el entorno primero, a mano, y después instalas dentro**. Son dos pasos, no uno.

### 2.3 Qué hay dentro

```
.venv/
├── bin/
│   ├── python          <- el Python de ESTE proyecto
│   ├── pip             <- el pip de ESTE proyecto
│   └── activate        <- el script que cambia tu terminal
├── lib/
│   └── python3.11/
│       └── site-packages/   <- aqui viven django, rest_framework, etc.
└── pyvenv.cfg
```

`site-packages/` es literalmente el `vendor/` de este proyecto. Si entras a mirar, ahí está el código fuente de Django completo, que puedes abrir y leer.

### 2.4 Instalar dependencias

```bash
.venv/bin/pip install -r requirements.txt
```

`requirements.txt` es tu `composer.json`. El del curso son tres líneas:

```
django==4.2.*
djangorestframework==3.15.*
django-cors-headers
```

| Composer | pip |
|---|---|
| `composer.json` | `requirements.txt` |
| `composer.lock` | no hay equivalente directo de fábrica |
| `composer install` | `pip install -r requirements.txt` |
| `composer require x` | `pip install x` (y agregarlo tú al archivo) |
| `composer show` | `pip list` |

Dos diferencias que conviene saber:

- **No hay `composer.lock`.** `pip install` no deja constancia de las versiones exactas que instaló. Lo más parecido es `pip freeze`, que **lista lo que hay instalado ahora mismo** con sus versiones exactas:

  ```bash
  .venv/bin/pip freeze > requirements.txt
  ```

  Esto sobrescribe el archivo con la foto actual. Es útil y es peligroso a partes iguales: congela también las dependencias de tus dependencias, y el archivo pasa de tres líneas legibles a cuarenta. Herramientas modernas como Poetry o uv resuelven esto; el proyecto real y el curso usan `requirements.txt` a secas.
- **`pip install x` no toca `requirements.txt`.** `composer require` sí actualiza tu `composer.json`. En pip, si instalas algo y olvidas anotarlo, funciona en tu máquina y falla en la de al lado. Es una de las causas más comunes del clásico "en mi máquina sí corre".

### 2.5 `activate`, y por qué el curso no lo usa

Hay dos maneras de usar el entorno. La primera es **activarlo**:

```bash
source .venv/bin/activate
```

Eso cambia tu terminal: el prompt gana un `(.venv)` delante y, a partir de ahí, `python` y `pip` a secas son los del entorno. Para salir, `deactivate`.

```
vscode@abc123:~/api-django$ source .venv/bin/activate
(.venv) vscode@abc123:~/api-django$ python manage.py runserver
```

La segunda es **no activarlo y llamar al binario directo**, que es lo que hacen todos los comandos del curso:

```bash
.venv/bin/python manage.py runserver 0.0.0.0:8001
```

Las dos hacen exactamente lo mismo. El curso usa la segunda por una razón práctica: **la activación vive en una terminal**. Si abres otra pestaña, o el contenedor se reinicia, o copias un comando de la guía dos días después, la activación ya no está y el comando falla con `ModuleNotFoundError: No module named 'django'`, un error que no dice en ningún lado "te faltó activar el entorno".

Escribir `.venv/bin/python` es más largo y no falla nunca. Cuando veas ese prefijo en las guías, ya sabes lo que significa: **"usa el Python de este proyecto, no el del sistema"**.

### 2.6 Qué no va a git

`.venv/` **nunca** va al repositorio, igual que `vendor/` y que `node_modules/`. Son cientos de megas, se reconstruyen en segundos y contienen rutas absolutas de tu máquina que no sirven en otra.

El `.gitignore` de la base ya lo excluye, junto con `db.sqlite3` y los `__pycache__/`. Esos últimos son carpetas que Python crea solo, con el código compilado a bytecode; aparecen sin que hagas nada y tampoco van a git.

---

## 3. La estructura de un proyecto, archivo por archivo

Este es el árbol de lo que tienes en `api-django/`. Cada archivo tiene un motivo.

```
api-django/
├── manage.py                 <- la puerta de entrada a todo comando
├── requirements.txt          <- las dependencias
├── .gitignore
├── db.sqlite3                <- la base (la crea migrate, no va a git)
├── .venv/                    <- el entorno virtual (no va a git)
│
├── config/                   <- EL PROYECTO: configuracion y rutas raiz
│   ├── __init__.py           <- vacio: marca la carpeta como paquete
│   ├── settings.py           <- toda la configuracion
│   ├── urls.py               <- las rutas raiz
│   ├── wsgi.py               <- por donde entra un servidor web clasico
│   └── asgi.py               <- lo mismo, para servidores asincronos
│
└── avisos/                   <- UNA APP: un area del sistema
    ├── __init__.py
    ├── apps.py               <- la ficha de identidad de la app
    ├── models.py             <- los modelos
    ├── admin.py              <- que se ve en el panel de administracion
    ├── views.py              <- los controladores
    ├── serializers.py        <- de objeto a JSON y de vuelta (lo agrega DRF)
    ├── permissions.py        <- las Policies (lo agregas tu)
    ├── tests.py              <- las pruebas
    └── migrations/           <- las migraciones, las escribe Django
        └── __init__.py
```

### 3.1 `manage.py`

Es tu `artisan`. Todo comando pasa por aquí:

| Laravel | Django |
|---|---|
| `php artisan migrate` | `manage.py migrate` |
| `php artisan make:migration` | `manage.py makemigrations` |
| `php artisan tinker` | `manage.py shell` |
| `php artisan serve` | `manage.py runserver` |
| `php artisan test` | `manage.py test` |
| `php artisan db:seed` | no hay equivalente directo |

Si abres el archivo, son quince líneas y solo una importa:

```python
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
```

Traducido: **"la configuración de este proyecto está en `config/settings.py`"**. Es la línea que ata todo lo demás. Si renombras la carpeta `config/`, esta línea es la que hay que cambiar.

Fíjate en que ya está usando una variable de entorno (`os.environ`) para decidirlo. Volvemos a eso en la sección 6.

### 3.2 `config/settings.py` y `config/urls.py`

La configuración y las rutas. Tienen su sección propia más abajo (la 5) y aparecen en toda la clase.

El puente rápido: `settings.py` es todo tu `config/` de Laravel en un solo archivo, y `config/urls.py` es tu `routes/web.php` más `routes/api.php`.

### 3.3 `config/wsgi.py` y `config/asgi.py`

Los dos archivos que nadie abre nunca y que conviene reconocer.

Son el punto de entrada cuando el proyecto corre **de verdad**, servido por Gunicorn, uWSGI o similar, no por `runserver`. WSGI es el estándar clásico de Python para "servidor web habla con aplicación"; ASGI es su versión moderna, que además admite peticiones asíncronas y websockets.

El equivalente en Laravel es `public/index.php`: ese archivo que existe, que arranca el framework y que jamás editas.

`runserver` no los usa (trae su propio servidor de desarrollo), así que en el curso no los vas a tocar. Están ahí porque un proyecto en producción los necesita.

### 3.4 Los archivos de la app

| Archivo | Qué es | Su pariente en Laravel |
|---|---|---|
| `models.py` | los modelos, **todos en un archivo** | `app/Models/*.php`, uno por modelo |
| `views.py` | los controladores | `app/Http/Controllers/*.php` |
| `serializers.py` | valida lo que entra y arma el JSON que sale | `FormRequest` más `Resource` |
| `permissions.py` | quién puede hacer qué | `app/Policies/*.php` |
| `admin.py` | el panel de administración | lo más cercano es Filament |
| `apps.py` | la ficha de la app | no tiene equivalente |
| `tests.py` | las pruebas | `tests/Feature/*.php` |
| `migrations/` | las migraciones | `database/migrations/` |

Dos costumbres que chocan al venir de Laravel:

1. **Un archivo por tipo de cosa, no uno por clase.** Los siete modelos de una app viven en el mismo `models.py`. En un proyecto grande eso se vuelve un archivo de mil líneas, y entonces se convierte en carpeta (`models/` con un `__init__.py` que importa los de dentro), pero el patrón por defecto es un archivo.
2. **Las migraciones no se escriben.** `migrations/` empieza con un solo archivo vacío, `__init__.py`. Los demás los genera Django leyendo tus modelos. Es lo contrario de Laravel, donde escribes la migración y el modelo por separado.

### 3.5 `apps.py`, el archivo raro

```python
from django.apps import AppConfig


class AvisosConfig(AppConfig):
    default_auto_field = 'django.db.models.BigAutoField'
    name = 'avisos'
```

Es la ficha de identidad de la app: cómo se llama y con qué configuración arranca. `default_auto_field` decide el tipo de la columna `id` que Django agrega solo a cada modelo (aquí, entero de 64 bits).

En el noventa por ciento de los casos no se toca. Existe porque Django permite que una app haga cosas al arrancar (registrar señales, por ejemplo), y ese código va en un método `ready()` de esta clase.

---

## 4. Proyecto y app: la división que Laravel no tiene

Esta es la idea más "de Django" de todas, y la que más desconcierta si vienes de Laravel.

En Laravel hay **una aplicación**. Los controladores van en `app/Http/Controllers`, los modelos en `app/Models`, y si el sistema crece, esas carpetas crecen.

En Django hay **un proyecto** que contiene **varias apps**:

- El **proyecto** (aquí, `config/`) es el pegamento: la configuración, las rutas raíz y los puntos de entrada. No contiene lógica de negocio.
- Una **app** es un área funcional completa y autocontenida: sus modelos, sus vistas, sus migraciones, sus pruebas.

En el curso hay una sola app, `avisos`, porque el sistema es un blog de avisos. Un sistema real se organiza distinto: un proyecto con siete u ocho apps, una por área del negocio (usuarios, roles, turnos, reportes, expedientes), cada una con su `models.py`, su `views.py` y sus migraciones propias.

Eso significa que al abrir un proyecto Django ajeno, **lo primero que ves es el mapa del negocio**, no una carpeta de controladores. Los nombres de las carpetas te dicen de qué trata el sistema.

### 4.1 Cómo se registra una app

Existir en el disco no basta: hay que anunciarla en `INSTALLED_APPS`, en `settings.py`:

```python
INSTALLED_APPS = [
    "rest_framework",
    "rest_framework.authtoken",
    "corsheaders",
    "avisos",                          # <- la tuya
    'django.contrib.admin',
    'django.contrib.auth',
    ...
]
```

Si una app no está en esa lista, Django **la ignora por completo**: `makemigrations` no ve sus modelos, el admin no la muestra, sus pruebas no corren. Y no da error, que es lo traicionero. Cuando `makemigrations` responda `No changes detected` y tú sepas que sí cambiaste el modelo, esta lista es lo primero que hay que mirar.

Fíjate en que en esa lista conviven tres cosas: apps de terceros (`rest_framework`, `corsheaders`), la tuya (`avisos`) y las del propio Django (`django.contrib.*`). **El admin, el sistema de usuarios y las sesiones de Django son apps**, exactamente igual que la tuya. Si quitas `django.contrib.admin` de la lista, el panel deja de existir. No hay magia en el framework: hay apps registradas.

Es el equivalente de los `providers` de Laravel, con la diferencia de que en Django el paquete de terceros y tu propio código se declaran en el mismo sitio y de la misma forma.

---

## 5. `settings.py`, sección por sección

Un solo archivo con toda la configuración. En Laravel esto está repartido en una docena de archivos dentro de `config/`; aquí es uno.

### `BASE_DIR`

```python
BASE_DIR = Path(__file__).resolve().parent.parent
```

La ruta absoluta a la raíz del proyecto, calculada a partir de dónde está este archivo. `__file__` es la ruta de `settings.py`, `.resolve()` la vuelve absoluta y cada `.parent` sube una carpeta: de `config/settings.py` a `config/`, y de ahí a la raíz.

Se usa para construir rutas sin escribirlas a mano, como la de la base de datos. Es el `base_path()` de Laravel.

### `SECRET_KEY`

```python
SECRET_KEY = 'django-insecure-tcwb@_1-&-cm8@w)84e7k1lh4bq5k)6id9osni$-b(bvq&k=lh'
```

La clave con la que Django firma las cookies de sesión, los tokens de recuperación de contraseña y la protección CSRF. Es tu `APP_KEY`.

Django la genera al crear el proyecto y **la escribe en el código**, con el prefijo `django-insecure-` como advertencia. En un proyecto de verdad esto no puede quedarse así: es el ejemplo central de la sección 6.

### `DEBUG`

```python
DEBUG = True
```

Con `True`, un error responde con la página amarilla de Django: la traza completa, el fragmento de código que falló, las variables locales en el momento del fallo y **la configuración entera del proyecto**, incluidas las credenciales de la base. Es magnífico para desarrollar y es una filtración de datos en producción.

Con `False`, el mismo error responde un 500 sobrio y el detalle va al log.

Es el `APP_DEBUG` de Laravel, y tiene el mismo riesgo.

### `ALLOWED_HOSTS`

```python
ALLOWED_HOSTS = []
```

La lista de nombres de dominio con los que el proyecto acepta que lo llamen. Con `DEBUG = True` Django la ignora y acepta `localhost` y `127.0.0.1`.

Con `DEBUG = False` y la lista vacía **el servidor ni siquiera arranca**:

```
CommandError: You must set settings.ALLOWED_HOSTS if DEBUG is False.
```

Es un momento clásico de desconcierto al desplegar por primera vez, y la razón por la que `DEBUG` y `ALLOWED_HOSTS` se mueven siempre juntos al entorno.

### `MIDDLEWARE`

```python
MIDDLEWARE = [
    "corsheaders.middleware.CorsMiddleware",
    'django.middleware.security.SecurityMiddleware',
    'django.contrib.sessions.middleware.SessionMiddleware',
    ...
]
```

Los middlewares, **y el orden importa**, igual que en tu `bootstrap/app.php`. Cada petición los atraviesa de arriba abajo, y cada respuesta de abajo arriba. Es la misma cebolla que viste con los interceptores de Angular en la sesión 8.

`CorsMiddleware` va primero a propósito: tiene que poder responder antes que nadie a las peticiones de comprobación que manda el navegador.

### `ROOT_URLCONF` y `WSGI_APPLICATION`

```python
ROOT_URLCONF = 'config.urls'
WSGI_APPLICATION = 'config.wsgi.application'
```

Dónde empiezan las rutas y cuál es el punto de entrada del servidor. Son las dos líneas que atan el proyecto a la carpeta `config/`.

### `DATABASES`

```python
DATABASES = {
    'default': {
        'ENGINE': 'django.db.backends.sqlite3',
        'NAME': BASE_DIR / 'db.sqlite3',
    }
}
```

Tu `config/database.php`. `default` es la conexión por defecto y puede haber más, igual que en Laravel.

El curso usa SQLite por comodidad. Un proyecto real usa PostgreSQL, y entonces este bloque tiene motor, nombre, usuario, contraseña, host y puerto, y **ninguno de esos valores se escribe aquí**: salen del entorno, que es justo la siguiente sección.

### `REST_FRAMEWORK`

```python
REST_FRAMEWORK = {
    "DEFAULT_AUTHENTICATION_CLASSES": [
        "rest_framework.authentication.TokenAuthentication",
        "rest_framework.authentication.SessionAuthentication",
    ],
    "DEFAULT_PAGINATION_CLASS": "rest_framework.pagination.PageNumberPagination",
    "PAGE_SIZE": 10,
}
```

La configuración de DRF, que se declara aquí y no en un archivo aparte. Estas cuatro líneas son, en Laravel, tu `config/sanctum.php` más el `paginate(10)` que escribiste en el controlador.

Eso último es un cambio de mentalidad: **la paginación es configuración del proyecto, no una decisión de cada endpoint**. Por eso el ViewSet de la clase cabe en siete líneas.

---

## 6. La configuración que cambia entre máquinas

Esta sección es la que más se echa en falta, y es la que separa un proyecto de juguete de uno real.

### 6.1 El problema

Mira otra vez estas dos líneas de tu `settings.py`:

```python
SECRET_KEY = 'django-insecure-tcwb@_1-&-cm8@w)84e7k1lh4bq5k)6id9osni$-b(bvq&k=lh'
DEBUG = True
```

Las dos están **escritas en el código**, y el código va a git. Eso trae tres problemas:

1. **La clave secreta queda en el repositorio**, visible para cualquiera que tenga acceso, y en su historial para siempre aunque la borres después.
2. **`DEBUG = True` viaja al servidor** salvo que alguien se acuerde de cambiarlo a mano al desplegar.
3. **Los valores que cambian entre máquinas no pueden cambiar**: la base de datos de tu portátil no es la del servidor, pero el archivo es el mismo.

Tú ya resolviste esto en Laravel sin pensarlo, porque Laravel te lo da hecho.

### 6.2 Cómo lo hace Laravel

```
.env                          <- valores de ESTA maquina, fuera de git
.env.example                  <- la plantilla, sin valores, si va a git
config/app.php                <- lee del entorno: env('APP_DEBUG', false)
```

```php
// config/app.php
'debug' => env('APP_DEBUG', false),
```

Tres piezas: un archivo `.env` con los valores de esta máquina que **no va a git**, los archivos de `config/` que leen de ahí con `env()`, y el código de la aplicación que nunca llama a `env()` directamente sino a `config('app.debug')`.

### 6.3 Cómo lo hace Django

Aquí viene el detalle importante: **Django no trae nada de esto de fábrica**. No hay `.env`, no hay función `env()`, no hay `config()`. Lo que hay es Python puro y una convención.

La herramienta base es `os.getenv`, que lee una **variable de entorno** del sistema:

```python
import os

SECRET_KEY = os.getenv("SECRET_KEY")
DEBUG = os.getenv("DEBUG", "False") == "True"
ALLOWED_HOSTS = os.getenv("ALLOWED_HOSTS", "localhost,127.0.0.1").split(",")
```

Dos cosas que saltan a la vista:

- `os.getenv("SECRET_KEY")` devuelve `None` si la variable no existe.
- **Todas las variables de entorno son texto.** No existe el booleano `True` en el entorno: existe la cadena `"True"`. Por eso la segunda línea compara contra texto. Si escribieras `DEBUG = os.getenv("DEBUG")`, la cadena `"False"` sería verdadera para Python (toda cadena no vacía lo es) y tendrías el modo de depuración encendido en producción creyendo lo contrario.

Para que esas variables existan sin escribirlas a mano en cada terminal, se usa un paquete que lea un archivo `.env`. El más común es `python-dotenv`:

```python
import os
from dotenv import load_dotenv

load_dotenv()          # lee el archivo .env y mete sus valores en el entorno

SECRET_KEY = os.getenv("SECRET_KEY")
```

```
# .env, fuera de git
SECRET_KEY=una-clave-larga-y-aleatoria
DEBUG=True
DATABASE_NAME=avisos
DATABASE_USER=avisos
DATABASE_PASSWORD=...
```

Con eso, `settings.py` queda igual en todas las máquinas y lo que cambia es el `.env` de cada una. Es exactamente el modelo de Laravel, montado a mano.

### 6.4 Cómo se ve en un proyecto real

Así es como está resuelto en un proyecto Django en producción, y es el patrón que vas a reconocer:

```python
from dotenv import load_dotenv

load_dotenv()

SECRET_KEY = os.getenv("SECRET_KEY", "defaultsecretkey")

DATABASES = {
    'default': {
        'ENGINE': os.getenv("DATABASE_ENGINE", "django.db.backends.postgresql"),
        'NAME': os.getenv("DATABASE_NAME"),
        'USER': os.getenv("DATABASE_USER"),
        'PASSWORD': os.getenv("DATABASE_PASSWORD"),
        'HOST': os.getenv("DATABASE_HOST"),
        'PORT': os.getenv("DATABASE_PORT"),
    }
}
```

Ni un solo valor de la base escrito en el código. Todos salen del entorno, y en un despliegue con contenedores esas variables las inyecta el propio orquestador.

**Y hay algo en ese código que conviene mirar con ojo crítico**, porque es un error muy repetido:

```python
SECRET_KEY = os.getenv("SECRET_KEY", "defaultsecretkey")
```

El segundo argumento de `os.getenv` es el **valor por defecto**: lo que devuelve si la variable no existe. Puesto así, si un día la variable falta en el servidor, el proyecto **no falla**: arranca tan contento firmando las sesiones con una clave que está escrita en el repositorio y que cualquiera puede leer. Un fallo ruidoso sería mejor que ese arranque silencioso.

Lo correcto para un valor sin el cual el sistema no debería arrancar es no dar alternativa:

```python
SECRET_KEY = os.environ["SECRET_KEY"]      # si no esta, revienta al arrancar, y eso esta bien
```

La regla general: **valor por defecto solo cuando ese valor por defecto es seguro** (un puerto, un idioma, un tamaño de página). Nunca para una clave, una contraseña o `DEBUG`.

### 6.5 Lo que nunca va a git

| Archivo | ¿A git? | Por qué |
|---|---|---|
| `settings.py` | sí | es código, igual en todas las máquinas |
| `.env` | **no** | valores de esta máquina, y secretos |
| `.env.example` | sí | la plantilla con las claves y sin los valores |
| `db.sqlite3` | no | son datos, no código |
| `.venv/` | no | se reconstruye con `pip install -r` |
| `__pycache__/` | no | lo genera Python solo |

El `.env.example` merece una nota: es lo que le dice al siguiente **qué variables necesita** el proyecto. Sin él, quien clona el repositorio descubre las variables que faltan de una en una, a base de errores.

### 6.6 Por qué el curso no lo usa

La base del curso deja `SECRET_KEY` y `DEBUG` escritos en el código, como los deja `startproject`. Es a propósito: son dos horas y el objetivo es la API, no el despliegue. Pero **ahora ya sabes que eso no se queda así**, y en el extra de la tarea puedes moverlo al entorno en diez minutos.

---

## 7. El viaje de una petición

Cinco piezas, los mismos cinco pasos que ya conoces con otros nombres de archivo.

```
navegador
   |
   v
config/urls.py          <- que ruta coincide            (routes/api.php)
   |
   v
avisos/views.py         <- el ViewSet decide            (Controller)
   |
   v
avisos/models.py        <- consulta la base con el ORM  (Eloquent)
   |
   v
avisos/serializers.py   <- arma el JSON                 (Resource)
   |
   v
HTTP 200 con el JSON
```

De ida, las rutas eligen quién atiende; la vista pide datos al modelo; el serializer los convierte a JSON. De vuelta, lo mismo al revés: el serializer valida lo que llega, la vista decide, el modelo guarda.

La única frase que hay que memorizar de todo el modelo MTV: **en Django, la "vista" es el controlador**. No es la plantilla. La plantilla se llama "template". El nombre es desafortunado y lleva confundiendo a la gente desde 2005.

---

## 8. Modelos y migraciones

En Laravel escribes dos archivos: la migración (la forma de la tabla) y el modelo (cómo se usa). En Django escribes **solo el modelo**, y la migración la genera Django leyéndolo.

```python
class Aviso(models.Model):
    titulo = models.CharField(max_length=120)
    contenido = models.TextField()
    categoria = models.ForeignKey(
        Categoria,
        on_delete=models.CASCADE,
        related_name="avisos",
    )
    creado = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ["-creado"]

    def __str__(self):
        return self.titulo
```

El ciclo completo:

```bash
.venv/bin/python manage.py makemigrations    # lee los modelos y ESCRIBE la migracion
.venv/bin/python manage.py sqlmigrate avisos 0001   # te enseña el SQL, sin ejecutarlo
.venv/bin/python manage.py migrate           # lo aplica
```

`sqlmigrate` merece un minuto: imprime el SQL exacto que va a correr, sin correrlo. Ahí se ve que Django agrega solo un `CREATE INDEX` sobre la llave foránea, algo que en Laravel tendrías que recordar escribir.

Dos avisos que ahorran problemas:

- **`on_delete` es obligatorio** en una `ForeignKey`. Django no elige por ti qué pasa con los avisos cuando se borra su categoría. `CASCADE` los borra, `PROTECT` impide borrar la categoría, `SET_NULL` deja el campo vacío. Es una decisión que Laravel te deja olvidar y Django no.
- **No pongas `null=True` en campos de texto.** Tendrías dos maneras de decir "vacío", la cadena `""` y `None`, y consultas que fallan la mitad de las veces. La convención de Django es que los textos vacíos son `""`.

---

## 9. El serializer

Hace **dos trabajos que en Laravel eran dos archivos**: valida lo que entra (tu `FormRequest`) y decide qué sale (tu `Resource`).

```python
class AvisoSerializer(serializers.ModelSerializer):
    categoria = CategoriaSerializer(read_only=True)
    categoria_id = serializers.PrimaryKeyRelatedField(
        queryset=Categoria.objects.all(), source="categoria", write_only=True
    )

    class Meta:
        model = Aviso
        fields = ["id", "titulo", "contenido", "categoria", "categoria_id", "creado"]
```

Lo que más confunde al principio es la **asimetría**: entra `categoria_id` como número y sale `categoria` como objeto completo. Eso es deliberado, y es lo que hacen las dos líneas de arriba con `write_only` y `read_only`.

Y un detalle que sorprende a quien viene de Laravel: **el modelo no valida al guardar**. `Aviso.objects.create(titulo="x" * 500)` no protesta, aunque el campo diga `max_length=120`. La validación vive en el serializer, no en el modelo. Si escribes directo contra el ORM, te la saltas.

---

## 10. Vistas, ViewSets y el router

```python
class AvisoViewSet(viewsets.ModelViewSet):
    """Avisos del blog."""
    queryset = Aviso.objects.select_related("categoria", "autor")
    serializer_class = AvisoSerializer
    permission_classes = [IsAuthenticatedOrReadOnly, EsAutorOAdmin]
```

Siete líneas, y tienes listar, ver, crear, editar y borrar. El router las convierte en rutas:

```python
router = DefaultRouter()
router.register("avisos", AvisoViewSet)
```

**El precio honesto**: es mucho menos explícito que un controlador de Laravel. No ves los métodos, así que no sabes qué hace sin conocer `ModelViewSet`. La propia documentación de DRF lo admite y ofrece las vistas de clase normales como alternativa cuando la claridad importa más que la brevedad.

**La barra final.** El router genera rutas **con barra al final**: `/api/avisos/`. Pedir `/api/avisos` sin barra responde **301** y redirige. Para un GET desde el navegador es invisible. Para un POST es un problema serio: **la redirección pierde el cuerpo**, y te llega una petición sin datos.

**La API navegable.** Abre `http://localhost:8001/api/avisos/` en el navegador y no verás JSON crudo: verás una interfaz HTML con la respuesta, sus encabezados y, si entras por `/api-auth/login/`, formularios generados desde tu serializer para probar POST y PUT. Y el texto entre comillas triples del ViewSet (el docstring) aparece impreso como descripción de la página: documentación automática sin anotar nada. Laravel no trae nada parecido.

---

## 11. Permisos

Tu `PostPolicy` existe aquí también, pero **declarada en vez de llamada**:

```python
class EsAutorOAdmin(permissions.BasePermission):
    def has_object_permission(self, request, view, obj):
        if request.method in permissions.SAFE_METHODS:
            return True
        return obj.autor == request.user or request.user.is_staff
```

```python
permission_classes = [IsAuthenticatedOrReadOnly, EsAutorOAdmin]
```

En Laravel llamas `Gate::authorize('delete', $post)` dentro del método. En DRF **declaras la lista** y el framework las ejecuta solo. La diferencia práctica: no hay una línea que puedas olvidar dentro del método, pero tampoco ves en el método qué lo protege.

`has_permission` decide si puedes entrar al endpoint; `has_object_permission` decide si puedes tocar **ese** objeto. Las dos corren, en ese orden.

---

## 12. Glosario

| Término | Qué es |
|---|---|
| **app** | un área funcional del proyecto, con sus modelos y vistas propias |
| **proyecto** | el pegamento: configuración, rutas raíz y puntos de entrada |
| **`__init__.py`** | archivo vacío que marca una carpeta como paquete importable |
| **entorno virtual** | carpeta con su propio Python y sus paquetes, aislada del sistema |
| **`venv`** | el módulo de Python que crea entornos virtuales |
| **pip** | el gestor de paquetes de Python, el equivalente de Composer |
| **`requirements.txt`** | la lista de dependencias, el `composer.json` |
| **variable de entorno** | valor que vive fuera del código, en el sistema o en un `.env` |
| **`os.getenv`** | la función que lee una variable de entorno; devuelve texto o `None` |
| **`python-dotenv`** | paquete que carga un archivo `.env` al entorno |
| **WSGI / ASGI** | los estándares por los que un servidor web habla con la aplicación |
| **middleware** | capa que atraviesa cada petición y cada respuesta |
| **ORM** | el traductor entre objetos y tablas, el Eloquent de Django |
| **migración** | el archivo que lleva la base de un estado al siguiente |
| **`makemigrations`** | lee tus modelos y escribe la migración |
| **`migrate`** | aplica las migraciones a la base |
| **serializer** | valida lo que entra y arma el JSON que sale |
| **ViewSet** | clase que resuelve el CRUD completo de un modelo |
| **router** | convierte un ViewSet en rutas |
| **decorador** | línea con `@` que envuelve una función para agregarle comportamiento |
| **`self`** | el `$this` de PHP, pero escrito como primer parámetro de cada método |
| **docstring** | texto entre comillas triples que documenta una clase o función |
