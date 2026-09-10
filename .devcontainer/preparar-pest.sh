#!/usr/bin/env bash
# Deja Pest listo en tu proyecto para la sesion 6.
#
# Pest es el marco de pruebas que se usa hoy en Laravel y el que corre en el
# sistema real de la dependencia. Por dentro sigue siendo PHPUnit: Pest es la
# forma de escribirlo.
#
# Es IDEMPOTENTE: si ya lo tienes, no hace nada.
#
#   bash .devcontainer/preparar-pest.sh
set -e

if [ ! -f artisan ]; then
    echo "Aqui no hay proyecto Laravel (no encuentro 'artisan')."
    echo "Primero: bash .devcontainer/crear-proyecto.sh"
    exit 1
fi

echo ""
echo "=== 1/2 El paquete ==="
if [ -f vendor/bin/pest ]; then
    echo "  [ya existe] $(./vendor/bin/pest --version 2>/dev/null | tr -d '\n' | sed 's/\x1b\[[0-9;]*m//g')"
else
    echo "  Instalando (tarda un poco, esta bajando el paquete)..."
    # Las versiones van fijas a proposito: son las que corren en el sistema
    # real de la dependencia (Pest 3 sobre PHPUnit 11). Pest 5 ya existe, pero
    # pide PHP 8.4 y cambia cosas; sin fijar, cada quien acabaria con una
    # version distinta segun el PHP de su contenedor.
    composer require "pestphp/pest:^3.8" "pestphp/pest-plugin-laravel:^3.2" --dev --with-all-dependencies --no-interaction
fi

echo ""
echo "=== 2/2 El archivo de configuracion ==="
if [ -f tests/Pest.php ]; then
    echo "  [ya existe] tests/Pest.php"
else
    ./vendor/bin/pest --init >/dev/null 2>&1
    echo "  [creado]    tests/Pest.php"
fi

echo ""
echo "Listo. Comprueba con:"
echo "    php artisan test"
echo ""
echo "Nota: en tests/Pest.php la linea de RefreshDatabase viene COMENTADA a"
echo "proposito. Cada archivo de prueba declara si necesita base de datos."
