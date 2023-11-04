## Ticklerio

### Add domain to /etc/hosts (host)

```bash
sudo vi /etc/hosts
127.0.0.111  ticklerio.test
```

### Install mkcert (host)

```bash
sudo apt install libnss3-tools
curl -JLO "https://dl.filippo.io/mkcert/latest?for=linux/amd64"
chmod +x mkcert-v*-linux-amd64
sudo mv mkcert-v*-linux-amd64 /usr/local/bin/mkcert
cd config/ssls/
mkcert -install ticklerio.test
```

### Up containers (host)

```bash
cd ../../
docker-compose up --build -d
```

### Create & import databases

```bash
http://localhost:8080/
(root/root)

CREATE DATABASE ticklerio CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

cat live.sql | docker exec -i container_id /usr/bin/mysql -u root --password=root ticklerio
```

### Connect to container bash (host)

```bash
docker exec -it container_id bash
```

### Setup git

```bash
git config --global user.email "example@example.com"
git config --global user.name "John Doe"
```

### npm install / watch / install package (host)

```bash
docker-compose run node-service npm install
docker-compose run node-service npm run watch

# If you want to install a package
docker-compose run node-service npm i bootstrap --save-dev
```
