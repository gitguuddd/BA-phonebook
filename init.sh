#!/bin/bash
docker-compose build
docker-compose up -d
docker exec -ti php_phonebook mkdir -p config/jwt
docker exec -ti php_phonebook openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
docker exec -ti php_phonebook openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
docker exec -ti php_phonebook php bin/console --no-interaction doctrine:migrations:migrate
docker exec -ti php_phonebook php bin/console --no-interaction doctrine:migrations:migrate --env=test
docker exec -ti php_phonebook php bin/console --no-interaction doctrine:fixtures:load --env=test