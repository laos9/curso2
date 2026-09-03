#!/usr/bin/env bash
# Nivelacion del blog para la sesion 4 (Filament).
#
# Deja tu proyecto con lo minimo que Filament necesita: los modelos Post y
# Categoria, sus tablas (con las columnas publicado y user_id) y datos de
# practica. Es IDEMPOTENTE: solo crea lo que falta y nunca toca un archivo
# que ya exista. Si ya tienes tu blog de las sesiones 2 y 3, no hace nada.
#
#   bash .devcontainer/nivelar-blog.sh
set -e

if [ ! -f artisan ]; then
    echo "Aqui no hay proyecto Laravel (no encuentro 'artisan')."
    echo "Primero: bash .devcontainer/crear-proyecto.sh"
    exit 1
fi

N=.devcontainer/nivelacion
M=database/migrations

copia() {   # copia ORIGEN DESTINO solo si el destino no existe
    if [ -f "$2" ]; then
        echo "  [ya existe] $2"
    else
        cp "$1" "$2"
        echo "  [creado]    $2"
    fi
}
hay_migracion() {   # hay_migracion PATRON  -> true si algun archivo de migraciones lo menciona
    grep -qs "$1" "$M"/*.php 2>/dev/null
}

echo ""
echo "=== 1/3 Modelos ==="
copia "$N/Categoria.php" app/Models/Categoria.php
copia "$N/Post.php"      app/Models/Post.php

# Si el modelo Post ya era tuyo, revisa que Filament pueda guardar estas columnas
if ! grep -q "'publicado'" app/Models/Post.php || ! grep -q "'user_id'" app/Models/Post.php; then
    echo ""
    echo "  AVISO: tu app/Models/Post.php no tiene 'publicado' y/o 'user_id' en \$fillable."
    echo "         Filament los va a mostrar en el formulario pero NO los guardaria."
    echo "         Agrega ambos a \$fillable (es una linea) antes de seguir."
fi

echo ""
echo "=== 2/3 Migraciones ==="
if hay_migracion "create('categorias'"; then
    echo "  [ya existe] tabla categorias"
else
    copia "$N/2026_09_03_000001_create_categorias_table.php" "$M/2026_09_03_000001_create_categorias_table.php"
fi

if hay_migracion "create('posts'"; then
    echo "  [ya existe] tabla posts"
    # tu tabla existe: solo se agregan las columnas que falten
    if hay_migracion "'publicado'"; then
        echo "  [ya existe] columna publicado"
    else
        copia "$N/2026_09_03_000003_add_publicado_to_posts_table.php" "$M/2026_09_03_000003_add_publicado_to_posts_table.php"
    fi
    if hay_migracion "'user_id'"; then
        echo "  [ya existe] columna user_id"
    else
        copia "$N/2026_09_03_000004_add_user_id_to_posts_table.php" "$M/2026_09_03_000004_add_user_id_to_posts_table.php"
    fi
else
    # no tenias blog: la tabla nace completa, con publicado y user_id
    copia "$N/2026_09_03_000002_create_posts_table.php" "$M/2026_09_03_000002_create_posts_table.php"
fi

[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan migrate --no-interaction

echo ""
echo "=== 3/3 Datos de practica ==="
# Los seeders llegaron con la base del curso (sesiones 2 y 3). Se llaman por
# clase para no tener que editar tu DatabaseSeeder.
for S in CategoriaSeeder PostSeeder UserSeeder; do
    if [ -f "database/seeders/$S.php" ]; then
        php artisan db:seed --class="$S" --no-interaction
    else
        echo "  AVISO: falta database/seeders/$S.php (haz el pull del curso: git merge upstream/main)"
    fi
done

echo ""
echo "=== Listo. Usuarios de practica: admin@blog.test y editor@blog.test, clave secreto123 ==="
