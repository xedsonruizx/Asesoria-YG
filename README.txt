# Asesoria-YG
Pagina web con manejo de usuarios y roles, con formularios tipo quiz y crud para post

Proyecto realizado en Laravel Vue js

#Requerimientos
- php 8.1
- Maria DB
- composer
- node js 22.12.0
- npm 


En el archivo .env modificar los datos de conexion a la base de datos

#Inicializar proyecto

Crear base de datos para conectar

Crear tablas mediante comando >  php artisan migrate
Comando que creara tablas para el inicio de sesion



REFRESCAR RUTAS DEPUES DE NUEVA RUTAS
php artisan route:clear
php artisan config:clear
php artisan view:clear


LINKEAR CARPETA PARA GUARDAR ARCHIVOS
php artisan storage:link


2 consolas para front y back

> php artisan serve el backend
> npm run dev el frontend








Comandos para la modificaciones del proyecto

creacion de nueva tabla > php artisan make:migration create_+nombres_plural +_table  > create_usuarios_table





PROBLEMAS CON XAMPP?


Ejecuta:

netstat -ano | findstr :3306


Verás algo como:

TCP    0.0.0.0:3306   0.0.0.0:0   LISTENING   1234


→ El número (1234) es el PID.

Identifica el programa con:

tasklist /FI "PID eq 1234"


Si confirmas que es otro MySQL/MariaDB, elimínalo:

taskkill /PID 1234 /F