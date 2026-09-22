#!/usr/bin/env bash
# Deja lista la API de Django. Se corre una sola vez, desde la raiz del proyecto:
#   bash api-django/preparar-django.sh
set -e

cd "$(dirname "$0")"

echo ""
echo "==> 1/4  Revisando Python"
if ! command -v python3 >/dev/null 2>&1; then
    echo "    No hay python3 en este contenedor. Reconstruye el contenedor y vuelve a intentar."
    exit 1
fi
echo "    $(python3 --version)"

# La imagen del curso trae Python, pero no el modulo venv (y sin el, tampoco pip).
if ! python3 -m venv --help >/dev/null 2>&1; then
    echo "    Falta el modulo venv. Lo instalo (tarda un minuto, solo la primera vez)."
    sudo apt-get update -qq >/dev/null 2>&1
    sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq python3-venv >/dev/null 2>&1
    echo "    Instalado."
fi

echo ""
echo "==> 2/4  Entorno virtual"
if [ ! -d .venv ]; then
    python3 -m venv .venv
    echo "    Creado en api-django/.venv"
else
    echo "    Ya existia, lo reuso"
fi

echo ""
echo "==> 3/4  Django y Django REST Framework"
.venv/bin/pip install --quiet --upgrade pip
.venv/bin/pip install --quiet -r requirements.txt
echo "    Django $(.venv/bin/python -c 'import django;print(django.get_version())')"
echo "    DRF    $(.venv/bin/python -c 'import rest_framework;print(rest_framework.VERSION)')"

echo ""
echo "==> 4/4  Base de datos"
.venv/bin/python manage.py migrate --no-input >/dev/null
echo "    Migraciones aplicadas (SQLite, en api-django/db.sqlite3)"

echo ""
echo "  Listo. Para levantar la API:"
echo ""
echo "      cd api-django && .venv/bin/python manage.py runserver 0.0.0.0:8001"
echo ""
echo "  Queda en el puerto 8001, para no pelearse con Laravel en el 8000."
echo ""
