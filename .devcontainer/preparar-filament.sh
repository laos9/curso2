#!/usr/bin/env bash
# Prepara PHP para Filament: instala las extensiones intl y zip, que Filament
# exige y que la imagen del contenedor no trae. Tarda alrededor de un minuto
# la primera vez; despues no hace nada. Corre solo en cada arranque del
# contenedor (postStartCommand) y tambien puedes lanzarlo a mano:
#   bash .devcontainer/preparar-filament.sh

faltan=""
for ext in intl zip; do
    php -m 2>/dev/null | grep -qi "^${ext}$" || faltan="$faltan $ext"
done

if [ -z "$faltan" ]; then
    echo "[OK] intl y zip ya estan instaladas."
    exit 0
fi

echo "Instalando extensiones de PHP:${faltan} (alrededor de un minuto)..."
export DEBIAN_FRONTEND=noninteractive
LOG=/tmp/preparar-filament.log
: > "$LOG"
sudo apt-get update -qq >>"$LOG" 2>&1 || true
if ! sudo apt-get install -y -qq libicu-dev libzip-dev >>"$LOG" 2>&1; then
    echo "  AVISO: apt-get no pudo instalar libicu-dev/libzip-dev. Ultimas lineas:"
    tail -5 "$LOG"
fi

# docker-php-ext-install compila el modulo, pero su activacion automatica
# falla bajo sudo (no ve PHP_INI_DIR y busca /conf.d). El .ini se escribe a mano.
INI_SCAN_DIR=$(php --ini | grep 'Scan for additional' | awk -F': ' '{print $2}' | tr -d ' ')
EXT_DIR=$(php -r 'echo ini_get("extension_dir");')
for ext in $faltan; do
    echo "  compilando $ext..."
    if ! sudo docker-php-ext-install "$ext" >>"$LOG" 2>&1; then
        # docker-php-ext-enable falla bajo sudo aunque el .so ya exista: se revisa el .so
        if [ ! -f "$EXT_DIR/$ext.so" ]; then
            echo "  AVISO: no se pudo compilar $ext. Ultimas lineas de $LOG:"
            tail -8 "$LOG"
            continue
        fi
    fi
    if [ -n "$INI_SCAN_DIR" ]; then
        echo "extension=$ext" | sudo tee "$INI_SCAN_DIR/docker-php-ext-$ext.ini" >/dev/null
    fi
done

ok=1
for ext in intl zip; do
    if php -m 2>/dev/null | grep -qi "^${ext}$"; then
        echo "[OK] $ext instalada y activa."
    else
        echo "AVISO: no pude activar $ext."
        ok=0
    fi
done
[ "$ok" = 1 ] || { echo "El detalle completo esta en $LOG. Avisa en el canal del curso con una captura de este mensaje."; exit 1; }
