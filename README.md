# VirtualWaitingRoom

## Setup

1. Build

```bash
docker compose down -v
docker compose build --no-cache php-fpm
docker compose up -d
```

`-v` option on `docker compose down -v` wipes the db

2. Migrate

```bash
docker exec vwr_php php scripts/migrate.php
```

3. Visit http://localhost:8080/
4. Mail goes to http://localhost:8025/

## Debug

in the file:
```php
error_log('USER FOUND: ' . json_encode($user));
```

in the console:
```bash
docker logs vwr_php -f
```

## Auth
```php
AuthMiddleware::handle();                    // any logged-in user
AuthMiddleware::require('admin');            // admin only
AuthMiddleware::require('admin', 'teacher'); // admin or teacher
$user = AuthMiddleware::user();              // get current user
```