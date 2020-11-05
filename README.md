# Port Activity App / Fenix integration
Fenix is pilot ordering system used in Sweden.

Fenix provides API that is polled with IMO number to get pilotages for a vessel.

## Description
CLI job for polling timestamps from Fenix service

Polling is done for IMOs received from redis active port calls

## Configuring container
Copy .env.template to .env and fill values

## Configuring local development environment
Copy src/lib/init_local.php.sample.php to src/lib/init_local.php and fill values

## Running manually

### With docker compose
Configure container environment and
- `docker-compose build` Build container
- `docker-compose up` Start container. Will execute one polling run.
- `docker-compose stop` Stop container

### Locally
Configure development environment and
```make run```