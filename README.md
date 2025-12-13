<div align="center">

# NSFW Discord Directory

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![PHP Version](https://img.shields.io/badge/PHP-8.5%2B-777BB4.svg)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-8.0-000000.svg)](https://symfony.com)
[![Node.js](https://img.shields.io/badge/Node.js-22%2B-339933.svg)](https://nodejs.org)
[![Discord API](https://img.shields.io/badge/Discord%20API-v10-5865F2.svg)](https://discord.com/developers/docs)

A Discord server directory and listing platform built with Symfony. Allows server owners to list, manage, and promote their Discord communities.

**Part of a two-project ecosystem** - Premium tier purchases are handled by the companion [stripewebsite](https://github.com/blubskye/stripewebsite) payment gateway. Both projects share the same MariaDB server.

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
| Backend | PHP 8.5+, Symfony 8.0 |
| Database | MariaDB 10.6+ / MySQL 8.0+ |
| Cache | Redis 7+ |
| Search | Elasticsearch 8+ |
| Frontend | Webpack 5, Bootstrap 5, ES6+ |
| Discord | Discord API v10, DiscordPHP |

## Requirements

- PHP 8.5 or higher
- MariaDB 10.6+ or MySQL 8.0+
- Redis 7+
- Elasticsearch 8+
- Nginx 1.18+
- Node.js 22+
- Composer 2.x

## Installation

### Quick Start

```bash
# Clone the repository
git clone https://github.com/blubskye/nsfwdiscordme.git
cd nsfwdiscordme

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Build frontend assets
npm run build

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
| `DATABASE_URL` | MariaDB/MySQL connection string |
| `REDIS_URL` | Redis connection URL |
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

## Security

This project takes security seriously. Below are the security measures implemented and vulnerabilities that have been addressed.

### Security Features

- **OAuth2 Authentication** - Secure Discord OAuth2 integration with state validation
- **CSRF Protection** - All forms include CSRF tokens via Symfony's form component
- **2FA for Admins** - Google Authenticator required for admin panel access
- **Input Validation** - Server-side validation on all user inputs
- **Parameterized Queries** - All database queries use Doctrine ORM with parameter binding
- **Password Hashing** - Bcrypt hashing for all stored passwords
- **Rate Limiting** - Discord API rate limiting to prevent abuse
- **reCAPTCHA** - Bot protection on public-facing forms

### Security Advisories (Fixed)

The following security vulnerabilities have been identified and fixed:

#### CVE-2025-XXXX-1: Open Redirect (CWE-601)
- **Severity**: High
- **Component**: `AuthController.php`
- **Description**: The `back` parameter in the login flow was not validated, allowing attackers to redirect users to malicious external sites after authentication.
- **Fix**: Implemented URL whitelist validation that only allows relative paths starting with approved prefixes.
- **CVSS Score**: 6.1 (Medium)

#### CVE-2025-XXXX-2: Insecure Deserialization (CWE-502)
- **Severity**: Critical
- **Component**: `RedisCacheHandler.php`
- **Description**: PHP's `unserialize()` was used on Redis cache data without class restrictions, potentially allowing remote code execution if Redis is compromised.
- **Fix**: Replaced PHP serialization with JSON encoding. Legacy data is decoded with `allowed_classes => false` to prevent object instantiation.
- **CVSS Score**: 9.8 (Critical)

#### CVE-2025-XXXX-3: ElasticSearch Injection (CWE-943)
- **Severity**: High
- **Component**: `SearchController.php`
- **Description**: User search input was passed directly to ElasticSearch's `QueryString` parser, which interprets Lucene query syntax and could allow unauthorized data access or DoS.
- **Fix**: Replaced `QueryString` with `MultiMatch` query type that does not parse special syntax. Added input sanitization and length limits.
- **CVSS Score**: 7.5 (High)

#### CVE-2025-XXXX-4: Cross-Site Scripting (CWE-79)
- **Severity**: Medium
- **Component**: `bootstrap_4_layout.html.twig`
- **Description**: Form help text was rendered with Twig's `|raw` filter, bypassing HTML escaping and potentially allowing XSS if help text contained user-controlled content.
- **Fix**: Removed unconditional `|raw` filter. Now only renders raw HTML when explicitly enabled via `help_html` form option.
- **CVSS Score**: 5.4 (Medium)

### Reporting Security Issues

If you discover a security vulnerability, please report it responsibly:

1. **Do NOT** create a public GitHub issue
2. Email security concerns to the repository maintainers
3. Include detailed steps to reproduce the vulnerability
4. Allow reasonable time for a fix before public disclosure

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

## Related Projects

| Project | Description |
|---------|-------------|
| [stripewebsite](https://github.com/blubskye/stripewebsite) | Stripe payment gateway for handling premium tier purchases |

Both projects are designed to run together on the same server, sharing the same MariaDB instance with separate databases.

## Links

- **Repository**: [github.com/blubskye/nsfwdiscordme](https://github.com/blubskye/nsfwdiscordme)
- **Discord API Docs**: [discord.com/developers/docs](https://discord.com/developers/docs)

---

<div align="center">
Made with PHP and Discord
</div>
