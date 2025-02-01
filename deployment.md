# Deployment Guide

## File Structure Issue Resolution

The current error `Class "Auth" not found` on the live server indicates a mismatch between the deployed files and the expected structure. Follow these steps to resolve:

1. **Backup Current Files**
```bash
cd /var/www/vhosts/anvillive.com/ph.anvillive.com
cp -r public public.bak
```

2. **Verify File Structure**
```bash
# Required structure:
/var/www/vhosts/anvillive.com/ph.anvillive.com/
├── app/
│   └── Auth.php  # Must contain "namespace App;"
├── public/
│   └── login.php # Must use \App\Auth
├── vendor/
└── composer.json
```

3. **Fix Autoloader**
```bash
cd /var/www/vhosts/anvillive.com/ph.anvillive.com
# Run the fix script
php fix-autoload.php
```

4. **Check File Content**
Ensure login.php uses fully qualified namespace:
```php
\App\Auth::initialize();
\App\Auth::login($username, $password);
```

5. **Fix Permissions**
```bash
chown -R www-data:www-data .
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
```

6. **Clear Cache**
```bash
# For Apache
service apache2 restart
# Clear PHP opcache
php -r "opcache_reset();"
```

## Common Issues

1. **Multiple Versions**: If you see duplicate files, remove any outdated versions:
```bash
find /var/www/vhosts/anvillive.com/ph.anvillive.com -name login.php
```

2. **Symlinks**: If using symlinks, ensure they point to correct locations:
```bash
ls -la /var/www/vhosts/anvillive.com/ph.anvillive.com/public
```

3. **Composer**: If autoloader issues persist:
```bash
composer dump-autoload -o
composer clear-cache
```

## Verification

Run the test script to verify autoloader:
```bash
php public/test-autoload.php
```

Should output: "Auth class found in namespace App"