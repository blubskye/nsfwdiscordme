# Installation Guide

Complete setup instructions for deploying the NSFW Discord Directory.

## Table of Contents

- [Requirements](#requirements)
- [Clone and Build](#clone-and-build)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Nginx Setup](#nginx-setup)
- [Cron Jobs](#cron-jobs)
- [Discord Setup](#discord-setup)
- [Running the Bot](#running-the-bot)

---

## Requirements

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 8.1+ | With GD, Redis, PDO extensions |
| MySQL | 5.7+ | Or MariaDB 10.2+ |
| Redis | 3+ | For caching and sessions |
| Elasticsearch | 6+ | For server search |
| Nginx | 1.10+ | With PHP-FPM |
| Node.js | 14+ | For building assets |
| Yarn | 1.x | Package manager |
| Composer | 2.x | PHP dependency manager |

### PHP Extensions Required

```bash
sudo apt install php8.1-fpm php8.1-mysql php8.1-redis php8.1-gd php8.1-xml php8.1-mbstring php8.1-curl
```

### Installing Elasticsearch

See: [Elasticsearch Installation Guide](https://www.elastic.co/guide/en/elasticsearch/reference/current/install-elasticsearch.html)

### Installing Redis

```bash
sudo apt install redis-server
sudo systemctl enable redis-server
```

---

## Clone and Build

```bash
# Clone the repository
cd /var/www
git clone https://github.com/blubskye/nsfwdiscordme.git
cd nsfwdiscordme

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies and build assets
yarn install
yarn run build
```

### Set File Permissions

```bash
sudo chown -R www-data:www-data /var/www/nsfwdiscordme
sudo chmod -R 755 /var/www/nsfwdiscordme
sudo chmod -R 775 /var/www/nsfwdiscordme/var
```

---

## Database Setup

### Create Database and User

Connect to MySQL and run:

```sql
CREATE USER 'nsfwdiscordme'@'localhost' IDENTIFIED BY 'your_secure_password';
CREATE DATABASE nsfwdiscordme CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON nsfwdiscordme.* TO 'nsfwdiscordme'@'localhost';
FLUSH PRIVILEGES;
```

### Run Migrations

```bash
bin/console doctrine:migrations:migrate --no-interaction
```

---

## Configuration

### Environment Variables

Copy the example environment file and edit it:

```bash
cp .env .env.local
nano .env.local
```

### Required Variables

```env
# Application
APP_ENV=prod
APP_SECRET=your_random_secret_here

# Database
DATABASE_URL="mysql://nsfwdiscordme:password@localhost:3306/nsfwdiscordme"

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379

# Discord OAuth2 (from Discord Developer Portal)
DISCORD_CLIENT_ID=your_client_id
DISCORD_CLIENT_SECRET=your_client_secret
DISCORD_BOT_TOKEN=your_bot_token
DISCORD_OAUTH_REDIRECT_URL=https://yourdomain.com/discord/oauth2/redirect

# reCAPTCHA (from Google reCAPTCHA admin)
RECAPTCHA_SITE_KEY=your_site_key
RECAPTCHA_SECRET_KEY=your_secret_key

# Misc
SNOWFLAKE_MACHINE_ID=1
```

---

## Nginx Setup

Create the Nginx configuration:

```bash
sudo nano /etc/nginx/sites-available/nsfwdiscordme.conf
```

### Configuration File

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/nsfwdiscordme/public;

    index index.php;

    # Logging
    error_log /var/log/nginx/nsfwdiscordme-error.log;
    access_log /var/log/nginx/nsfwdiscordme-access.log;

    # Gzip compression
    gzip on;
    gzip_min_length 1000;
    gzip_types application/javascript text/css application/json application/xml;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Static file caching
    location ~* \.(?:jpg|jpeg|gif|png|ico|svg|webp|woff2?)$ {
        expires 1M;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    location ~* \.(?:css|js)$ {
        expires 1M;
        access_log off;
        add_header Cache-Control "public";
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;

        # Environment variables (alternative to .env.local)
        # fastcgi_param APP_ENV "prod";
        # fastcgi_param DATABASE_URL "mysql://user:pass@localhost:3306/db";
        # ... etc
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ ^/(config|src|vendor)/ {
        deny all;
    }
}
```

### Enable the Site

```bash
sudo ln -s /etc/nginx/sites-available/nsfwdiscordme.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### SSL with Certbot (Recommended)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

---

## Cron Jobs

Install cron jobs for the application:

```bash
crontab -e
```

Add these lines:

```cron
# NSFW Discord Directory Cron Jobs
@hourly   /usr/bin/php /var/www/nsfwdiscordme/bin/console app:logs:check
@hourly   /usr/bin/php /var/www/nsfwdiscordme/bin/console app:server:online
@daily    /usr/bin/php /var/www/nsfwdiscordme/bin/console app:bumps:reset
0 */6 * * * /usr/bin/php /var/www/nsfwdiscordme/bin/console app:server:upgrades
```

### Cron Job Descriptions

| Schedule | Command | Description |
|----------|---------|-------------|
| Hourly | `app:logs:check` | Check for error logs and send notifications |
| Hourly | `app:server:online` | Update online member counts from Discord |
| Daily | `app:bumps:reset` | Reset server bump points |
| Every 6 hours | `app:server:upgrades` | Process expired premium subscriptions |

---

## Discord Setup

### Create Discord Application

1. Go to [Discord Developer Portal](https://discord.com/developers/applications)
2. Click "New Application" and give it a name
3. Go to **OAuth2** > **General**:
   - Copy the **Client ID** and **Client Secret**
   - Add redirect URL: `https://yourdomain.com/discord/oauth2/redirect`
4. Go to **Bot**:
   - Click "Add Bot"
   - Copy the **Bot Token**
   - Enable these intents under "Privileged Gateway Intents":
     - Server Members Intent (if needed)
     - Message Content Intent (if needed)

### Bot Permissions

When inviting the bot, it needs these permissions:
- Create Instant Invite

Invite URL format:
```
https://discord.com/api/oauth2/authorize?client_id=YOUR_CLIENT_ID&scope=bot&permissions=1
```

---

## Running the Bot

The Discord bot creates invite links for servers that use the "bot" invite type.

### Manual Start

```bash
php /var/www/nsfwdiscordme/bot/index.php
```

### With Supervisor (Recommended)

Create a Supervisor config:

```bash
sudo nano /etc/supervisor/conf.d/nsfwdiscordme-bot.conf
```

```ini
[program:nsfwdiscordme-bot]
command=/usr/bin/php /var/www/nsfwdiscordme/bot/index.php
directory=/var/www/nsfwdiscordme
user=www-data
autostart=true
autorestart=true
stderr_logfile=/var/log/nsfwdiscordme-bot.err.log
stdout_logfile=/var/log/nsfwdiscordme-bot.out.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start nsfwdiscordme-bot
```

---

## Troubleshooting

### Clear Cache

```bash
bin/console cache:clear --env=prod
```

### Check Logs

```bash
tail -f var/log/prod.log
tail -f /var/log/nginx/nsfwdiscordme-error.log
```

### Verify PHP Extensions

```bash
php -m | grep -E "(redis|gd|pdo_mysql)"
```

---

## Next Steps

- [Set up administrator accounts](admins.md)
- Configure your Discord application's OAuth2 settings
- Set up SSL with Let's Encrypt
