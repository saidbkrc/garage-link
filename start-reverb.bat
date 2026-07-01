@echo off
title GarageLink Reverb (WebSocket)
color 0B
echo ============================================
echo  GarageLink Reverb WebSocket Sunucusu
echo  Canli guncelleme icin gereklidir.
echo  Durdurmak icin bu pencereyi kapatin.
echo ============================================
echo.

:loop
echo [%DATE% %TIME%] Reverb baslatiliyor (0.0.0.0:8080)...
php artisan reverb:start
echo.
echo [%DATE% %TIME%] Reverb durdu. 3 saniye sonra yeniden baslatiliyor...
echo.
timeout /t 3 /nobreak > nul
goto loop
