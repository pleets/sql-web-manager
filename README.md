<p align="center">
<a href="https://scrutinizer-ci.com/g/Pleets/SQLWebManager"><img src="https://img.shields.io/scrutinizer/g/pleets/sqlwebmanager.svg" alt="Code Quality"></a>
<a href="https://scrutinizer-ci.com/g/Pleets/SQLWebManager/build-status/master"><img src="https://scrutinizer-ci.com/g/Pleets/SQLWebManager/badges/build.png?b=master" alt="Build Status"></a>
<a href="https://packagist.org/packages/pleets/sqlwebmanager"><img src="https://poser.pugx.org/pleets/sqlwebmanager/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/pleets/sqlwebmanager"><img src="https://poser.pugx.org/pleets/sqlwebmanager/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/pleets/sqlwebmanager"><img src="https://poser.pugx.org/pleets/sqlwebmanager/license.svg" alt="License"></a>
</p>

# SQL Web Manger
### Minimalist SQL IDE

![img](http://i.imgur.com/VjFQ4it.png)

## About

SQLWebManager is a web application for managing your MySQL, Oracle and SQLServer databases build on PHP.

## System requirements

SQLWebManager Framework requires PHP 5.6 or later; we recommend using the latest PHP version whenever possible.

## Installation

You can install SQLWebManager via composer. A copy of *composer.phar* is given with the lastest version of SQLWebManager. Run the following command in your shell.

```bash
php composer.phar install
```

Go to `install/scripts/` and execute the script mysql.sql, oracle.sql or sqlsever.sql depending of your choice. Then set the database connection on `config/database.config.php`. The following is the schema of the database file.

```php
return [
    'default' => [
       'dbname' => '',
       'dbuser' => '',
       'dbpass' => '',
       'dbhost' => 'localhost',
       'driver' => '',            // database driver
       'dbchar' => 'utf8'
    ],
];
```

Set the following driver depeding your choice.

mysql     -->  Mysqli
oracle    -->  Oci8
sqlserver -->  Sqlsrv

## License

The SQLWebManager IDE is open-source software licensed under the [MIT license](http://opensource.org/licenses/MIT).
