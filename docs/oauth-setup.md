# OAuth Setup Guide

This guide will help you set up OAuth authentication with Google and Discord for your Finanzas App.

## Environment Variables

Add the following variables to your `.env` file:

```env
# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here

# Discord OAuth
DISCORD_CLIENT_ID=your_discord_client_id_here
DISCORD_CLIENT_SECRET=your_discord_client_secret_here
```

## OAuth Provider Setup

### 1. Google OAuth Setup

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google+ API
4. Go to "Credentials" → "Create Credentials" → "OAuth 2.0 Client IDs"
5. Set the application type to "Web application"
6. Add this redirect URI:
   - `http://localhost:8000/oauth/callback/google` (works for both admin and finances panels)
   - `https://yourdomain.com/oauth/callback/google` (for production)
7. Copy the Client ID and Client Secret to your `.env` file

### 2. Discord OAuth Setup

1. Go to the [Discord Developer Portal](https://discord.com/developers/applications)
2. Click "New Application"
3. Give your application a name
4. Go to the "OAuth2" section
5. Add this redirect URI:
   - `http://localhost:8000/oauth/callback/discord` (works for both admin and finances panels)
   - `https://yourdomain.com/oauth/callback/discord` (for production)
6. Copy the Client ID and Client Secret to your `.env` file

## Additional Package Installation

Discord requires an additional Socialite provider package (already installed):

```bash
composer require socialiteproviders/discord
```

The Discord provider has been registered in `AppServiceProvider.php`.

## Database Migration

Run the following commands to set up the required database tables:

```bash
php artisan migrate
```

This will create:
- The `socialite_users` table to store OAuth user data
- Make the `password` field nullable in the `users` table

## Available Panels

OAuth login is now available on both panels:

### Admin Panel
- URL: `http://localhost:8000/admin`
- OAuth Routes:
  - Google: `/admin/oauth/redirect/google`
  - Discord: `/admin/oauth/redirect/discord`

### Finances Panel
- URL: `http://localhost:8000/finances`
- OAuth Routes:
  - Google: `/finances/oauth/redirect/google`
  - Discord: `/finances/oauth/redirect/discord`

## Multi-Panel Callback

Both panels use a shared callback system:
- Google: `/oauth/callback/google`
- Discord: `/oauth/callback/discord`

This means you only need to configure **one redirect URI per provider** in your OAuth applications, and it will work for both panels.

## Testing the Setup

1. Make sure your Laravel server is running
2. Visit either panel:
   - Admin: `http://localhost:8000/admin`
   - Finances: `http://localhost:8000/finances`
3. You should see OAuth login buttons for Google and Discord
4. Click any of them to test the OAuth flow

## Troubleshooting

### Common Issues:

1. **"Client error: 401 Unauthorized"**
   - Check that your Client ID and Client Secret are correct
   - Ensure the redirect URI matches exactly what you configured in the OAuth provider

2. **"The redirect URI provided does not match"**
   - Verify the redirect URIs in your OAuth provider settings
   - Make sure you're using: `http://localhost:8000/oauth/callback/{provider}`

3. **"SVG not found" errors**
   - The FontAwesome package should be installed and working
   - Try clearing cache: `php artisan config:clear && php artisan view:clear`

## Security Notes

- Never commit your `.env` file to version control
- Use different OAuth applications for development and production
- Consider enabling two-factor authentication on your OAuth provider accounts
- Regularly rotate your client secrets

## Additional Configuration

### Scopes (Optional)

You can add specific scopes to request additional permissions:

```php
// In AdminPanelProvider.php or FinancesPanelProvider.php
Provider::make('google')
    ->label('Google')
    ->icon('fab-google')
    ->color(Color::hex('#4285f4'))
    ->scopes(['email', 'profile']) // Add specific scopes
```

### Domain Restrictions (Optional)

To restrict OAuth login to specific domains:

```php
// In AdminPanelProvider.php or FinancesPanelProvider.php
FilamentSocialitePlugin::make()
    ->providers([...])
    ->domainAllowList(['yourdomain.com']) // Only allow users from this domain
``` 
