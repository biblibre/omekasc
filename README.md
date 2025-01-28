# Omeka S CLI

A command line interface for Omeka S

## Quick start

1. Download `omekasc.phar` from the
   [latest release](https://git.biblibre.com/omeka-s/omekasc/releases/latest)
   into a directory that is in your `$PATH`

   ```
   wget -O ~/.local/bin/omekasc https://git.biblibre.com/omeka-s/omekasc/releases/download/latest/omekasc.phar
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

## License

GNU General Public License v3.0 or later
