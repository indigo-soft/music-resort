@echo off
setlocal

set "DIR=%~dp0"
php "%DIR%bin\console" %*

endlocal
