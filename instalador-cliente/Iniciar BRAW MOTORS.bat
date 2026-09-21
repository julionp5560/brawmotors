@echo off
REM ==========================================================
REM  BRAW MOTORS - Acceso directo con red de seguridad
REM  Intenta prender Apache y MySQL (si ya estan prendidos como
REM  servicio de Windows, esto no hace nada, no truena) y luego
REM  abre la app en el navegador.
REM ==========================================================

net start Apache2.4 >nul 2>&1
net start mysql >nul 2>&1

start "" "http://localhost/rawmotos/"
exit
