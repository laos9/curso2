# Guía 02 · La misma API, ahora con Django REST Framework

En vivo en clase. Tiempo: unos 35 minutos.

En la sesión 5 construiste una API en Laravel: rutas, controlador, Resource, validación, Sanctum y Policy. Ahora la vas a construir otra vez, con las piezas de DRF. Al terminar vas a tener **los mismos seis códigos de respuesta**, y vas a saber qué pieza decide cada uno.

## El mapa

| Tu Laravel de la sesión 5 | En DRF |
|---|---|
| `PostResource` (qué campos salen) | **Serializer** |
| `$request->validate([...])` (qué entra) | **el mismo Serializer** |
| `PostController` con sus 5 métodos | **ViewSet** |
| las 5 rutas de `routes/api.php` | **Router** |
| Sanctum y `auth:sanctum` | **TokenAuthentication** |
| `Gate::authorize` y `PostPolicy` | **Permission class** |
| `->paginate(10)` | `PAGE_SIZE` en `settings.py` |

La diferencia más grande: en Laravel el Resource y la validación son dos archivos distintos. En DRF **son el mismo objeto**, el serializer, que traduce en los dos sentidos. Entra JSON y lo valida; sale modelo y lo convierte a JSON.

---

## Paso 1 · El serializer

Abre `avisos/serializers.py`:

```python
from rest_framework import serializers

from .models import Aviso, Categoria


class CategoriaSerializer(serializers.ModelSerializer):
    class Meta:
        model = Categoria
        fields = ["id", "nombre"]


class AvisoSerializer(serializers.ModelSerializer):
    categoria = CategoriaSerializer(read_only=True)
    categoria_id = serializers.PrimaryKeyRelatedField(
        queryset=Categoria.objects.all(), source="categoria", write_only=True
    )
    autor = serializers.StringRelatedField(read_only=True)

    class Meta:
        model = Aviso
        fields = ["id", "titulo", "contenido", "categoria", "categoria_id", "autor", "creado"]
```

Tres cosas que mirar:

- **`fields`** es la lista blanca. Lo que no esté ahí no sale nunca. Es exactamente lo que hacías a mano en tu `PostResource` para no publicar la tabla entera.
- **`categoria` sale como objeto, `categoria_id` entra como número.** Uno es `read_only`, el otro `write_only`. En Laravel eso eran dos archivos: el Resource decidía la salida y el `validate` la entrada.
- **La validación no la escribiste.** `max_length=120` ya está en el modelo, y el serializer lo hereda. Ese es el cambio de hábito: en Laravel las reglas viven en el controlador, aquí viven en el modelo y se propagan solas.

Ojo con una sutileza que confunde a mucha gente: **el modelo por sí solo no valida al guardar**. Si abres el shell y haces `Aviso.objects.create(titulo="x"*500)`, Django no se queja (la base sí, según el motor). Las reglas del modelo se revisan cuando alguien las **pide**, y en una API quien las pide es el serializer. Por eso la validación de verdad vive ahí, y por eso escribir directo con el ORM se salta las reglas. Es lo mismo que en Laravel pasa si guardas con `Post::create()` sin haber llamado a `validate()`.

## Paso 2 · El ViewSet

Abre `avisos/views.py`:

```python
from rest_framework import permissions, viewsets

from .models import Aviso
from .serializers import AvisoSerializer


class AvisoViewSet(viewsets.ModelViewSet):
    queryset = Aviso.objects.filter(publicado=True).select_related("categoria", "autor")
    serializer_class = AvisoSerializer

    def perform_create(self, serializer):
        serializer.save(autor=self.request.user)
```

Eso es todo. **Esas siete líneas son los cinco métodos de tu `PostController`**: listar, ver uno, crear, actualizar y borrar.

Y el precio, que la documentación de DRF admite sin rodeos: **un ViewSet es menos explícito**. Con tu controlador de Laravel abres el archivo y ves los cinco métodos con sus nombres; aquí hay que saber qué trae `ModelViewSet` por debajo. Escribes menos código, pero tienes que conocer más.

- `queryset` es el `Post::publicados()->with(...)` de tu `index()`, escrito una sola vez para los cinco métodos.
- `perform_create` es el `$datos['user_id'] = $request->user()->id` que ponías antes de guardar.
- Lo que no ves es lo que `ModelViewSet` ya trae hecho.

## Paso 3 · El router

Abre `config/urls.py`:

```python
from django.contrib import admin
from django.urls import include, path
from rest_framework.routers import DefaultRouter

from avisos.views import AvisoViewSet

router = DefaultRouter()
router.register(r"avisos", AvisoViewSet)

urlpatterns = [
    path("admin/", admin.site.urls),
    path("api/", include(router.urls)),
    path("api-auth/", include("rest_framework.urls")),
]
```

La última línea ya venía en tu proyecto: es la que te deja entrar y salir de la API navegable, que vas a usar en un momento. No la borres.

`router.register` genera las cinco rutas de golpe. Es el `Route::apiResource` de Laravel, con una diferencia que te va a morder: **las rutas llevan barra final**. Es `/api/avisos/`, no `/api/avisos`.

Pruébalo:

```bash
curl -s http://localhost:8001/api/avisos/
```

Sale un JSON con `count`, `next`, `previous` y `results`. Compáralo con el de tu Laravel, que traía `data`, `links` y `meta`: **los dos paginan de diez, con otros nombres**. El número lo pusiste en `settings.py`, en `PAGE_SIZE`.

### Ahora ábrela en el navegador

Esa misma dirección, `http://localhost:8001/api/avisos/`, pero en el navegador en vez de `curl`.

No sale JSON: sale una **interfaz completa**, con la respuesta, sus encabezados y botones. Se llama **API navegable** y viene encendida de fábrica en DRF. Tres cosas para mirar:

- **La descripción bajo el título** es el comentario que escribiste dentro del ViewSet. Tu comentario se convirtió en documentación, sin anotar nada.
- **Los encabezados** de la respuesta, incluido `Allow`, que dice qué métodos acepta esa dirección.
- **Los enlaces** de la paginación, que puedes seguir con el ratón.

Entra con tu usuario en `http://localhost:8001/api-auth/login/` (el que creaste con `createsuperuser`) y vuelve a la lista: **ahora aparece un formulario** con los campos `titulo`, `contenido` y `categoria_id`, y un botón POST. Ese formulario **lo armó DRF leyendo tu serializer**: si agregas un campo, aparece solo.

De aquí en adelante puedes probar la API desde el navegador en vez de escribir `curl`. Laravel no trae nada parecido: ahí la documentación se genera aparte.

## Paso 4 · El token

Hasta aquí cualquiera puede escribir. Vamos a pedir token, como con Sanctum.

En `config/urls.py`, agrega el import y la ruta:

```python
from rest_framework.authtoken.views import obtain_auth_token
```

```python
    path("api/token", obtain_auth_token),
```

Y en `avisos/views.py`, el equivalente de tu `/api/yo`:

```python
from rest_framework.decorators import api_view, permission_classes
from rest_framework.response import Response
```

```python
@api_view(["GET"])
@permission_classes([permissions.IsAuthenticated])
def yo(request):
    return Response({
        "id": request.user.id,
        "nombre": request.user.username,
        "rol": "admin" if request.user.is_staff else "autor",
    })
```

Registra `yo` en `config/urls.py` igual que la anterior.

Ahora protege el ViewSet. En `avisos/views.py`, dentro de la clase:

```python
    permission_classes = [permissions.IsAuthenticatedOrReadOnly]
```

`IsAuthenticatedOrReadOnly` dice: leer lo puede cualquiera, escribir solo con token. Es tu `Route::middleware('auth:sanctum')->group(...)` en una línea.

Pide un token:

```bash
curl -s -X POST http://localhost:8001/api/token \
  -H 'Content-Type: application/json' \
  -d '{"username":"admin","password":"admin12345"}'
```

Sale `{"token":"..."}`. Guárdalo:

```bash
TOKEN=pega_aqui_tu_token
curl -s http://localhost:8001/api/yo -H "Authorization: Token $TOKEN"
```

**Ojo con la palabra.** En Sanctum era `Authorization: Bearer`. En DRF es `Authorization: Token`. Si escribes `Bearer` te responde 401 y el mensaje no lo dice claro. El sistema real usa esta misma forma.

## Paso 5 · Los permisos, que son tu Policy

Con lo de arriba, cualquiera con token puede borrar el aviso de cualquiera. Falta la Policy.

Crea `avisos/permissions.py`:

```python
from rest_framework import permissions


class EsAutorOAdmin(permissions.BasePermission):
    def has_object_permission(self, request, view, obj):
        if request.method in permissions.SAFE_METHODS:
            return True
        if request.user.is_staff:
            return True
        return obj.autor_id == request.user.id
```

Y añádela en el ViewSet:

```python
    permission_classes = [permissions.IsAuthenticatedOrReadOnly, EsAutorOAdmin]
```

Compáralo con tu `PostPolicy`:

| Tu Policy | Aquí |
|---|---|
| `before()` que deja pasar al admin | `if request.user.is_staff: return True` |
| `delete(User $user, Post $post)` | `has_object_permission(self, request, view, obj)` |
| `$post->user_id === $user->id` | `obj.autor_id == request.user.id` |
| `Gate::authorize` en el controlador | la lista `permission_classes` |

La diferencia: en Laravel **tú llamas** a `Gate::authorize` en cada método. Aquí no llamas nada, declaras la clase y DRF la consulta solo. Se te puede olvidar en Laravel; aquí no.

## Paso 6 · Los seis códigos, uno por uno

Con el servidor corriendo, prueba cada caso y **anota qué respondió**:

```bash
# 1. leer sin token
curl -i -s http://localhost:8001/api/avisos/ | head -1

# 2. crear sin token
curl -i -s -X POST http://localhost:8001/api/avisos/ \
  -H 'Content-Type: application/json' \
  -d '{"titulo":"x","contenido":"y","categoria_id":1}' | head -1

# 3. crear con token
curl -i -s -X POST http://localhost:8001/api/avisos/ \
  -H "Authorization: Token $TOKEN" -H 'Content-Type: application/json' \
  -d '{"titulo":"Mi primer aviso en Django","contenido":"hola","categoria_id":1}' | head -1

# 4. crear con el cuerpo vacio
curl -s -X POST http://localhost:8001/api/avisos/ \
  -H "Authorization: Token $TOKEN" -H 'Content-Type: application/json' -d '{}'

# 5. borrar un aviso ajeno
curl -i -s -X DELETE http://localhost:8001/api/avisos/2/ \
  -H "Authorization: Token $TOKEN" | head -1

# 6. borrar el tuyo
curl -i -s -X DELETE http://localhost:8001/api/avisos/1/ \
  -H "Authorization: Token $TOKEN" | head -1
```

La tabla que vas a llenar, y la sorpresa está en la fila 4:

| Caso | Tu Laravel | Django + DRF | Quién lo decide |
|---|---|---|---|
| Leer sin token | 200 | 200 | `IsAuthenticatedOrReadOnly` |
| Crear sin token | 401 | 401 | la misma |
| Crear con token | 201 | 201 | el ViewSet |
| Cuerpo vacío | **422** | **400** | el serializer |
| Borrar ajeno | 403 | 403 | tu permission class |
| Borrar el tuyo | 204 | 204 | el ViewSet |

**Cinco de seis son idénticos.** El que cambia es la validación: Laravel responde 422 y DRF responde 400. Los dos devuelven los errores por campo, pero el código es distinto. Si un frontend espera 422 y le llega 400, el formulario no enseña los errores. Eso, en un sistema con Angular delante, es un bug de verdad.

---

## Lo que te llevas de esta guía

- El serializer hace de Resource y de Form Request a la vez.
- Un `ModelViewSet` es tu controlador entero, y el router son tus cinco rutas.
- El token va como `Authorization: Token`, no `Bearer`.
- Las rutas del router llevan **barra final**.
- La permission class es tu Policy, pero DRF la consulta sola.
- La validación responde **400**, no 422.
