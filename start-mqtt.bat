@echo off
title GarageLink MQTT Subscriber
color 0A
echo ============================================
echo  GarageLink MQTT Subscriber
echo  Durdurmak icin bu pencereyi kapatin.
echo ============================================
echo.

:loop
echo [%DATE% %TIME%] Baglaniyor...
php artisan mqtt:subscribe
echo.
echo [%DATE% %TIME%] Subscriber durdu. 3 saniye sonra yeniden baslatiliyor...
echo.
timeout /t 3 /nobreak > nul
goto loop
