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


2 consolas para front y back

> php artisan serve el backend
> npm run dev el frontend








Comandos para la modificaciones del proyecto

creacion de nueva tabla > php artisan make:migration create_+nombres_plural +_table  > create_usuarios_table