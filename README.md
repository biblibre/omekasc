# Omeka S CLI

A command line interface for Omeka S

## Quick start

1. Download `omekasc.phar` from the
   [latest release](https://github.com/biblibre/omekasc/releases/latest)
   into a directory that is in your `$PATH`

   ```
   wget -O ~/.local/bin/omekasc https://github.com/biblibre/omekasc/releases/download/v0.4.0/omekasc.phar
   ```

2. Make it executable

   ```
   chmod +x ~/.local/bin/omekasc
   ```

3. Go to Omeka S directory

   ```
   cd /path/to/omeka
   ```

4. Use it

   ```
   # List all commands
   omekasc list

   # Get help about a command
   omekasc help settings:get
   ```

## Available commands

```
help                Displays help for a command
list                Lists commands
db:migrate          Run pending database migrations
module:activate     Activate a module
module:deactivate   Deactivate a module
module:install      Install a module
module:uninstall    Uninstall a module
module:upgrade      Upgrade a module
scaffold:module     Generates files for a new module
settings:get        List Omeka S settings
settings:set        Define Omeka S settings
user:set-password   Set a new password
```

## License

GNU General Public License v3.0 or later
