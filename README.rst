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
- Note synchronization
- OAuth token management interface
- Database management interface (reset)
- Viewing notes
- Searching notes

What is missing
===============
- Web interface to edit notes. I will probably not implement this.
  Patches welcome :-)


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


=============
Functionality
=============

Search
======
You can use ownCloud's global search on the top right.

During search, the note's titles, tags and content are searched.

Search syntax:

``foo``
  Search for notes containing "foo"
``foo bar``
  Search for notes containing "foo" and "bar"
``"foo bar" baz``
  Search for notes containing "foo bar" and "baz"
``foo -bar``
  Search for notes containing "foo" but not "bar"


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


=====
Links
=====
- `Homepage`__
- `grauphel on apps.owncloud.com`__
- `Source code repository`__
- `Github source code mirror`__

__ http://cweiske.de/grauphel.htm
__ http://apps.owncloud.com/content/show.php?action=content&content=166654
__ http://git.cweiske.de/grauphel.git/
__ https://github.com/cweiske/grauphel


=================
Development hints
=================
* JSON coming from Tomboy: Title is html-escaped already
  (e.g. ``>`` is ``&gt;``).
  We store it that way in the database, so there is no need to escape the
  output.
* ``latest-sync-revision`` sent from Tomboy during PUT sync is already
  incremented by 1.
