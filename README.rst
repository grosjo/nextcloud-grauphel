*****************************
grauphel - tomboy REST server
*****************************
nextCloud__ application implementing the `Tomboy`__ `REST API`__ for syncing notes.

Pretty stable.

I have taken over the repository. Hopefully a new version by end of 2021

__ https://nextcloud.com/
__ https://wiki.gnome.org/Apps/Tomboy
__ https://wiki.gnome.org/Apps/Tomboy/Synchronization/REST/1.0


.. contents::

Please submit requests/bugs via the [GitHub issue tracker](https://github.com/grosjo/nextcloud-grauphel/issues).
A Matrix Room exists also at : #nextcloud-grauphel:matrix.grosjo.net


What works
==========
- Note synchronization
- OAuth token management interface
- Database management interface (reset)
- Viewing notes
- Searching notes
- Downloading notes as HTML and reStructuredText

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
__ https://github.com/tomboy-notes/tomdroid


Known working versions
======================
grauphel 1.0.0 is known to work with:

* Tomboy 1.15.2, Linux
* Tomboy 1.15.1, Windows
* Tomdroid 0.7.5, Android 4.4.1

See the HowTo__ document for client-specific configuration instructions.

__ docs/howto.rst


=============
Functionality
=============

Search
======
You can use nextCloud's global search on the top right.

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


============
Installation
============

.. note::
   grauphel needs to be activated for all users.
   It will not work with the "enable only for specific groups" setting.

App store installation
======================
#. Log into nextcloud as administrator
#. Goto "Apps"
#. Enable experimental apps in the settings
#. Click "Productivity"
#. Look for "grauphel - Tomboy sync server"
#. Click "Activate"


Manual installation
===================

#. SSH onto your web server
#. Navigate to the nextcloud ``apps`` directory, often in ``/var/www/nextcloud/apps``
#. Download the latest release from https://github.com/grosjo/nextcloud-grauphel/releases/
   and extract it.
   For example::

     $ wget https://github.com/grosjo/fts-xapian/archive/refs/tags/1.0.0.tar.gz -O grauphel-1.0.0.tar.gz
     $ tar xjvf grauphel-1.0.0.tar.gz
     $ rm grauphel-1.0.0.tar.gz

   You do have a directory ``/var/www/nextcloud/apps/grauphel`` now.
#. Using your browser, login as administrator into nextCloud and click
   the "Apps" icon in the main menu ("+" icon).
#. Click on "Grauphel: Tomboy note server" and then on the "Enable" button.
#. In the main menu, click the "Tomboy notes" icon.

It may be that grauphel now shows you an error message::

  PHP extension "oauth" is required

You have to install the PHP PECL oauth extension now.
On Debian 9 or higher, do the following::

  $ apt install php-oauth
  $ phpenmod oauth
  $ /etc/init.d/apache2 restart

Reload the nextCloud page in your browser now.

.. note::
   ``oauth-1.2.3`` is only needed on PHP 5.x
   For PHP 7 simply use ``pecl install oauth``.


========
Download
========
.. LATESTRELEASE

See https://github.com/grosjo/nextcloud-grauphel/releases/
for all released versions.

======
Author
======
Joan Moreau, jom@grosjo.net

Christian Weiske, cweiske@cweiske.de, http://cweiske.de/


=====
Links
=====
- `Homepage`__
- `grauphel on apps.nextcloud.com`__
- `Source code repository`__
- `Github source code mirror`__

__ http://cweiske.de/grauphel.htm
__ http://apps.nextcloud.com/apps/grauphel
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

#. Validate ``appinfo/info.xml``::

     $ xmllint --noout --schema tools/info.xsd appinfo/info.xml

#. Validate ``appinfo/database.xml``::

     $ xmllint --noout --schema tools/database.xsd appinfo/database.xml

#. Fill the ``ChangeLog`` file with the changes since the last release,
   mention the new version number.
#. Update ``README.rst`` and increase the version number
#. Create the grauphel release file with::

     $ phing

   The file will be in ``dist/``
