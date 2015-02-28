Some considerations about [Fortsu's workout log](http://entrenos.fortsu.com):

  1. [How it works](#howitworks)
    1. [Infrastructure](#infrastructure)
  2. [Sources](#sources)
    1. [Structure](#structure)
    2. [External dependencies](#dependencies)
    3. [License](#license)

# <a name='howitworks'>How it works</a> 

Basicaly it is a website to track sport (running, cycling, swimming and hiking) activities. What makes it different from other options is the advantage of accessing to source code (see [License](#license) for more details) by anyone. Usage is absolutely free of charge.

In addition any user can at anytime export all his/her information (tracks, metadata) from the system. Tracks are exported as GPX files and database metadata as JSON.

Activities can be uploaded in three ways:

* Entering data manually using a form.
* Uploading file.
* Using [Garmin Connect plugin](http://developer.garmin.com/web-device/garmin-communicator-plugin/).

Supported source data formats:

* [GPX v1.x](http://www.topografix.com/gpx.asp) ([schema](http://www.topografix.com/GPX/1/1/))
  * [GPX+](https://www.cluetrust.com/Schemas/gpxdata10.xsd) To support additional data like heartrate, cadence, laps, etc.
* [TCX v2](http://developer.garmin.com/schemas/tcx/v2/)
* [FIT](http://wiki.openstreetmap.org/wiki/FIT)

## <a name='infrastrucure'>Infrastructure</a>

It is a Linux Apache MySQL PHP (LAMP) web application hosted on a virtual private server running Ubuntu 13.10.

# <a name='sources'>Sources</a>

Programming languages used are PHP 5.x and javascript.

Additional PHP5 libraries:

* php5-gd
* php5-json
* php5-mcrypt
* php5-mysql
* php5-xsl

## <a name='structure'>Structure</a>

* __classes__
  * __Utils__
    * __Graphs__
    * __Parser__
* __config__
* __public__
  * __admin__
  * __communicator-api__
  * __estilo__
  * __fonts__
  * __forms__
  * __images__
  * __js__
  * __oauth__
  * __osm__
  * __tmp__
  * __users__
* __reports__
* __scripts__
  * __db_releases__
  * __GarminFit__
* __tests__
  * __data__
* __transform__

## <a name='dependencies'>External dependencies</a>

Need to be satisfied on server side:
* [GarminFit](http://pub.ks-and-ks.ne.jp/cycling/GarminFIT.shtml)
* [jQuery](http://jquery.com/)
* [jQueryUI](http://jqueryui.com/)
* [OpenLayers](http://openlayers.org/two/)
* [phpMyGraph](http://phpmygraph.abisvmm.nl/)
* [composer](https://getcomposer.org/)
  * [log4php](http://logging.apache.org/log4php/)
* TrueType fonts
  * [Ubuntu](http://font.ubuntu.com/)
  * [Rock Salt](http://www.fontsquirrel.com/fonts/rock-salt)

Need to be satisfied on client side:
* Garmin Communicator plugin (only when uploading activities using it)
  * [Garmin official version](http://developer.garmin.com/web-device/garmin-communicator-plugin/)
  * [GNU/Linux version](https://github.com/adiesner/GarminPlugin)

## <a name='license'>License</a>

Copyright (C) 2012-2014 by David García Granda (dgranda at gmail dot com).

"entrenos" is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or any later version.

"entrenos" is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License along with "entrenos". If not, see <http://www.gnu.org/licenses/>.

A copy of the License can be retrieved from [http://www.gnu.org/licenses/agpl-3.0.html](here) and is also available in the file called "COPYING".
