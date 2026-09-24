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
