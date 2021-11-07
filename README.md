# Symfony API Skeleton

## How to use the skeleton

```shell
composer create-project tseho/symfony-api-skeleton [directory]
```

## Production

build the docker image:
```shell
DOCKER_IMAGE_NAME=foo DOCKER_IMAGE_VERSION=latest make docker-image
```

launch apache+php on port 8080:
```shell
docker run -d -p 127.0.0.1:8080:8080/tcp $DOCKER_IMAGE_NAME:$DOCKER_IMAGE_VERSION
```

## Development

### Start the project in 3 steps

1) Create the default `.env` file
```shell
make .env
```
2) Edit the values in `.env`, if necessary
3) Start the docker-compose development environment:
```shell
make up
```

### Commands

Stop the development environment:
```shell
make down
```
Execute a symfony command:
```shell
docker-compose run --rm php bin/console [cmd]
```
Execute a composer command:
```shell
docker-compose run --rm composer [cmd]
```
Launch the tests:
```shell
make tests
```
