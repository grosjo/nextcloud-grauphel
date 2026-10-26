*****************************
grauphel - tomboy REST server
*****************************
nextCloud__ application implementing the `Tomboy`__ `REST API`__ for syncing notes.

Pretty stable.

I have taken over the repository.

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
Requirements
============
* Nextcloud 30 to 34
* PHP 8.1 or later (Nextcloud 34 needs PHP 8.2 or later)

Since version 1.1.0 the PHP PECL ``oauth`` extension is no longer needed;
grauphel implements OAuth 1.0a itself.


============
Installation
============

.. note::
   grauphel needs to be activated for all users.
   It will not work with the "enable only for specific groups" setting.

App store installation
======================
#. Log into Nextcloud as administrator
#. Go to "Apps"
#. Click "Organization"
#. Look for "Grauphel: Tomboy note server"
#. Click "Download and enable"


Manual installation
===================

#. Download the latest release archive from
   https://github.com/grosjo/nextcloud-grauphel/releases/
#. Extract it into the ``apps`` (or ``custom_apps``) directory of your
   Nextcloud, for example::

     $ cd /var/www/nextcloud/apps
     $ tar xzf ~/grauphel-1.1.0.tar.gz

   You have a directory ``/var/www/nextcloud/apps/grauphel`` now.
#. Enable the app in the web interface ("Apps" > "Disabled apps")
   or on the command line::

     $ sudo -u www-data php occ app:enable grauphel

#. In the main menu, click the "Tomboy notes" icon.


Upgrading from 1.0.x
====================
Notes, sync state and access tokens are kept; clients do not need to be
authorized again.
The upgrade adds database indexes, which may take a moment on large
installations.
The PECL ``oauth`` extension can be removed afterwards if no other
application needs it.


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
  We store it that way in the database and decode it before output.
* ``latest-sync-revision`` sent from Tomboy during PUT sync is already
  incremented by 1.


Tests
=====
Install the development dependencies and run the checks::

  $ composer install
  $ make lint psalm test

``make test`` needs ``xsltproc``.

The integration test runs the complete OAuth flow and a sync against a
running Nextcloud with two users.
It needs the PECL ``oauth`` extension *for the test client only*::

  $ php -d extension=oauth.so tests/integration/oauth-sync.php \
      http://localhost:8080/index.php/apps/grauphel \
      admin:password bob:password

GitHub Actions run all of these, the integration test against
Nextcloud 30 to 34 with SQLite, MariaDB and PostgreSQL.


Releasing
=========
#. Add the changes to ``news/CHANGELOG.md``.
#. Increase ``<version>`` in ``appinfo/info.xml``.
   Database changes need a new class in ``lib/Migration/``;
   it only runs when the version number increases.
#. Create the release archive::

     $ make appstore

   The file is ``build/artifacts/grauphel-VERSION.tar.gz``.
   If ``~/.nextcloud/certificates/grauphel.key`` and ``.crt`` exist and the
   app lies inside a Nextcloud server checkout, the code gets signed, too.
#. Tag the release in git and attach the archive to a GitHub release.
#. Upload to the App Store: create the signature with ``make sign``
   and paste it together with the download URL of the archive at
   https://apps.nextcloud.com/developer/apps/releases/new
