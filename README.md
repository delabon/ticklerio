# Ticklerio

Ticklerio is a robust customer support ticketing system designed to streamline the process of handling customer inquiries and issues. Built with PHP and JavaScript, it offers a responsive and intuitive interface for support teams to manage tickets efficiently. Our system is thoroughly tested to ensure reliability and a seamless user experience.

## Tech Stack

- **Backend:** PHP 8.2
- **Frontend:** JavaScript, SASS, Bootstrap 5.3
- **Database:** SQLite
- **Testing:** PHPUnit for unit and integration tests. PHPUnit + Guzzle for feature tests.
- **Static Analysis:** PHPStan for analyzing code quality
- **Environment Management:** Docker for containerization and consistent development environments

## How to setup

#### Build & Up containers

```bash
# From the root folder run:
docker compose up -d
```

#### Create .env, database file, and sessions folder

```bash
cp app/.env.example app/.env
touch app/database/database.sqlite
mkdir app/sessions
```

#### Install required libraries using composer

```bash
docker compose run php-service composer install
```

#### Run migration scripts

```bash
docker compose run php-service php database/migrate.php
```

#### Run seeders if you like

```bash
# Admin account credentials: admin/123456789
docker compose run php-service php database/seed.php
```

#### Build assets

```bash
docker compose run --rm node-service npm install
docker compose run --rm node-service npm run build
```

Finally, open https://ticklerio.test on your browser
