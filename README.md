# Open List

Version 2.x.1.0

SOAP/REST service handling user list elements in Danish libraries.

Each library may implement an open_list client on their website and thereby share certain user data between all library websites using the Open List service.

## Server Requirements
  * PHP 5.3.3+
  * Mysql 5.7+
  * Zend Engine 2.3+ (For WDSL auto discover)

## Install

    Create MySql database for the service
    Run sql/openlist.sql into the db
    Copy settings_default.php to settings.php
    Create a virtual host with document root www/ and index.php

## API docs
  http://test.openlist.ddbcms.dk/doc/classes/OpenList.html

## Example implementation Ding2 / DDBCMS client
  https://github.com/ding2/ding2/tree/master/modules/p2/ting_openlist

## Quick start

OpenList owner ids are SHA256 hashes of local user ids salted with a prefix of choise

```
  // Create a new list of some user
  createList("9f74d..", "My test list", $type = 'test');

  // Insert an element of data into that list. $list_id is returned from create list
  createElement($list_id, "My test data element"); 
  
  // Fetch a list of all lists from a certain owner
  getLists("9f74d..")

  // Or for sync purposes add a unix timestamp since last sync
  getLists("9f74d..",1499335887)
```