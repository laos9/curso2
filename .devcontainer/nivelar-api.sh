#!/usr/bin/env bash
# Nivelacion de la API para la sesion 6 (Testing).
#
# Las pruebas de hoy se escriben sobre la API de la sesion 5. Si no la
# terminaste, este script te la deja puesta con la version oficial del curso:
# routes/api.php, el PostResource y los dos controladores.
#
# Es IDEMPOTENTE y CONSERVADOR: nunca sobreescribe un archivo que ya exista.
# Si tu API ya funciona, no toca nada y te lo dice.
#
#   bash .devcontainer/nivelar-api.sh
set -e

if [ ! -f artisan ]; then
    echo "Aqui no hay proyecto Laravel (no encuentro 'artisan')."
    exit 1
fi

N=.devcontainer/nivelacion

copia() {   # copia ORIGEN DESTINO solo si el destino no existe
    if [ -f "$2" ]; then
        echo "  [ya existe] $2"
    else
        mkdir -p "$(dirname "$2")"
        cp "$1" "$2"
        echo "  [creado]    $2"
    fi
}

echo ""
echo "=== 1/4 Sanctum y routes/api.php ==="
if [ -f routes/api.php ]; then
    echo "  [ya existe] routes/api.php"
else
    echo "  Corriendo php artisan install:api ..."
    php artisan install:api --no-interaction
fi

echo ""
echo "=== 2/4 El trait en el modelo User ==="
if grep -q "HasApiTokens" app/Models/User.php; then
    echo "  [ya existe] HasApiTokens en app/Models/User.php"
else
    echo "  AVISO: a tu app/Models/User.php le falta el trait HasApiTokens."
    echo "         Sin el, createToken() no existe y /api/token truena."
    echo "         Agrega estas dos lineas (es lo que hiciste en la sesion 5):"
    echo "             use Laravel\Sanctum\HasApiTokens;      // arriba, con los use"
    echo "             use HasApiTokens, HasFactory, Notifiable;  // dentro de la clase"
fi

echo ""
echo "=== 3/4 La API de la sesion 5 ==="
copia "$N/PostResource.php"       app/Http/Resources/PostResource.php
copia "$N/Api-PostController.php"  app/Http/Controllers/Api/PostController.php
copia "$N/Api-TokenController.php" app/Http/Controllers/Api/TokenController.php
if grep -qs "PostController" routes/api.php; then
    echo "  [ya existe] tus rutas en routes/api.php"
else
    cp "$N/rutas-api.php" routes/api.php
    echo "  [creado]    routes/api.php con las rutas del curso"
fi

echo ""
echo "=== 4/4 Comprobacion ==="
php artisan route:list --path=api

echo ""
echo "Si ves las ocho rutas de arriba, tu API esta lista para que le escribas pruebas."
