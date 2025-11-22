# Deployment Guide: Loan Management System on AWS EC2

This guide assumes you have an AWS EC2 instance (Ubuntu 22.04 or similar) running and you have SSH access to it.

## Prerequisites

1.  **SSH Access**: Ensure you can SSH into your server:
    ```bash
    ssh -i /path/to/your-key.pem ubuntu@your-server-ip
    ```

## Step 1: Install Docker and Git

Run the following commands on your EC2 server to install Docker and Git:

```bash
# Update package index
sudo apt-get update

# Install required packages
sudo apt-get install -y ca-certificates curl gnupg git

# Add Docker's official GPG key
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

# Set up the repository
echo \
  "deb [arch="$(dpkg --print-architecture)" signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  "$(. /etc/os-release && echo "$VERSION_CODENAME")" stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker Engine
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Add your user to the docker group (so you don't need sudo for docker commands)
sudo usermod -aG docker $USER
newgrp docker
```

## Step 2: Setup the Project

1.  **Clone the repository** (or copy your files):
    ```bash
    # If using git (replace with your repo URL)
    git clone https://github.com/chandachewe10/loan-management-system.git
    cd loan-management-system
    
    # OR if copying files manually, ensure you are in the project root
    ```

2.  **Configure Environment Variables**:
    ```bash
    cp .env.example .env
    nano .env
    ```
    Update the following in `.env`:
    - `APP_URL=http://your-server-ip`
    - `DB_HOST=db`
    - `DB_PASSWORD=your_secure_password` (must match `MYSQL_ROOT_PASSWORD` in docker-compose.yml if hardcoded, or better yet, use the env var)

    **Important**: Since we are using Docker, `DB_HOST` must be `db` (the service name in docker-compose.yml).

## Step 3: Build and Run Containers

```bash
# Build and start containers in detached mode
docker compose up -d --build
```

Check if containers are running:
```bash
docker compose ps
```

## Step 4: Application Setup

Run these commands *inside* the container to set up Laravel:

```bash
# Install PHP dependencies
docker compose exec app composer install --optimize-autoloader --no-dev

# Generate App Key
docker compose exec app php artisan key:generate

# Run Database Migrations
docker compose exec app php artisan migrate --force

# Link Storage
docker compose exec app php artisan storage:link

# Optimize Cache
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

## Step 5: Build Frontend Assets

Since this project uses Vite, you need to build assets. The `Dockerfile` includes Node.js for this purpose.

```bash
docker compose exec app npm install
docker compose exec app npm run build
```

## Step 6: Create Admin User & Verify Email

1. Create your admin user:
   ```bash
   docker compose exec app php artisan make:filament-user
   ```

2. Manually verify the email (since there's no mail server):
   ```bash
   docker compose exec app php artisan tinker --execute="App\Models\User::where('email', 'your-admin-email@example.com')->update(['email_verified_at' => now()]);"
   ```
   Replace `your-admin-email@example.com` with the email you just created.

3. Clear all Laravel caches:
   ```bash
   docker compose exec app php artisan config:clear
   docker compose exec app php artisan cache:clear
   docker compose exec app php artisan route:clear
   ```

## Step 7: Verify Deployment

Open your browser and visit `http://your-server-ip`. You should see the Loan Management System login page.

Log in at `http://your-server-ip/admin` with the credentials you created.

## Troubleshooting

- **Subscription Required Error**: If you see this, clear the cache:
  ```bash
  docker compose exec app php artisan config:clear && docker compose exec app php artisan cache:clear
  ```
  Then log out and log back in.

- **Permissions**: If you see permission errors, run:
  ```bash
  docker compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
  ```

- **Logs**: Check container logs if something fails:
  ```bash
  docker compose logs -f app
  docker compose logs -f nginx
  ```

- **Port 80 already in use**: If another service is using port 80, edit `docker-compose.yml` and change `80:80` to `8080:80`, then access via `http://your-server-ip:8080`

## Security Notes for Production

> [!WARNING]
> Before going to production, make sure to:
> - Change `APP_DEBUG=false` in `.env`
> - Set a strong `DB_PASSWORD`
> - Configure AWS Security Groups to allow only necessary ports
> - Set up SSL/HTTPS with a domain name (using certbot/nginx)
> - Set `APP_URL` to your actual domain
