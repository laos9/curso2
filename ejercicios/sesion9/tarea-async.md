# Tarea de la semana · Sesión 9

Tiempo estimado: 2.5 horas, sin contar los extras. Se entrega en el Pull Request de siempre.

En clase escribiste en Django la misma API de avisos que ya tenías en Laravel, y viste que cinco de las seis respuestas salen idénticas. Esta tarea cierra lo que quedó a medias y te lleva al punto que de verdad enseña: **conectar tu Angular de la sesión 8 a esta API nueva**, y descubrir por qué no funciona a la primera.

La guía escrita de todo lo de clase está en [`01-django-de-cero.md`](01-django-de-cero.md) y [`02-api-con-drf.md`](02-api-con-drf.md). Y la explicación de los conceptos, con el detalle que en clase pasa rápido, está en la lectura [`00-django-por-dentro.md`](00-django-por-dentro.md): Python lo justo para leer Django, el entorno virtual, qué es cada archivo del proyecto, `settings.py` y las variables de entorno. **Si algo de la clase te quedó en el aire, empieza por ahí.**

Todos los comandos se corren desde `api-django/`, con el servidor en el puerto 8001.

---

## 1. Termina la API (35 min)

Si en clase quedaste antes del token o de los permisos, esto lo cierra. Con las guías 01 y 02 a la mano:

- [ ] El modelo `Categoria` y `Aviso` migrados, y el admin registrado.
- [ ] `AvisoSerializer` con `categoria` de salida y `categoria_id` de entrada.
- [ ] `AvisoViewSet` con su `queryset`, su `perform_create` y el router en `config/urls.py`.
- [ ] `/api/token` y la vista `yo`.
- [ ] `EsAutorOAdmin` en `permission_classes`.

Meta: los seis códigos de la tabla del deck, comprobados desde tu terminal con `curl`.

**Atajo que te va a servir toda la tarea:** en vez de `curl`, abre `http://localhost:8001/api/avisos/` en el navegador. La API navegable de DRF te deja leer, y si entras en `http://localhost:8001/api-auth/login/` también crear y borrar desde formularios. Para el punto 3 y el 4 vas a necesitar `curl` o Angular, pero para explorar es mucho más cómodo.

**Anota el token que te devolvió `/api/token`**, lo vas a necesitar en el punto 4.

---

## 2. El filtro por categoría (25 min)

Tu API de Laravel no tenía filtros. Esta sí los va a tener, y es tu primer `get_queryset`.

Haz que `GET /api/avisos/?categoria=2` devuelva solo los avisos de esa categoría, y que sin el parámetro los devuelva todos.

En `avisos/views.py`, dentro de `AvisoViewSet`:

```python
    def get_queryset(self):
        qs = super().get_queryset()
        categoria = self.request.query_params.get("categoria")
        if categoria:
            qs = qs.filter(categoria_id=categoria)
        return qs
```

- [ ] `?categoria=1` devuelve menos avisos que sin el parámetro.
- [ ] Sin el parámetro, la lista sigue completa.
- [ ] `?categoria=999` devuelve `count: 0`, no un error.

**Piensa y anota:** en Laravel, ¿dónde habrías puesto esto? Es el equivalente de un scope más un `when()` en el controlador.

---

## 3. Dos pruebas (40 min)

En la sesión 6 escribiste pruebas con Pest. Aquí el comando es `manage.py test` y la idea es la misma.

En `avisos/tests.py`:

```python
from django.contrib.auth.models import User
from rest_framework.test import APITestCase

from .models import Aviso, Categoria


class PermisosTest(APITestCase):
    def setUp(self):
        self.ana = User.objects.create_user("ana", password="secreto123")
        self.beto = User.objects.create_user("beto", password="secreto123")
        self.categoria = Categoria.objects.create(nombre="General")
        self.de_beto = Aviso.objects.create(
            titulo="Aviso de Beto", contenido="x",
            categoria=self.categoria, autor=self.beto,
        )

    def test_no_puedo_borrar_el_aviso_de_otro(self):
        self.client.force_authenticate(user=self.ana)
        respuesta = self.client.delete(f"/api/avisos/{self.de_beto.id}/")
        self.assertEqual(respuesta.status_code, 403)

    def test_el_cuerpo_vacio_responde_400(self):
        self.client.force_authenticate(user=self.ana)
        respuesta = self.client.post("/api/avisos/", {}, format="json")
        self.assertEqual(respuesta.status_code, 400)
        self.assertIn("titulo", respuesta.data)
```

Córrelas:

```bash
.venv/bin/python manage.py test avisos
```

- [ ] Las dos pasan. **Anota cuántas pruebas te reporta y en cuánto tiempo.**
- [ ] **El sabotaje:** quita `EsAutorOAdmin` de `permission_classes` y vuelve a correrlas. ¿Cuál cae y cuál no? Anótalo, va en el Pull Request.

---

## 4. Tu Angular contra esta API (45 min)

Este es el punto que enseña, y el que va a doler.

Tu aplicación de Angular de la sesión 8 habla con Laravel en el 8000. Vas a apuntarla al 8001, donde está tu Django, **sin reescribirla**: solo cambiando lo mínimo.

Levanta los dos: Django en el 8001 y Angular en el 4200.

Cambia la dirección base de tu servicio de avisos al 8001 y recarga. **No va a funcionar.** Tu trabajo es arreglar los tres tropiezos, uno por uno, y anotar cada uno:

- [ ] **La lista no carga.** Mira la respuesta en la pestaña de red: el JSON trae `results`, no `data`. Ajusta tu servicio.
- [ ] **El interceptor no autentica.** El encabezado dice `Bearer` y DRF espera `Token`. Cámbialo y comprueba que `/api/yo` responde 200.
- [ ] **El formulario no enseña los errores.** Tu componente pregunta por el 422 y aquí llega un 400. Ajústalo y comprueba que los mensajes por campo vuelven a aparecer.

Cuidado también con la **barra final**: si tu servicio pide `/api/avisos`, la redirección se come el cuerpo del POST.

Meta: la lista carga, puedes entrar, crear un aviso, y borrar el tuyo pero no el de otro.

---

## Extras (opcionales, cuentan como extra)

- **A.** Que `GET /api/avisos/` acepte `?buscar=` y filtre por texto en el título, con `titulo__icontains`.
- **B.** Registra `AvisoAdmin` con `list_display`, `list_filter` y `search_fields`, y compara el resultado con lo que hiciste en Filament en la sesión 4.
- **C.** Cambia `PAGE_SIZE` a 3 y sigue el enlace `next` hasta el final de la lista, desde la API navegable.
- **D.** Escribe un comentario (un `"""docstring"""`) dentro de `AvisoViewSet` explicando qué hace, recarga la API navegable y mira dónde apareció.
- **E. Saca la configuración del código** (unos 15 minutos, y es el extra que más se parece a un trabajo real). Tu `settings.py` trae `SECRET_KEY` escrita y `DEBUG = True`, tal como los genera Django. Móvelos al entorno:

  1. `.venv/bin/pip install python-dotenv` y agrégalo a `requirements.txt`.
  2. Arriba de `settings.py`:

     ```python
     import os
     from dotenv import load_dotenv

     load_dotenv()
     ```

  3. Cambia **las tres** líneas:

     ```python
     SECRET_KEY = os.environ["SECRET_KEY"]
     DEBUG = os.getenv("DEBUG", "False") == "True"
     ALLOWED_HOSTS = os.getenv("ALLOWED_HOSTS", "localhost,127.0.0.1").split(",")
     ```

     **`ALLOWED_HOSTS` no es opcional aquí**, y es la parte que sorprende: mientras `DEBUG` es `True`, Django ignora esa lista y acepta `localhost`. En cuanto la apagas, **exige la lista**, y si está vacía `runserver` ni siquiera arranca:

     ```
     CommandError: You must set settings.ALLOWED_HOSTS if DEBUG is False.
     ```

  4. Crea `api-django/.env` con las tres claves, y `api-django/.env.example` con las claves y sin los valores.
  5. Agrega `.env` al `.gitignore` de `api-django/`.

  **Comprueba las tres cosas que enseñan:**

  1. Borra la línea `SECRET_KEY` del `.env` y arranca: **debe reventar al arrancar**, y eso está bien. Un secreto que falta tiene que hacer ruido.
  2. Pon `DEBUG=False` **sin tocar `ALLOWED_HOSTS`** y arranca: sale el `CommandError` de arriba. Es el error que te enseña que las dos cosas van juntas.
  3. Deja `DEBUG=False` con su `ALLOWED_HOSTS`, provoca un error en la API y compara la página con la de antes: se acabó la página amarilla con tu configuración a la vista.

  En el PR, una línea: **por qué `os.getenv("SECRET_KEY", "una-clave-cualquiera")` sería peor que reventar.** La respuesta está en la sección 6 de la lectura.

---

## Cómo se entrega

Guarda, sube y abre (o actualiza) tu Pull Request de siempre:

```bash
git add -A
git commit -m "sesion 9: la API en Django"
git push origin HEAD
```

Revisa que **`api-django/.venv`, `db.sqlite3`, los `__pycache__` y, si hiciste el extra E, el `.env` no aparezcan** en los cambios: el `.gitignore` de `api-django/` los deja fuera. Si aparecen, algo se te quedó fuera de esa carpeta.

Y en la plataforma, la URL de tu Pull Request en **Entrega Sesión 9**.

En la descripción del PR van cuatro cosas:

1. Cuántas pruebas te reportó `manage.py test` y en cuánto tiempo.
2. Al quitar `EsAutorOAdmin`, **cuál de las dos pruebas cayó y cuál no**, y por qué.
3. Los **tres tropiezos** del punto 4: qué salía antes y qué cambiaste en cada uno.
4. Una diferencia entre Django y Laravel que te haya parecido mejor en Django, y una que te haya parecido mejor en Laravel. Con tus palabras.

## Checklist de la entrega

- [ ] `api-django/avisos/models.py` con `Categoria` y `Aviso`
- [ ] `api-django/avisos/serializers.py`, `views.py` y `permissions.py` completos
- [ ] `api-django/config/urls.py` con el router, `/api/token` y `/api/yo`
- [ ] `get_queryset` con el filtro por categoría
- [ ] `api-django/avisos/tests.py` con las dos pruebas, pasando
- [ ] Tu Angular apuntando al 8001 y funcionando
- [ ] La descripción del PR con los cuatro puntos
