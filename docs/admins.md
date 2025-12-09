# Administrator Setup

This guide covers creating and managing administrator accounts for the NSFW Discord Directory.

## Overview

The admin panel is protected by two-factor authentication (2FA) using Google Authenticator or any compatible TOTP app.

## Prerequisites

Install a TOTP authenticator app on your phone:

- [Google Authenticator](https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2) (Android)
- [Google Authenticator](https://apps.apple.com/us/app/google-authenticator/id388497605) (iOS)
- [Authy](https://authy.com/) (Cross-platform)
- [1Password](https://1password.com/) (Built-in TOTP support)

---

## Creating an Administrator

### Step 1: User Must Log In First

The user must have already logged into the site using "Log in with Discord" at least once. This creates their account in the database.

### Step 2: Add Admin Role

Run the following command:

```bash
bin/console app:user:role-add
```

You will be prompted for:
1. **User identifier**: Enter the user's Discord email address or Discord ID
2. **Role**: Enter `ROLE_ADMIN`

Example:

```
$ bin/console app:user:role-add

Enter the user's Discord email or ID:
> user@example.com

Enter the role to add:
> ROLE_ADMIN

✓ Role ROLE_ADMIN added to user@example.com

Scan this QR code with Google Authenticator:
https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=...
```

### Step 3: Set Up 2FA

1. The command outputs a URL for a QR code
2. Open the URL in a browser to see the QR code
3. Scan it with your authenticator app
4. The app will now generate 6-digit codes for login

### Step 4: Log In to Admin Panel

1. Have the user log out and log back in to refresh their session
2. Navigate to `/admin/login`
3. Enter the 6-digit code from the authenticator app
4. Access granted!

---

## Role Hierarchy

| Role | Access Level |
|------|--------------|
| `ROLE_USER` | Default role for all logged-in users |
| `ROLE_ADMIN` | Can access admin panel with 2FA |
| `ROLE_SUPER_ADMIN` | Full admin access, can manage other admins |

The hierarchy works upward:
- `ROLE_SUPER_ADMIN` includes all `ROLE_ADMIN` permissions
- `ROLE_ADMIN` includes all `ROLE_USER` permissions

---

## Removing Admin Access

To remove a role from a user:

```bash
bin/console app:user:role-remove
```

Example:

```
$ bin/console app:user:role-remove

Enter the user's Discord email or ID:
> user@example.com

Enter the role to remove:
> ROLE_ADMIN

✓ Role ROLE_ADMIN removed from user@example.com
```

---

## Admin Panel Features

The admin panel (`/admin`) provides:

### Content Management
- **Servers** - View, edit, enable/disable server listings
- **Users** - Manage user accounts
- **Categories** - Manage server categories
- **Tags** - Manage server tags

### Moderation
- **Banned Users** - Block specific Discord users
- **Banned Servers** - Block specific Discord servers
- **Banned Words** - Content filter for prohibited words

### Analytics
- **Server Events** - View bumps, views, and joins
- **Admin Events** - Audit log of admin actions
- **Purchases** - View premium upgrade transactions

---

## Security Best Practices

1. **Keep 2FA Secret Safe** - Never share your authenticator secret
2. **Use Strong Discord Password** - Admin access depends on Discord OAuth
3. **Review Admin Logs** - Regularly check `/admin/adminevent` for suspicious activity
4. **Limit Super Admins** - Only grant `ROLE_SUPER_ADMIN` when necessary
5. **Backup 2FA Codes** - Store backup codes securely in case you lose your phone

---

## Troubleshooting

### "Invalid 2FA Code"

- Ensure your phone's time is synced correctly (use automatic time)
- Wait for the next code cycle (codes change every 30 seconds)
- If still failing, re-run `app:user:role-add` to generate a new 2FA secret

### "Access Denied"

- Verify the user has `ROLE_ADMIN` or higher
- Have the user log out and log back in
- Check the console output when adding the role for errors

### Lost 2FA Access

If a user loses access to their authenticator:

1. Remove their admin role: `bin/console app:user:role-remove`
2. Re-add the role: `bin/console app:user:role-add`
3. This generates a new 2FA secret they can scan

---

## See Also

- [Installation Guide](installing.md)
- [Main README](../README.md)
