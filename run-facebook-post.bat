@echo off

cd /d D:\xampp\htdocs\facebook-poster

D:\xampp\php\php.exe artisan facebook:post >> storage\logs\facebook-post.log 2>&1
