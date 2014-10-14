*****************************
grauphel - tomboy REST server
*****************************
ownCloud__ application implementing the `Tomboy`__ `REST API`__ for syncing notes.

Work in progress.

__ http://owncloud.org/
__ https://wiki.gnome.org/Apps/Tomboy
__ https://wiki.gnome.org/Apps/Tomboy/Synchronization/REST/1.0


======
Status
======

What works
==========
- Authentication works
- Note synchronization works
- OAuth token management interface works
- Database management interface (reset) works

What is missing
===============
- Web interface to view notes is missing


=================
Supported clients
=================
* Conboy__ (Nokia N900 Maemo)
* Tomboy__ (Linux, Windows)
* Tomdroid__ (Android)

__ http://conboy.garage.maemo.org/
__ https://wiki.gnome.org/Apps/Tomboy
__ https://launchpad.net/tomdroid


Known working versions
======================
grauphel 0.2.1 is known to work with:

* Tomboy 1.15.2, Linux
* Tomboy 1.15.1, Windows
* Tomdroid 0.7.5, Android 4.4.1


============
Dependencies
============
* PHP
* PHP `oauth extension`__

__ http://pecl.php.net/package/oauth


======
Author
======
Christian Weiske, cweiske@cweiske.de, http://cweiske.de/


=========
Home page
=========
- `grauphel on apps.owncloud.com`__
- `Source code repository`__
- `Github source code mirror`__

__ http://apps.owncloud.com/content/show.php?action=content&content=166654
__ http://git.cweiske.de/grauphel.git/
__ https://github.com/cweiske/grauphel
