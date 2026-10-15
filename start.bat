@echo off
echo Starting PHP Server on port 1100...
echo Open http://localhost:1100 in your browser
echo Press Ctrl+C to stop the server
echo.

C:\Program Files\php\php.exe -S localhost:1100 -t public public\.router.php
