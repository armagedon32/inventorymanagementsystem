@echo off
setlocal enabledelayedexpansion
title PASO Inventory System - Launcher
cd /d "%~dp0"

for /f %%a in ('echo prompt $E ^| cmd') do set "ESC=%%a"
set "GREEN=%ESC%[92m"
set "YELLOW=%ESC%[93m"
set "RED=%ESC%[91m"
set "CYAN=%ESC%[96m"
set "WHITE=%ESC%[97m"
set "RESET=%ESC%[0m"

where node >nul 2>nul
if errorlevel 1 goto no_node
for /f "delims=" %%v in ('node -v') do set "NODEVER=%%v"

echo.
echo %GREEN%==============================================%RESET%
echo %GREEN%  PROPERTY AND SUPPLIES OFFICE - INVENTORY   %RESET%
echo %GREEN%==============================================%RESET%
echo.
echo %CYAN%  Node version : %RESET% %WHITE%%NODEVER%%RESET%
echo %CYAN%  Location     : %RESET% %WHITE%%~dp0%RESET%
echo.

:menu
echo.
echo %YELLOW%Choose an option:%RESET%
echo.
echo   [1] Run in DEVELOPMENT mode ^(hot reload, browser auto-opens^)
echo   [2] Run in PRODUCTION mode  ^(build once, then start^)
echo   [3] Reset database ^(seed fresh demo data + admin account^)
echo   [4] Install / repair dependencies
echo   [5] Exit
echo.
set /p "choice=Enter your choice [1-5]: "

if "%choice%"=="1" goto dev
if "%choice%"=="2" goto prod
if "%choice%"=="3" goto seed
if "%choice%"=="4" goto install
if "%choice%"=="5" exit /b 0
echo %RED%Invalid choice. Please try again.%RESET%
goto menu

:install
echo.
echo %CYAN%Installing dependencies (this may take a few minutes)...%RESET%
call npm install
if errorlevel 1 goto fail
echo %GREEN%Dependencies installed.%RESET%
goto menu

:seed
echo.
echo %CYAN%Checking if dependencies are installed...%RESET%
if not exist "node_modules" goto install_for_seed
echo %YELLOW%WARNING: This will DELETE the current database and create fresh demo data.%RESET%
set /p "confirm=Type RESET then press Enter to continue (any other key cancels): "
if not "%confirm%"=="RESET" goto seed_cancel
del /q "server\data\custodian.db" "server\data\custodian.db-shm" "server\data\custodian.db-wal" >nul 2>nul
echo %CYAN%Resetting database...%RESET%
call npm run seed
echo.
echo %GREEN%Database ready!%RESET%
echo %YELLOW%  Admin login : superadmin / admin123%RESET%
echo %YELLOW%  Intern login: intern / intern123%RESET%
echo %YELLOW%  Assistant   : assistant / assistant123%RESET%
echo.
pause
goto menu

:seed_cancel
echo %YELLOW%Reset cancelled. Nothing was changed.%RESET%
pause
goto menu

:install_for_seed
echo %YELLOW%Dependencies not found. Installing first...%RESET%
call npm install
if errorlevel 1 goto fail
echo %GREEN%Dependencies installed. Continuing with seed...%RESET%
goto seed

:dev
echo.
if not exist "node_modules" goto install_for_dev
if not exist "server\data\custodian.db" goto seed_for_dev
echo.
echo %GREEN%Starting DEVELOPMENT mode...%RESET%
echo %CYAN%  API     : http://localhost:5000%RESET%
echo %CYAN%  Browser : http://localhost:5173  ^(opens automatically^)%RESET%
echo %CYAN%  Press Ctrl+C to stop.%RESET%
echo.
timeout /t 3 >nul
start "" http://localhost:5173
call npm run dev
goto done

:install_for_dev
echo %YELLOW%Dependencies not found. Installing first...%RESET%
call npm install
if errorlevel 1 goto fail
goto dev

:seed_for_dev
echo %YELLOW%Database not found. Seeding first...%RESET%
call npm run seed
goto dev

:prod
echo.
if not exist "node_modules" goto install_for_prod
if not exist "server\data\custodian.db" goto seed_for_prod
echo.
echo %GREEN%Building the app ^(first time takes a while^)...%RESET%
call npm run build
if errorlevel 1 goto fail
echo.
echo %GREEN%Starting PRODUCTION mode...%RESET%
echo %CYAN%  App : http://localhost:5000  ^(opens automatically^)%RESET%
echo %CYAN%  Press Ctrl+C to stop.%RESET%
echo.
timeout /t 3 >nul
start "" http://localhost:5000
call npm start
goto done

:install_for_prod
echo %YELLOW%Dependencies not found. Installing first...%RESET%
call npm install
if errorlevel 1 goto fail
goto prod

:seed_for_prod
echo %YELLOW%Database not found. Seeding first...%RESET%
call npm run seed
goto prod

:no_node
echo.
echo %RED%[ERROR] Node.js is NOT installed or not in PATH.%RESET%
echo %YELLOW%Download it from: https://nodejs.org ^(LTS version recommended^)%RESET%
echo %YELLOW%Restart this file after installing.%RESET%
echo.
pause
exit /b 1

:fail
echo.
echo %RED%Something went wrong. Check the error messages above.%RESET%
echo %YELLOW%Tip: make sure you are connected to the internet the first time.%RESET%
pause
exit /b 1

:done
echo.
echo %RED%Server stopped.%RESET%
pause
goto menu