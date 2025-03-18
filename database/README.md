# Database setup

This document assumes you have basic knowledge to set up a mysql, mariadb or postgres database.

If you did not already create a database then do so by starting `psql` as the postgres user and issue the following commands:
```
create role webuser login password '...';
create database web with owner webuser;
```

Of course you can choose a different name for your user and database but here we will assume the database is called web and the user is called webuser. The password for webuser must be filled in the statements above.

Then you create the database table we need. Again as postgres user:
`psql -h localhost -d web -U webuser -W -f php-ddns.sql`

Now copy the file src/database_parameters.inc.example to src/database_parameters.inc and adapt its contents to reflect your database name, username and password.

