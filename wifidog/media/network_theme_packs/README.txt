Directory: wifidog/media/network_theme_packs/
User-installable theme packs that can be chosen from the network admin insterface.  To create a new one, create a directory having the following structure:
theme_pack_id/		A directory, it's name becomes the theme pack's id
|- name.txt		The content of this file become the theme pack's name (Displayed in the theme selection menu)
|- description.txt	The content of this file become the theme pack's description (displayed in the help)
|- stylesheet.css	Mandatory:  the stylesheet used by the theme, will be applied after the stylesheet in base_theme
|- images/		Images used by this theme's stylesheet

Important note:  The encoding for name.txt and description.txt is assumed to be UTF-8
