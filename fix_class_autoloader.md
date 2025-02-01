# Fixing Class Autoloader Issues in Production

## Problem
The PHP autoloader is unable to find the `App\Auth` class in the production environment, despite correct PSR-4 configuration in composer.json.

## Verification Steps

1. Confirm file locations:
   ```bash
   # Check if Auth.php exists in the correct location
   ls -l /var/www/vhosts/anvillive.com/ph.anvillive.com/app/Auth.php
   
   # Check if vendor directory exists
   ls -l /var/www/vhosts/anvillive.com/ph.anvillive.com/vendor
   ```

2. Verify file permissions:
   ```bash
   # Ensure files are readable by web server
   sudo chown -R www-data:www-data /var/www/vhosts/anvillive.com/ph.anvillive.com
   sudo chmod -R 755 /var/www/vhosts/anvillive.com/ph.anvillive.com
   ```

3. Regenerate composer autoloader:
   ```bash
   cd /var/www/vhosts/anvillive.com/ph.anvillive.com
   composer dump-autoload -o
   ```

4. Clear PHP opcache:
   ```bash
   # Add to a PHP file to clear opcache
   php -r "opcache_reset();"
   # Or restart PHP-FPM
   sudo systemctl restart php8.0-fpm  # adjust version as needed
   ```

5. Verify composer installation:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

6. Debug class loading:
   ```php
   // Add to login.php temporarily for debugging
   var_dump(get_include_path());
   var_dump(file_exists(__DIR__ . '/../vendor/autoload.php'));
   var_dump(file_exists(__DIR__ . '/../app/Auth.php'));
   ```

## Common Issues

1. **Incorrect File Permissions**: Ensure web server has read access to all required files
2. **Composer Not Installed**: Verify composer is installed in production
3. **Autoloader Not Generated**: Run composer dump-autoload after deployment
4. **Cache Issues**: Clear PHP opcache after deploying new code
5. **Path Issues**: Verify absolute paths match production environment structure

## Prevention

1. Add composer dump-autoload to deployment scripts
2. Include file permission updates in deployment process
3. Implement proper staging environment that matches production
4. Use deployment checklist that includes autoloader verification

## Additional Notes

- Always run composer with --optimize-autoloader in production
- Consider implementing a healthcheck that verifies critical classes can be loaded
- Monitor PHP error logs for early detection of autoloading issues