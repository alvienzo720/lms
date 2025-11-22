# Local Setup Guide: Loan Management System (Docker)

This guide will help you run the Loan Management System on your local machine using Docker.

## Prerequisites

- **Docker Desktop**: Ensure Docker Desktop is installed and running. [Download here](https://www.docker.com/products/docker-desktop/).

## Step 1: Configuration & Passwords

1.  **Copy the environment file**:
    ```bash
    cp .env.example .env
    ```

2.  **Configure `.env`**:
    Open `.env` in your code editor and update the following database settings. This is where you set your passwords.

    ```ini
    APP_URL=http://localhost

    # Database Configuration
    DB_CONNECTION=mysql
    DB_HOST=db  <-- IMPORTANT: Must be 'db' (not 127.0.0.1) for Docker
    DB_PORT=3306
    DB_DATABASE=loan_system
    DB_USERNAME=root
    DB_PASSWORD=secret
    ```

    > **CRITICAL**: You MUST change `DB_HOST` from `127.0.0.1` to `db`.
    > Inside the Docker container, `127.0.0.1` refers to the container itself, not the database. `db` refers to the database service.

## Step 2: Build and Start Containers

Run the following command in your terminal (from the project root):

```bash
docker compose up -d --build
```

- `up`: Starts the containers.
- `-d`: Detached mode (runs in the background).
- `--build`: Rebuilds the images if you made changes to the Dockerfile.

Check if they are running:
```bash
docker compose ps
```

## Step 3: Install Dependencies & Setup

Now we need to install the PHP and Node.js dependencies *inside* the containers.

1.  **Install PHP Dependencies**:
    ```bash
    docker compose exec app composer install
    ```

2.  **Generate Application Key**:
    ```bash
    docker compose exec app php artisan key:generate
    ```

3.  **Run Database Migrations**:
    This creates the tables in your database.
    ```bash
    docker compose exec app php artisan migrate
    ```

    > **Tip**: If you want some dummy data, run `docker compose exec app php artisan migrate --seed`.

4.  **Link Storage**:
    ```bash
    docker compose exec app php artisan storage:link
    ```

## Step 4: Verify Admin User Email

Since we don't have a mail server configured locally, you need to manually verify your admin email in the database:

```bash
docker compose exec app php artisan tinker --execute="App\Models\User::where('email', 'your-admin-email@example.com')->update(['email_verified_at' => now()]);"
```

Replace `your-admin-email@example.com` with the email you used when creating your admin user.

## Step 5: Build Frontend Assets

Install Node.js dependencies and build the assets (CSS/JS):

```bash
docker compose exec app npm install
docker compose exec app npm run build
```

## Step 6: Access the Application

Open your browser and go to: [http://localhost](http://localhost)

## Step 7: Create Admin User

Create your admin user with Filament's built-in command:

```bash
docker compose exec app php artisan make:filament-user
```

This will prompt you for name, email, and password. Log in at [http://localhost/admin](http://localhost/admin).

## Step 8: Clear Laravel Cache

After initial setup, clear all caches to ensure everything works properly:

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan route:clear
```

## Step 9: Stopping the Application

To stop the containers:
```bash
docker compose down
```

## Troubleshooting

### Subscription Required Error
If you see a subscription required error, the cache might not be cleared. Run:
```bash
docker compose exec app php artisan config:clear && docker compose exec app php artisan cache:clear
```
Then log out and log back in.

### Other Issues
- **Can't access admin panel**: Make sure your email is verified (see Step 4)
- **Assets not loading**: Run `docker compose exec app npm run build` again
- **Database errors**: Ensure `DB_HOST=db` in your `.env` file
