@echo off
setlocal enabledelayedexpansion

REM ==========================================================
REM   SIGET - Sistema de Gestion de Espacios y Turnos
REM   IES Nuevo Horizonte - Lanzador de entorno de desarrollo
REM ==========================================================
REM   Pensado para correr desde la carpeta raiz del proyecto,
REM   con Laragon abierto y MySQL/Apache en "Start All".
REM ==========================================================

cd /d "%~dp0"

echo.
echo ==========================================
echo   SIGET - Levantando entorno de desarrollo
echo ==========================================
echo.

REM --- .env ---
if not exist ".env" (
    if exist ".env.example" (
        echo [AVISO] No existe .env. Copiando desde .env.example...
        copy ".env.example" ".env" >nul
        call php artisan key:generate
    ) else (
        echo [ERROR] No existe .env ni .env.example. Crealo manualmente antes de continuar.
        pause
        exit /b 1
    )
)

REM --- Dependencias de Composer ---
if not exist "vendor\autoload.php" (
    echo [1/5] Instalando dependencias de Composer...
    call composer install
) else (
    echo [1/5] Dependencias de Composer OK.
)

REM --- Dependencias de npm ---
if not exist "node_modules\" (
    echo [2/5] Instalando dependencias de npm...
    call npm install
) else (
    echo [2/5] Dependencias de npm OK.
)

REM --- Conexion a la base de datos (Laragon: MySQL/Apache deben estar iniciados) ---
echo [3/5] Verificando conexion a la base de datos...
call php artisan migrate:status >nul 2>&1
if errorlevel 1 (
    echo [AVISO] No se pudo conectar a la base de datos.
    echo          Abri Laragon y confirma "Start All" ^(Apache + MySQL^) antes de continuar.
    pause
)

REM --- Enlace de storage (para ver las notas formales subidas) ---
if not exist "public\storage" (
    echo [4/5] Creando enlace de storage...
    call php artisan storage:link
) else (
    echo [4/5] Enlace de storage OK.
)

echo [5/5] Levantando servidores...
echo.

REM --- Vite (assets con hot-reload) en su propia ventana ---
start "SIGET - Vite (assets)" cmd /k "npm run dev"

REM --- Servidor de Laravel en su propia ventana ---
start "SIGET - Laravel (artisan serve)" cmd /k "php artisan serve"

REM --- Esperar un momento y abrir el navegador ---
timeout /t 3 /nobreak >nul
start http://127.0.0.1:8000

echo.
echo Listo. Se abrieron dos ventanas: una para Vite y otra para el servidor de Laravel.
echo Para detener el entorno, cerra esas dos ventanas (o Ctrl+C en cada una).
echo.
echo Nota: si preferis usar el virtual host de Laragon (ej: http://turnos-ies.test)
echo en vez de "php artisan serve", comenta esa linea de este .bat y abri esa URL.
echo.
pause
endlocal
