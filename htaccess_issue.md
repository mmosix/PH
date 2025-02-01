# Apache Configuration Issue Analysis

## Current Issue
The server is reporting the following error:
```
[core:alert] /var/www/vhosts/anvillive.com/ph.anvillive.com/.htaccess: <DirectoryMatch not allowed here
```

## Analysis
1. This error persists despite no DirectoryMatch directive being present in the .htaccess file
2. The error occurs in a virtual host environment (ph.anvillive.com)
3. Multiple .htaccess configurations have been attempted without success

## Root Cause
This error typically indicates one of the following:

1. **Server-Level Restrictions**: The VirtualHost configuration or global Apache configuration is restricting .htaccess capabilities
2. **Permission Issues**: The server configuration might have AllowOverride set too restrictively
3. **Inherited Configuration**: There might be conflicting directives in parent .htaccess files or server configuration

## Recommended Solutions

### 1. Server Configuration Update
Request the server administrator to check and update the VirtualHost configuration:

```apache
<VirtualHost *:80>
    ServerName ph.anvillive.com
    DocumentRoot /var/www/vhosts/anvillive.com/ph.anvillive.com
    
    <Directory "/var/www/vhosts/anvillive.com/ph.anvillive.com">
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 2. Temporary Alternative
Until the server configuration is updated, consider creating a subdirectory-specific Apache configuration file (if allowed by hosting provider):

1. Create a file named `ph.anvillive.com.conf` in the Apache configuration directory
2. Add the VirtualHost configuration there
3. Include the rewrite rules directly in the VirtualHost configuration instead of .htaccess

### 3. .htaccess Configuration
Once the server configuration is corrected, use this minimal .htaccess configuration:

```apache
RewriteEngine On
RewriteCond %{THE_REQUEST} !^/public/
RewriteRule !^public/ public%{REQUEST_URI} [L]
```

## Next Steps
1. Contact the server administrator to verify the VirtualHost configuration
2. Request AllowOverride and Directory permissions review
3. Check for any parent .htaccess files that might be causing conflicts
4. Consider moving the rewrite rules to the server configuration if .htaccess continues to be problematic