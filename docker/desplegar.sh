#!/bin/sh
# Pide el despliegue al servidor y espera a que termine.
#
# Lo corre el trabajo `desplegar` del pipeline. Usa tres variables que NO van
# en el repositorio: viven en Settings > CI/CD > Variables de tu proyecto.
#   SERVIDOR_URL       la direccion del panel, sin diagonal al final
#   SERVIDOR_TOKEN     tu token de despliegue (Masked)
#   SERVIDOR_UUID      el identificador de tu aplicacion en el servidor
# Y una mas, para comprobar al final que tu sitio de verdad responde:
#   DIRECCION_PUBLICA  tu direccion, por ejemplo https://tunombre.curso.lol
set -eu

: "${SERVIDOR_URL:?falta SERVIDOR_URL en las variables del proyecto}"
: "${SERVIDOR_TOKEN:?falta SERVIDOR_TOKEN en las variables del proyecto}"
: "${SERVIDOR_UUID:?falta SERVIDOR_UUID en las variables del proyecto}"

api() {
  curl --silent --show-error --fail-with-body \
    -H "Authorization: Bearer $SERVIDOR_TOKEN" "$@"
}

echo "Pidiendo el despliegue del commit ${CI_COMMIT_SHORT_SHA:-local}..."
respuesta=$(api -X POST "$SERVIDOR_URL/api/v1/deploy?uuid=$SERVIDOR_UUID") || {
  echo "El servidor rechazo la peticion:"
  echo "$respuesta"
  exit 1
}
despliegue=$(echo "$respuesta" | jq -r '.deployments[0].deployment_uuid // empty')
if [ -z "$despliegue" ]; then
  echo "El servidor no encolo nada. Respondio:"
  echo "$respuesta"
  exit 1
fi
echo "En fila con el identificador $despliegue"

# El servidor clona tu repositorio, construye tus tres imagenes y levanta el
# stack. Mientras, preguntamos cada 10 segundos como va. Tope: media hora.
anterior=""
estado=""
limite=$(( $(date +%s) + 1800 ))
while [ "$(date +%s)" -lt "$limite" ]; do
  estado=$(api "$SERVIDOR_URL/api/v1/deployments/$despliegue" | jq -r '.status')
  if [ "$estado" != "$anterior" ]; then
    echo "$(date +%H:%M:%S)  $estado"
    anterior=$estado
  fi
  case "$estado" in
    finished) break ;;
    failed|cancelled-by-user)
      echo "El servidor no pudo desplegar. Casi siempre es algo que en tu"
      echo "maquina si existe y en el repositorio no. Pruebalo desde un clon"
      echo "limpio: git clone, y luego docker compose build."
      exit 1 ;;
  esac
  sleep 10
done
[ "$estado" = finished ] || { echo "Pasaron 30 minutos y no termino."; exit 1; }

# Que el servidor diga "termine" no es lo mismo que que tu sitio responda.
if [ -n "${DIRECCION_PUBLICA:-}" ]; then
  codigo=""
  for intento in $(seq 1 18); do
    codigo=$(curl -s -o /tmp/pagina -w '%{http_code}' "$DIRECCION_PUBLICA/" || true)
    if [ "$codigo" = 200 ] && grep -q "<app-root" /tmp/pagina; then
      echo "$DIRECCION_PUBLICA responde 200 y sirve tu Angular."
      exit 0
    fi
    sleep 10
  done
  echo "Termino de desplegar, pero $DIRECCION_PUBLICA no responde bien (codigo $codigo)."
  exit 1
fi
