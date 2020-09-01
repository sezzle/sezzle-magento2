# Sezzle Magento2 Module Demo Store

> This setup is for development purpose only, NOT for production purpose.

## Setup
1. `cp ./internal/docker.sample.env ./internal/docker.env`
2. Configure environment variables in `./internal/docker.env`
3. Run the container with `docker-compose up -d`
4. Install Magento and configure Sezzle module using `docker exec -it sezzle_magento2 install`
5. Login to the [Admin dashboard](http://127.0.0.1:8095/admin) using the admin username and password configured in `docker.env`

Sezzle plugin directory is mounted to the container. please refer to volumes in `docker-compose.yml`.

> Magento starts support of MySQL 5.7 in version 2.1.2. Before 2.1.2, MySQL 5.6 should be used.

## Cleanup
```bash
docker-compose down --rmi local -v --remove-orphans
```
