# VirtualWaitingRoom

## Setup

1. Build

```bash
docker compose down -v
docker compose build --no-cache php-fpm
docker compose up -d
```

2. Migrate

```bash
docker exec vwr_php php scripts/migrate.php
```

## Debug

in the file:
```php
error_log('USER FOUND: ' . json_encode($user));
```

in the console:
```bash
docker logs vwr_php -f
```