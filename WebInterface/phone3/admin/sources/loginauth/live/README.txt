Windows Live Registration and Application Key.

You will need to register your site as an application and receive an application ID to make use of Windows Live Services.

See http://msdn.microsoft.com/en-us/library/bb676626.aspx for more information.  When you register and are asked for the Return URL, you must use

http://(yourdomain.com)/(forums)/interface/board/live.php

Replace "(yourdomain.com)" with your domain name, and "(forums)" with the path to your forums.

Afterwards you will be presented with some necessary information.  You will need the application ID and the secret key.

Open /admin/sources/loginauth/live/Application-Key.xml and edit the "appid" and "secret" XML keys to the appropriate values supplied when you registered your site.  Then take this file and upload it to your server (it is recommended to store it outside of your public root directory for security purposes).  Finally, you will need to edit /admin/sources/loginauth/live/conf.php to supply the path (not URL) to this Application-Key.xml file.

If you require support or assistance, please submit a technical support ticket in your IPS Client Center.