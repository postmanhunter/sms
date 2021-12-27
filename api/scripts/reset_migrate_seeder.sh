#!/bin/bash
php /usr/share/nginx/html/www/download/api/artisan migrate
php /usr/share/nginx/html/www/download/api/artisan db:seed --class=UsersSeeder
php /usr/share/nginx/html/www/download/api/artisan db:seed --class=MessageSeeder