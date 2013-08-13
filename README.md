bandwidthd-pSQL-frontend
========================

PHP frontend to display bandwidthd data that was stored in a postgreSQL database

The origin of this project is: [http://sourceforge.net/projects/bandwidthd](http://sourceforge.net/projects/bandwidthd)
All glory goes to the author of [bandwidthd](http://sourceforge.net/projects/bandwidthd)

The main aim of this partly fork is to make the PHP files more robust against SQL injections.
I'm starting this fork because the author of the original [bandwidthd does no development on it anymore](http://sourceforge.net/p/bandwidthd/discussion/308608/thread/cef76694/#7b3f) 
but I'm very happy to merge it back if someone has access to the sourceforge project page.

DATABASE SUPPORT section from bandwidthd-2.0.1 README file
----------------------------------------------------------

Since version 2.0, Bandwidthd now has support for external databases.  This system 
consists of 3 major parts:

1. The Bandwidthd binary which acts as a sensor, recording traffic information and 
storing it in a database across the network or on the local host.  In this mode 
Bandwidthd uses very little ram and CPU. In addition, multiple sensors can record 
to the same database.

2. The database system.  Currently Bandwidthd only supports Postgresql.

3. The webserver and php application. Bundled with Bandwidthd in the "phphtdocs" 
directory is a php application that reports on and graphs the contents of the database.  
This has been designed to be easy to customize.  Everything is passed around on the urls, 
just tinker with it a little and you'll see how to generate custom graphs pretty easy.
**The files you find in this repository replace the files mentioned above.**

Using Bandwidthd with a database has many advantages, such as much lower overhead, because 
graphs are only graphed on demand.  And much more flexibility, SQL makes building new 
reports easy, and php+sql greatly improves the interactivity of the reports.

My ISP has now switched over to the database driven version of bandwidthd entirely, we 
have half a dozen sensors sprinkled around the country, writing millions of data points a 
day on our customers into the system.

INSTRUCTIONS

As a prerequisite for these instructions, you must have Postgresql installed and working, 
as well as a web server that supports php.

Database Setup:
1. Create a database for Bandwidthd.  You will need to create users that can access the 
database remotely if you want remote sensors.

2. Bandwidthd's schema is in "schema.postgresql".  "psql mydb username < schema.postgresql" 
should load it and create the 2 tables and 4 indexes.Thi

Bandwidthd Setup:
1. Add the following lines to your bandwidthd.conf file:

# Standard postgres connect string, just like php, see postgres docs for
# details
pgsql_connect_string "user = someuser dbname = mydb host = databaseserver.com"
# Arbitrary sensor name, I recommend the sensors fully qualified domain
# name
sensor_id "sensor1.mycompany.com"
# Tells Bandwidthd to keep no data and preform no graphing locally
graph false
# If this is set to true Bandwidthd will try to recover the daily log 
# into the database.  If you set this true on purpose only do it once.
# Bandwidthd does not track the fact that it has already transferred 
# certain records into the database.
recover_cdf false

4. Simply start bandwidthd, and after a few minutes data should start appearing in 
your database.  If not, check syslog for error messages.

Web Server Setup:
1. Copy the contents of this repository into your web tree some where.
2. Edit config.conf to set your db connect string

You should now be able to access the web application and see you graphs.  All graphing 
is done by graph.php,  all parameters are passed to it in it's url.  You can create 
custom urls to pull custom graphs from your own index pages, or use the canned 
reporting system.

In addition, you should schedule bd_pgsql_purge.sh to run every so often.  I recomend 
running it weekly.  This script outputs sql statements that aggregate the older 
data points in your database in order to reduce the amount of data that needs to
be slogged through in order to generate yearly, monthly, and weekly graphs.

Example:
bd_pgsql_purge.sh | psql bandwidthd postgres

Will connect to the bandwidthd database on local host as the user postgres and summarize 
the data.

