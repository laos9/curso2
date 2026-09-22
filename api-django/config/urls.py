from django.contrib import admin
from django.urls import include, path

# En la guia 02 agregas aqui el router de DRF, /api/token y /api/yo.
urlpatterns = [
    path("admin/", admin.site.urls),
    # Entrar y salir de la API navegable de DRF, la que se ve en el navegador.
    path("api-auth/", include("rest_framework.urls")),
]
