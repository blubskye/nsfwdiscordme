<div align="center">

# NSFW Discord Directory

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-6.4%20LTS-000000.svg)](https://symfony.com)
[![Discord API](https://img.shields.io/badge/Discord%20API-v10-5865F2.svg)](https://discord.com/developers/docs)

A Discord server directory and listing platform built with Symfony. Allows server owners to list, manage, and promote their Discord communities.

[Features](#features) • [Requirements](#requirements) • [Installation](#installation) • [Documentation](#documentation) • [License](#license)

</div>

---

## Features

- **Server Listings** - Browse and discover Discord servers with search and filtering
- **Premium Tiers** - Ruby, Topaz, and Emerald upgrade tiers with enhanced visibility
- **Bump System** - Servers can be "bumped" to increase ranking (6-hour cooldown)
- **Server Management** - Edit server details, manage team members, view analytics
- **Discord OAuth2** - Secure authentication via Discord
- **Admin Panel** - EasyAdmin-powered moderation tools with 2FA
- **Bot Integration** - Discord bot for creating server invites
- **Elasticsearch** - Fast full-text search across all servers

## Tech Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP 8.2+, Symfony 6.4 LTS |
| Database | MySQL 5.7+ |
| Cache | Redis 3+ |
| Search | Elasticsearch 6+ |
| Frontend | Webpack, Bootstrap 4, ES6 |
| Discord | Discord API v10, DiscordPHP |

## Requirements

- PHP 8.2 or higher
- MySQL 5.7+
- Redis 3+
- Elasticsearch 6+
- Nginx 1.10+
- Node.js & Yarn
- Composer

## Installation

### Quick Start

```bash
# Clone the repository
git clone https://github.com/blubskye/nsfwdiscordme.git
cd nsfwdiscordme

# Install dependencies
composer install
yarn install

# Build frontend assets
yarn run build

# Configure environment
cp .env .env.local
# Edit .env.local with your settings

# Run database migrations
bin/console doctrine:migrations:migrate
```

### Detailed Guides

- **[Installation Guide](docs/installing.md)** - Complete setup instructions including Nginx configuration
- **[Admin Setup](docs/admins.md)** - Creating administrator accounts with 2FA

## Configuration

### Environment Variables

| Variable | Description |
|----------|-------------|
| `DATABASE_URL` | MySQL connection string |
| `REDIS_HOST` | Redis server hostname |
| `DISCORD_CLIENT_ID` | Discord OAuth2 application ID |
| `DISCORD_CLIENT_SECRET` | Discord OAuth2 secret |
| `DISCORD_BOT_TOKEN` | Bot token for invite creation |
| `RECAPTCHA_SITE_KEY` | Google reCAPTCHA site key |
| `RECAPTCHA_SECRET_KEY` | Google reCAPTCHA secret |

### Cron Jobs

```bash
@hourly  bin/console app:logs:check        # Check error logs
@hourly  bin/console app:server:online     # Update online member counts
@daily   bin/console app:bumps:reset       # Reset daily bump counts
0 */6 * * * bin/console app:server:upgrades # Process premium subscriptions
```

## Project Structure

```
nsfwdiscordme/
├── assets/          # Frontend JS/CSS source files
├── bin/             # Console commands
├── bot/             # Discord bot
├── config/          # Symfony configuration
├── docs/            # Documentation
├── public/          # Web root
├── src/
│   ├── Command/     # CLI commands
│   ├── Controller/  # Route handlers
│   ├── Entity/      # Doctrine entities
│   ├── Repository/  # Database queries
│   ├── Services/    # Business logic
│   └── Security/    # Authentication
├── templates/       # Twig templates
└── translations/    # i18n files
```

## Running the Bot

The Discord bot is used to create invite links for servers:

```bash
php bot/index.php
```

Make sure `DISCORD_BOT_TOKEN` is configured in your environment.

## Documentation

| Document | Description |
|----------|-------------|
| [Installing](docs/installing.md) | Full installation and deployment guide |
| [Admins](docs/admins.md) | Administrator account setup with 2FA |

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the **GNU Affero General Public License v3.0** - see the [LICENSE](LICENSE) file for details.

[![AGPL v3](https://www.gnu.org/graphics/agplv3-with-text-162x68.png)](https://www.gnu.org/licenses/agpl-3.0)

## Links

- **Repository**: [github.com/blubskye/nsfwdiscordme](https://github.com/blubskye/nsfwdiscordme)
- **Discord API Docs**: [discord.com/developers/docs](https://discord.com/developers/docs)

---

<div align="center">
Made with PHP and Discord
</div>
