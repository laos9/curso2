from rest_framework import permissions, viewsets
from rest_framework.decorators import api_view, permission_classes
from rest_framework.response import Response

from .models import Aviso, Categoria
from .permissions import EsAutorOAdmin
from .serializers import AvisoSerializer, CategoriaSerializer


class AvisoViewSet(viewsets.ModelViewSet):
    queryset = Aviso.objects.filter(publicado=True).select_related("categoria", "autor")
    serializer_class = AvisoSerializer
    permission_classes = [permissions.IsAuthenticatedOrReadOnly, EsAutorOAdmin]

    def get_queryset(self):
        qs = super().get_queryset()
        categoria = self.request.query_params.get("categoria")
        if categoria:
            qs = qs.filter(categoria_id=categoria)
        return qs

    def perform_create(self, serializer):
        serializer.save(autor=self.request.user)


class CategoriaViewSet(viewsets.ReadOnlyModelViewSet):
    queryset = Categoria.objects.all()
    serializer_class = CategoriaSerializer
    pagination_class = None


@api_view(["GET"])
@permission_classes([permissions.IsAuthenticated])
def yo(request):
    return Response({
        "id": request.user.id,
        "nombre": request.user.username,
        "rol": "admin" if request.user.is_staff else "autor",
    })
