## Installation

These instructions assume you installed FreeScout using the [recommended process](https://github.com/freescout-helpdesk/freescout/wiki/Installation-Guide), the "one-click install" or the "interactive installation bash-script", and you are viewing this page using a macOS or Ubuntu system.

Other installations are possible, but not supported here.

1. Download the [latest release of UnSub](https://github.com/aarhus/unsub).

2. Unzip the file and then place the unzipped folder in the Modules directory in the web root (i.e. /var/www/html/Modules/ )

3. Make sure that the files are readable by the webserver....

   ```sh
   chown -r www-data:www-data /var/www/html/Modules/UnSub/
   ```

4. Access your admin modules page like https://freescout.example.com/modules/list.

5. Find **Aarhus Unsub** and click ACTIVATE.

6. Use and enjoy!

7. [Buy Matt a Coffee](https://ko-fi.com/aarhus)
