# Install dependencies  
```
composer install
```
  
# Set variables
```
nano .env  

# Environment
APP_ENV=prod

# Database
DATABASE_URL=mysql://root:root@127.0.0.1/artviewer
```
  
# Create database  
```
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```

# Permissions for cache and logs  
```
setfacl -R -m u:www-data:rwX var/cache var/log
setfacl -dR -m u:www-data:rwX var/cache var/log
```

# Apache config
```
composer require symfony/apache-pack
```
You may need "AllowOverride All" in your apache config.  

# CRON
```
*/5 * * * * php /var/www/artviewer/bin/console app:parse:rss
*/10 * * * * php /var/www/artviewer/bin/console app:parse:items --unparsed
0 0 * * * php /var/www/artviewer/bin/console app:parse:items --recent
```
