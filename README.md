# JUR.io Task
## Steps to run the project

- Clone Repo and checkout to api_development branch
- Run Below commands to start docker
    - docker-compose up -d
    - docker-compose exec nginx chmod -R 777 /var/www/html/storage
    - docker-compose exec nginx chmod -R 777 /var/www/html/bootstrap
    - docker-compose exec php php artisan migrate
- Access APIs on http://localhost:8088
- Use [this] postman collection to test api endpoints.

[this]: <https://www.getpostman.com/collections/98b46c4ff12bc36a2ae4>
