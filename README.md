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

#### Add domain to /etc/hosts (host)

```bash
sudo vi /etc/hosts
127.0.0.111  ticklerio.test
```

#### Install mkcert (host)

```bash
sudo apt install libnss3-tools
curl -JLO "https://dl.filippo.io/mkcert/latest?for=linux/amd64"
chmod +x mkcert-v*-linux-amd64
sudo mv mkcert-v*-linux-amd64 /usr/local/bin/mkcert
cd config/ssls/
mkcert -install ticklerio.test
```

#### Build & Up containers (host)

```bash
cd ../../
docker-compose up --build -d
```

#### Create .env and database files

```bash
cp app/.env.example app/.env
touch app/database/database.sqlite
```

#### Create sessions folder

```bash
mkdir app/sessions
```

#### Build assets

```bash
docker-compose run node-service npm install
docker-compose run node-service npm run build
```

#### Run migration scripts

```bash
docker exec -it php-container bash
php database/migrate.php
```

Finally, open ticklerio.test on your browser