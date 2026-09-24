# Tarea de la sesión 10

Unas 2 horas. La fecha límite está en la plataforma del curso.

Es la última entrega del curso. Lo que se pide es que tu proyecto arranque completo en una computadora que nunca lo ha visto, y que un cambio tuyo pueda llegar hasta una dirección que otra persona pueda abrir.

La guía **`04-despliegue-real.md`** lleva todo esto paso a paso, con qué hacer en cada punto donde suele atorarse. Esta página dice qué se entrega y cómo se evalúa.

---

## 1. Tu proyecto levanta desde cero

**Qué se entrega:** en tu repositorio, `docker/angular.Dockerfile`, `docker/laravel.Dockerfile`, `docker/django.Dockerfile`, tu `compose.yaml` listo para el servidor y tu `compose.override.yaml` con los puertos de tu máquina, junto con `docker/variables.env.example`.

**La prueba:** clona tu propio repositorio en una carpeta nueva, arma su `.env` desde los dos ejemplos que sí viajaron, y levanta:

```
git clone <tu repositorio> prueba-limpia
cd prueba-limpia
cp .env.example .env
# pega al final de .env el contenido de docker/variables.env.example, con
# otro COMPOSE_PROJECT_NAME, otros puertos, claves nuevas y tu APP_KEY
docker compose up -d --build
```

Los detalles, y cómo sacar la `APP_KEY` sin PHP en tu computadora, están en la parte 6 de la guía 04.

Las migraciones corren solas al arrancar, porque así quedó tu `compose.yaml` en la guía 04.

Si aquí falta un archivo, es que no lo subiste. Esa es toda la gracia de la prueba.

**Evidencia:** una captura de `docker compose ps` con los cuatro servicios arriba y la base en `healthy`, y una de tu Angular abierto desde esa copia.

## 2. Tu repositorio en GitLab, y el pipeline

**Qué se entrega:** la dirección de tu proyecto en GitLab y tu `.gitlab-ci.yml`.

**Evidencia:** tres capturas del pipeline.

1. **En verde**, con tus trabajos de prueba.
2. **En rojo**, y que se vea **la línea del error dentro del log**. No la pantalla de la lista: el log abierto.
3. **En verde otra vez**, después de arreglarlo.

Un pipeline que nunca se ha puesto en rojo no ha demostrado nada. Rómpelo a propósito: un tipo mal en TypeScript sirve.

## 3. Tu dirección, o el camino equivalente

**Qué se entrega:** la dirección donde corre tu proyecto, abierta y con HTTPS.

**Evidencia:** tres capturas.

1. El log del trabajo `desplegar` en verde, con la línea que dice que tu dirección responde.
2. Tu Angular en esa dirección, con la barra del navegador visible.
3. La misma dirección **después de un cambio que empujaste tú**, donde se note el cambio. Es lo que demuestra que no desplegaste una vez: montaste una cadena.

**Si no hay servidor disponible cuando entregas**, se entrega el camino equivalente que está en la parte 6 de la guía 04: la copia limpia levantada con puertos distintos y claves nuevas. **Vale igual.** Dilo en tu entrega y ya.

El acceso a una infraestructura nunca es parte de la calificación.

## 4. Media cuartilla

Responde estas dos, con tu proyecto en la cabeza y no en general:

> **Si tu servidor se apaga ahora mismo y no vuelve, ¿qué se pierde exactamente?**
>
> **¿Qué tendría que existir para que no se perdiera?**

Sé concreto. No vale "se perderían los datos": di cuáles, dónde están, y qué haría falta. Si tu respuesta incluye respaldos, di también cómo comprobarías que sirven.

Es el punto que menos se escribe y el que más se piensa. Vale lo mismo que los otros tres.

---

## Cómo se evalúa

Por **evidencia**, no por tiempo ni por cantidad. Una captura donde se vea el error y otra donde se vea resuelto valen más que un texto explicando que funcionó.

| Punto | Qué se busca |
|---|---|
| 1 | Que el repositorio, solo, baste para levantar el sistema |
| 2 | Que el pipeline detenga de verdad un cambio roto, y que sepas leer el log |
| 3 | Que la cadena completa funcione, o que entiendas cada eslabón |
| 4 | Que distingas lo que se puede recuperar de lo que no |

Reintentar nunca resta y cuenta tu mejor versión. Si algo no salió, entrega lo que llegaste a hacer con la captura del punto donde se atoró: eso se puede revisar y corregir, una entrega vacía no.

---

## Extras, sin peso en la calificación

Para quien quiera seguir. Están en la lectura `00-docker-y-gitlab-por-dentro.md`, sección 30, y el D tiene su propia guía, la `05-registro-de-imagenes.md`.

**A. Caché en el pipeline.** Agrega `cache` con la clave atada a `package-lock.json` y mide cuánto baja el tiempo del trabajo de Angular.

**B. Artifacts.** Haz que el trabajo de Angular guarde su `dist/` como artifact y descárgalo desde la web de GitLab.

**C. Reportes de prueba.** Haz que las pruebas de Django dejen un reporte JUnit y que GitLab te lo muestre en la pantalla de la Merge Request, sin abrir el log.

**D. Registro de imágenes.** Sigue la guía 05: tu pipeline construye una sola vez una base con PHP, sus extensiones y Composer, la guarda en el registro de tu proyecto, y la prueba de Laravel y el servidor la bajan en vez de compilar. En el ensayo del curso, esa prueba bajó de 112 a 26 segundos. La guía dice qué capturas agregar. Si quieres ir todavía más lejos, el paso siguiente es lo que hace un proyecto grande: subir la imagen **completa** etiquetada con `$CI_COMMIT_SHA` y que el servidor descargue esa etiqueta exacta en vez de construir.

**E. Solo lo que cambió.** Usa `rules: changes:` para que el trabajo de Angular solo corra cuando tocaste algo de `frontend/`.

---

## Si te trabas

Al canal del curso, con **la captura del error completo**, no solo la última línea. Casi siempre la razón está tres renglones más arriba.

Y antes de preguntar, dos cosas que resuelven la mitad de los casos:

- `docker compose logs <servicio>`. Un contenedor que se apaga solo casi siempre ya dijo por qué.
- `docker compose config`. Te muestra el archivo con las variables ya puestas, y así ves qué entendió de verdad.
