-- Un solo PostgreSQL, dos bases: una para Laravel y otra para Django.
-- Este archivo solo corre la PRIMERA vez que nace el volumen.
CREATE DATABASE avisos_django;
GRANT ALL PRIVILEGES ON DATABASE avisos_django TO avisos;
