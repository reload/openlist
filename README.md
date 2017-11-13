# Open List

Version 2.x.1.0

SOAP/REST service handling user list elements in Danish libraries.

Each library may implement an open_list client on their website and thereby share certain user data between all library websites using the Open List service.

## Requirements
  * PHP 5.3.3+
  * Mysql 5.7+
  * [Composer](https://getcomposer.org)

## Manual installation

* Create MySQL database for the service
* Import db/openlist.sql into the DB
* Copy settings.default.php to settings.php and insert valid DB information 
* Copy authkeys.default.php to authkeys.php and insert valid authkeys
* Create a virtual host
* Run composer install
* Create empty file in xml/wsdl.xml
    
## Docker development environment

Stop local running instances of web servers, database servers and DNS servers.

Otherwise you might encounter conflicts.

### Requirements
* [Docker](https://www.docker.com/community-edition)
* [Dory](https://github.com/FreedomBen/dory)
* [Ruby 2.4+](https://www.ruby-lang.org/en/downloads/)

### Get running
* Run `dory up`
* Run `composer install`
* Run `docker-compose up`
* You now have Openlist running at http://openlist.docker

## API docs
  http://test.openlist.ddbcms.dk/doc/classes/OpenList.html

  http://test.openlist.ddbcms.dk/doc

## WSDL in test environment
  http://test.openlist.ddbcms.dk/?wsdl

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
