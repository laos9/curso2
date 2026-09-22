# Guía 01 · Django de cero, con lo que ya sabes

En vivo en clase. Tiempo: unos 30 minutos.

Vas a escribir en Django el mismo modelo de avisos que ya tienes en Laravel. La gracia no es aprender un framework nuevo desde cero: es **reconocer**. Casi todo lo que hace Django tú ya lo sabes hacer, solo que con otro nombre.

Si te ayuda tenerlo en la cabeza: **un modelo es una hoja de cálculo**. Las columnas son los campos y las filas son los datos. Todo lo demás son detalles.

## El mapa, antes de empezar

| Lo que ya sabes | En Django | Dónde vive |
|---|---|---|
| `php artisan` | `manage.py` | raíz del proyecto |
| Modelo de Eloquent | Modelo de Django | `avisos/models.py` |
| Migración | Migración | `avisos/migrations/` |
| `php artisan migrate` | `manage.py migrate` | terminal |
| `php artisan tinker` | `manage.py shell` | terminal |
| `config/app.php` y `.env` | `config/settings.py` | un solo archivo |
| `routes/web.php` | `config/urls.py` | un solo archivo |
| Un paquete de Composer | Una **app** de Django | carpeta con `models.py` |

Dos diferencias de fondo, y conviene tenerlas claras desde el primer minuto:

1. **Django no separa modelo y migración.** Escribes el modelo y Django *genera* la migración leyéndolo. En Laravel son dos archivos que escribes tú y que pueden contradecirse; aquí no pueden.
2. **Django se organiza en apps.** Una app es una carpeta con su modelo, sus vistas y sus urls. Tu proyecto ya trae una, `avisos/`. En Laravel todo vive en `app/` sin esa división.

---

## Paso 1 · Mira lo que ya tienes

```bash
cd api-django
ls
```

Esto llegó con el merge: `manage.py`, la carpeta `config/` (la configuración del proyecto) y la carpeta `avisos/` (tu app, todavía vacía de contenido).

Ábrela:

```bash
ls avisos/
```

`models.py`, `views.py`, `serializers.py`, `admin.py`, `migrations/`. Todos están creados y casi vacíos. Los vas a llenar tú.

## Paso 2 · Escribe el modelo

Abre `avisos/models.py` y escribe esto:

```python
from django.contrib.auth.models import User
from django.db import models


class Categoria(models.Model):
    nombre = models.CharField(max_length=60)

    def __str__(self):
        return self.nombre


class Aviso(models.Model):
    titulo = models.CharField(max_length=120)
    contenido = models.TextField()
    categoria = models.ForeignKey(Categoria, on_delete=models.CASCADE, related_name="avisos")
    autor = models.ForeignKey(User, on_delete=models.CASCADE, related_name="avisos")
    publicado = models.BooleanField(default=True)
    creado = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ["-creado"]

    def __str__(self):
        return self.titulo
```

**Léelo contra tu Laravel**, línea por línea:

| Django | Tu Laravel |
|---|---|
| `models.CharField(max_length=120)` | la columna `string('titulo', 120)` de tu migración |
| `ForeignKey(Categoria, ...)` | `categoria_id` en la migración **más** el `belongsTo` del modelo, juntos |
| `related_name="avisos"` | el `hasMany` del otro lado |
| `on_delete=models.CASCADE` | `onDelete('cascade')` de la migración |
| `class Meta: ordering` | un `->latest()` que ya no tienes que repetir |
| `__str__` | lo que enseña el modelo cuando lo imprimes |

Fíjate en `on_delete`: Django **obliga** a decidir qué pasa con los avisos cuando borras su categoría. Laravel te deja no decidirlo. Es la misma decisión, pero aquí no se puede olvidar.

**Una trampa que vas a encontrar en proyectos ajenos:** en un campo de texto (`CharField` o `TextField`) **no se pone `null=True`**. Si lo pones, el campo pasa a tener dos formas distintas de estar vacío, `None` y `""`, y a partir de ahí cualquier comprobación se vuelve poco fiable. La convención de Django es dejar solo la cadena vacía. En números y fechas sí tiene sentido.

## Paso 3 · Genera la migración y aplícala

```bash
.venv/bin/python manage.py makemigrations avisos
```

**Qué debe salir:**

```
Migrations for 'avisos':
  avisos/migrations/0001_initial.py
    - Create model Categoria
    - Create model Aviso
```

Ábrela y mírala: **tú no la escribiste**, Django la dedujo del modelo. Ese es el cambio de hábito más grande respecto a Laravel.

Antes de aplicarla, pídele a Django que te enseñe el SQL **sin ejecutarlo**:

```bash
.venv/bin/python manage.py sqlmigrate avisos 0001
```

**Qué debe salir** (recortado):

```sql
CREATE TABLE "avisos_aviso" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "titulo" varchar(120) NOT NULL,
  "contenido" text NOT NULL,
  "publicado" bool NOT NULL,
  "creado" datetime NOT NULL,
  "autor_id" integer NOT NULL REFERENCES "auth_user" ("id") DEFERRABLE INITIALLY DEFERRED,
  "categoria_id" bigint NOT NULL REFERENCES "avisos_categoria" ("id") DEFERRABLE INITIALLY DEFERRED);
CREATE INDEX "avisos_aviso_categoria_id_e474bdfe" ON "avisos_aviso" ("categoria_id");
```

Míralo con calma, porque **ese SQL lo dedujo Django de tu modelo**. Fíjate sobre todo en la última línea: te puso un **índice en la llave foránea** sin que se lo pidieras. Es la clase de detalle que en una migración escrita a mano se olvida.

Ahora sí, aplícala:

```bash
.venv/bin/python manage.py migrate
```

## Paso 4 · El admin, que Laravel no trae de fábrica

Abre `avisos/admin.py`:

```python
from django.contrib import admin

from .models import Aviso, Categoria

admin.site.register(Categoria)
admin.site.register(Aviso)
```

Crea un usuario administrador:

```bash
.venv/bin/python manage.py createsuperuser
```

Te pide **usuario**, correo y contraseña. Usa **los mismos usuarios de práctica que ya tienes en Laravel**, para no andar recordando otros distintos:

| | Usuario | Contraseña |
|---|---|---|
| Superusuario | `admin@blog.test` | `secreto123` |

Sí, el usuario lleva el correo completo. **Django autentica por `username`, no por correo**, así que poniendo el correo como nombre de usuario acabas escribiendo el mismo dato que en tu Laravel. El campo de correo puedes repetirlo o dejarlo vacío.

**Ahora crea el segundo usuario, y este sin privilegios:**

```bash
.venv/bin/python manage.py shell -c "from django.contrib.auth.models import User; u = User.objects.create_user('editor@blog.test', password='secreto123'); print('creado', u.username, '| is_staff =', u.is_staff)"
```

Debe imprimir `creado editor@blog.test | is_staff = False`.

Son los mismos dos papeles de tu seeder de Laravel:

| Comando | Qué crea | `is_staff` | Su equivalente allá |
|---|---|---|---|
| `createsuperuser` | entra al panel y manda sobre todo | `True` | `admin@blog.test`, rol `admin` |
| `User.objects.create_user(...)` | un usuario normal | `False` | `editor@blog.test`, rol `editor` |

**Esa diferencia la vas a necesitar en la guía 02**: la Policy deja pasar a quien tenga `is_staff`, así que con un solo superusuario nunca verías un 403. Hacen falta los dos.

Levanta el servidor y entra a `/admin/`:

```bash
.venv/bin/python manage.py runserver 0.0.0.0:8001
```

**Lo que acabas de conseguir con tres líneas** es un panel de administración completo: listar, crear, editar y borrar avisos y categorías, con sus relaciones resueltas. En Laravel eso es Filament, que instalaste en la sesión 4. En Django viene incluido.

Crea desde el panel una categoría y dos avisos, **los dos con `autor` = `admin@blog.test`**. Los vas a necesitar en la guía 02: que sean del admin es lo que después te deja ver el 403 desde la cuenta del editor.

## Paso 5 · El shell, que es tu Tinker

En otra terminal:

```bash
.venv/bin/python manage.py shell
```

Y dentro:

```python
from avisos.models import Aviso, Categoria
Aviso.objects.all()
Aviso.objects.count()
Aviso.objects.filter(publicado=True)
Aviso.objects.first().categoria.nombre
```

| Django | Tu Eloquent |
|---|---|
| `Aviso.objects.all()` | `Post::all()` |
| `Aviso.objects.filter(publicado=True)` | `Post::where('publicado', true)->get()` |
| `Aviso.objects.count()` | `Post::count()` |
| `.first()` | `->first()` |

El `objects` de en medio es la diferencia visible: en Eloquent los métodos cuelgan del modelo, en Django cuelgan de un **manager** que se llama `objects`. Es el mismo patrón con una capa explícita.

Prueba también el N+1, que ya viste en la sesión 2:

```python
for a in Aviso.objects.all():
    print(a.categoria.nombre)
```

Eso son 1 + N consultas, igual que en Eloquent. Y se arregla igual de fácil:

```python
for a in Aviso.objects.select_related("categoria"):
    print(a.categoria.nombre)
```

`select_related` es el `with()` de Eloquent.

Con un matiz que no tiene equivalente en Laravel: Django parte ese `with()` en **dos métodos**, según de qué lado esté la relación.

| Usa | Cuando lo relacionado es | Qué hace | En Eloquent |
|---|---|---|---|
| `select_related("categoria")` | **uno** (un aviso tiene una categoría) | un `JOIN`, una sola consulta | `with('categoria')` |
| `prefetch_related("etiquetas")` | **muchos** (un aviso tiene varias etiquetas) | una segunda consulta y las une en Python | `with('etiquetas')` |

Elegir el que no es **no da error**, solo lentitud, así que es de los fallos que llegan a producción.

Para salir, `exit()`.

---

## Lo que te llevas de esta guía

- El modelo de Django es modelo y migración a la vez, y la migración la escribe él.
- Una app de Django es una carpeta con su propio modelo, vistas y urls.
- `manage.py` es `artisan`; `manage.py shell` es Tinker; `objects` es el manager.
- El panel de administración sale gratis con `admin.site.register`.
- `select_related` es `with()`: el N+1 existe igual en los dos mundos.

Sigue con la guía 02, donde esto se convierte en una API con DRF.
