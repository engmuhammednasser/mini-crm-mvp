@echo off
echo Running Database Migrations and Seeding...
"C:\Users\engmo\OneDrive\Desktop\CRM\php85\php.exe" artisan migrate --seed
pause
