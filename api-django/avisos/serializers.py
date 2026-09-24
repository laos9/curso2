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

