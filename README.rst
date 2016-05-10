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

     $ wget http://cweiske.de/download/grauphel/grauphel-0.6.4.tar.bz2
     $ tar xjvf grauphel-0.6.4.tar.bz2
     $ rm grauphel-0.6.4.tar.bz2

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
The list of changes in each version can found in the `ChangeLog`__.

__ http://git.cweiske.de/grauphel.git/blob/HEAD:/ChangeLog

* `grauphel-0.6.4.tar.gz <http://cweiske.de/download/grauphel/grauphel-0.6.4.tar.gz>`__,
  36 kiB, 2016-05-10,
  SHA256: ``6de8ea450c5141cf11e272d20ac3b6a009d1537d083dab03d208819153c7fde5``
* `grauphel-0.6.3.tar.gz <http://cweiske.de/download/grauphel/grauphel-0.6.3.tar.gz>`__,
  36 kiB, 2016-04-27,
  SHA256: ``eb021af9d99a6d88831af361163af9163fce4ded5f5833caf9764b5f6cd3ff27``
* `grauphel-0.6.2.tar.gz <http://cweiske.de/download/grauphel/grauphel-0.6.2.tar.gz>`__,
  36 kiB, 2016-03-18,
  SHA256: ``94786f9ef167d9b71e036ed70aea594d29b2edac3f49431d5da568fec513e5ee``
* `grauphel-0.6.1.tar.gz <http://cweiske.de/download/grauphel/grauphel-0.6.1.tar.gz>`__,
  35 kiB, 2015-09-22,
  SHA256: ``f86cf7b47be857d8a87d413b6315c336e83e9c4beba2cb6ed0eaea8d2b3ea1c3``

  * `grauphel-0.6.1.tar.bz2 <http://cweiske.de/download/grauphel/grauphel-0.6.1.tar.bz2>`__,
    31 kiB, 2015-09-21,
    SHA256: ``b755b32a453617536eb202cd9d29129df1b04311b633d971108c310a4b9b2e4b``
* `grauphel-0.6.0.tar.bz2 <http://cweiske.de/download/grauphel/grauphel-0.6.0.tar.bz2>`__,
  31 kiB, 2015-09-18,
  SHA256: ``42e66ed4db1f0c24ca25e46ac4be6e523352f431daaefb22140a2a2e621049b8``
* `grauphel-0.5.1.tar.bz2 <http://cweiske.de/download/grauphel/grauphel-0.5.1.tar.bz2>`__,
  29 kiB, 2015-06-04,
  SHA256: ``fdb6232fa0d09a72e8355e5e4610403717ffe5c7f7193af2b36d991f1eb76127``
* `grauphel-0.5.0.tar.bz2 <http://cweiske.de/download/grauphel/grauphel-0.5.0.tar.bz2>`__,
  29 kiB, 2015-03-17,
  SHA256: ``9bbd5426cd7cd36f603c49b0635f24cb9507cf857480edc1f72df0ea0107f7de``
* `grauphel-0.4.0.tar.bz2 <http://cweiske.de/download/grauphel/grauphel-0.4.0.tar.bz2>`__,
  24 kiB, 2014-10-29,
  SHA256: ``a625ed127af04af4c0a658fcac8156557ef8098eaeddb72281842ad0c0c00b71``
* `grauphel-0.3.0.tar.bz2 <http://cweiske.de/download/grauphel/grauphel-0.3.0.tar.bz2">`__,
  17 kiB, 2014-10-14,
  SHA256: ``c59ac4cab6d747a4fe89ebd59c92e7ec80f9e2fb3b1edf8904357bc161897ae8``
* `grauphel-0.2.1.tar.bz2 <http://cweiske.de/download/grauphel/grauphel-0.2.1.tar.bz2>`__,
  13 kiB, 2014-10-03,
  SHA256: ``b66db077fc3e117b2e143b5b177c1d9a8a86c43029936ea65300a4d822e9fdda``
* `grauphel-0.2.0.tar.bz2 <http://cweiske.de/download/grauphel/grauphel-0.2.0.tar.bz2>`__,
  13 kiB, 2014-09-27,
  SHA256: ``abb1372e8b8525237bea1b686aa6ee2d390974f84bf2206d3aacc2c191978162``


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
#. Fill the ``ChangeLog`` file with the changes since the last release,
   mention the new version number.
#. Update ``README.rst`` and increase the version number
#. Create the grauphel release file with::

     $ phing

   The file will be in ``dist/``
#. Test the release on a server
#. Tag the release in git
#. Upload the release to http://cweiske.de/grauphel.htm with::

     $ phing update-website

#. Link the new release on https://apps.owncloud.com/content/show.php?content=166654
