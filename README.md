# Chronic Demise - Clan Signature

This is a dynamic RuneScape clan signature banner (728 &#x2715; 150 px) generator script, using PHP with the GD image processing library.
In addition to fetching user/clan data from the official [RuneScape API](http://services.runescape.com/m=rswiki/en/Hiscores_APIs) for use
on the banners, the signature will feature a way for clan officers to award custom badges and accolades to clan members, as well as for
members to customize it.

The project includes an .htaccess for requesting the dynamic image using a .png extension instead of a .php extension. This is because
phpBB will not accept image URLs with a non-common image format extension. Right now the server doesn't store any generated images, but
regenerates the image from new or cached data. This will likely change in the future.

## Installation

Requires an installation of PHP 5.6 or later on a public-facing webserver.
1. Put the contents of "public_html" in its own subdirectory on your website.
2. Move [resources/](https://github.com/chronic-demise/clan-signature/tree/master/resources) to somewhere on your server
   (doesn't need to be public).
2. Modify [generate.php](https://github.com/chronic-demise/clan-signature/blob/master/public_html/generate.php)
   to specify allowed RuneScape usernames via whitelist.
3. Open [HiscoreParser.php](https://github.com/chronic-demise/clan-signature/blob/master/public_html/HiscoreParser.php)
   and modify the constants to fit your needs.
4. Instead of pointing to [generate.php](https://github.com/chronic-demise/clan-signature/blob/master/public_html/generate.php),
   use the alias "banner.png" (this gets around the pesky phpBB signature restrictions).
5. Append the desired username you want to the banner like so: `banner.png?user=Berserkguard`. Note that the stylizing
   of the name in the URL is the same as how it's rendered on the banner. Case matters!

## Thanks

1. Jagex for making RuneScape (http://www.runescape.com/community/)
2. My clanmates in Chronic Demise (http://chronicdemise.co.uk/), my RuneScape family since 2005
3. Font Library (https://fontlibrary.org/) for awesome open source fonts
4. phpBB (https://www.phpbb.com/) for a wonderful open source bulletin board

## License

This project is freely available under the MIT License (MIT). See "LICENSE" for details.
