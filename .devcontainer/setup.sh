#!/usr/bin/env bash
# Setup del contenedor del Curso 2.
# Prepara las herramientas; NO instala Laravel: el proyecto nace en clase
# con composer create-project (o con .devcontainer/crear-proyecto.sh).
set -u

echo ""
echo "=== Preparando el entorno del Curso 2 ==="

# El estudiante es dueño de los volúmenes de vendor/ y node_modules/
sudo chown -R vscode:vscode vendor node_modules 2>/dev/null || true

# SQLite para PHP (Laravel 12 usa SQLite por defecto)
if ! php -m | grep -qi pdo_sqlite; then
    sudo apt-get update -qq && sudo apt-get install -y -qq php8.3-sqlite3 >/dev/null 2>&1 \
        || sudo apt-get install -y -qq php-sqlite3 >/dev/null 2>&1
fi

# pcntl: lo exige "php artisan pail" (los logs de composer run dev)
if ! php -m | grep -qi pcntl; then
    sudo docker-php-ext-install pcntl >/dev/null 2>&1 || true
    INI_SCAN_DIR=$(php --ini | grep 'Scan for additional' | awk -F': ' '{print $2}' | tr -d ' ')
    if [ -n "$INI_SCAN_DIR" ] && ! php -m | grep -qi pcntl; then
        echo 'extension=pcntl' | sudo tee "$INI_SCAN_DIR/docker-php-ext-pcntl.ini" >/dev/null
    fi
fi

# intl y zip: las exige Filament (sesión 4). El script es idempotente y también
# corre en cada arranque desde postStartCommand.
bash .devcontainer/preparar-filament.sh || true

# OPcache para que el entorno se sienta rápido en desarrollo
PHP_INI_DIR=$(php -i | grep 'Scan this dir' | awk -F'=> ' '{print $2}' | tr -d ' ')
if [ -n "$PHP_INI_DIR" ] && [ ! -f "$PHP_INI_DIR/opcache-dev.ini" ]; then
    printf 'opcache.enable_cli=1\nopcache.memory_consumption=128\nopcache.max_accelerated_files=10000\n' \
        | sudo tee "$PHP_INI_DIR/opcache-dev.ini" >/dev/null
fi

chmod +x .devcontainer/crear-proyecto.sh 2>/dev/null || true

echo ""
echo "=== Verificando herramientas ==="
FALLOS=0
for check in "php --version" "composer --version" "node --version" "npm --version" "git --version"; do
    NOMBRE=${check%% *}
    if SALIDA=$($check 2>/dev/null | head -1); then
        echo "  [OK] $NOMBRE: $SALIDA"
    else
        echo "  [FALTA] $NOMBRE no responde"
        FALLOS=$((FALLOS + 1))
    fi
done

echo ""
if [ "$FALLOS" -gt 0 ]; then
    echo "Hay $FALLOS herramienta(s) con problema. Avisa en el canal del curso con una captura."
elif [ -f artisan ]; then
    echo "Proyecto Laravel detectado. Siguientes pasos:"
    echo "  composer install && npm install   (si vendor/ está vacío)"
    echo "  composer run dev                  (levanta Laravel y Vite juntos)"
else
    echo "Todo listo. El proyecto se crea en clase con:"
    echo "  bash .devcontainer/crear-proyecto.sh"
    echo "(o mira la guía para hacerlo a mano con composer create-project)"
fi
echo ""
