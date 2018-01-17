*****************************
grauphel - tomboy REST server
*****************************
ownCloud__ application implementing the `Tomboy`__ `REST API`__ for syncing notes.

Pretty stable.

__ http://owncloud.org/
__ https://wiki.gnome.org/Apps/Tomboy
__ https://wiki.gnome.org/Apps/Tomboy/Synchronization/REST/1.0


.. contents::

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
__ https://launchpad.net/tomdroid


Known working versions
======================
grauphel 0.2.1 is known to work with:

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


============
Installation
============

.. note::
   grauphel needs to be activated for all users.
   It will not work with the "enable only for specific groups" setting.

App store installation
======================
#. Log into owncloud as administrator
#. Goto "Apps"
#. Enable experimental apps in the settings
#. Click "Productivity"
#. Look for "grauphel - Tomboy sync server"
#. Click "Activate"


Manual installation
===================

#. SSH onto your web server
#. Navigate to the owncloud ``apps`` directory, often in ``/var/www/owncloud/apps``
#. Download the latest release from http://cweiske.de/grauphel.htm#download
   and extract it.
   For example::

     $ wget http://cweiske.de/download/grauphel/grauphel-0.6.4.tar.gz
     $ tar xjvf grauphel-0.6.4.tar.gz
     $ rm grauphel-0.6.4.tar.gz

   You do have a directory ``/var/www/owncloud/apps/grauphel`` now.
#. Using your browser, login as administrator into ownCloud and click
   the "Apps" icon in the main menu ("+" icon).
#. Click on "Grauphel: Tomboy note server" and then on the "Enable" button.
#. In the main menu, click the "Tomboy notes" icon.

It may be that grauphel now shows you an error message::

  PHP extension "oauth" is required

You have to install the PHP PECL oauth extension now.
On Debian 7, do the following::

  $ apt-get install libpcre3-dev php-pear php5-dev
  $ pecl install oauth-1.2.3
  $ echo 'extension=oauth.so' > /etc/php5/conf.d/oauth.ini
  $ /etc/init.d/apache2 restart

Reload the ownCloud page in your browser now.

.. note::
   ``oauth-1.2.3`` is only needed on PHP 5.x
   For PHP 7 simply use ``pecl install oauth``.


========
Download
========
.. LATESTRELEASE

See `grauphel downloads page <http://cweiske.de/grauphel-download.htm>`_
for all released versions.

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


Unit testing
============
- `ownCloud unit testing documentation`__
- `grauphel on Travis CI`__

  .. image:: https://travis-ci.org/cweiske/grauphel.svg
     :target: https://travis-ci.org/cweiske/grauphel

__ https://doc.owncloud.org/server/8.0/developer_manual/core/unit-testing.html
__ https://travis-ci.org/cweiske/grauphel


Releasing grauphel
==================
To release a new version, do the following:

#. Increase version number in ``appinfo/version`` and ``appinfo/info.xml``.
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
#. Test the release on a server
#. Tag the release in git
#. Upload the release to http://cweiske.de/grauphel.htm with::

     $ cd ~/Dev/html/cweiske.de
     $ ./scripts/update-grauphel.sh

#. Link the new release on https://apps.owncloud.com/content/show.php?content=166654
