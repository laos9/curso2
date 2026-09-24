from django.db import models

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
# Aqui escribes Categoria y Aviso (guia 01, paso 2).
