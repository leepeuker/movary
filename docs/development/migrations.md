For the management of migrations, Movary uses [phinx](https://phinx.org/) to do so. 

In order to create a new migration, run the following command:

```php
php vendor/bin/phinx create MyNewMigration -c ./settings/phinx.php
```

Make sure to use CamelCase, so the first character of your migration name has to be capitalized and preferrably any words are capitalized as well. So in the name `my new migration`, the words begin with a capital letter and the spaces are removed.

The `-c` parameter tells Phinx to use the configurations that are in `phinx.php`. To create a migration for MySQL, you'll have to update your `.env` file to `DATABASE_MODE=mysql` and set up the appropriate configuration (such as password, host, etc.) to connect to your MySQL database.

To make a migration for SQlite3, you can update your `.env` file to `DATABASE_MODE=sqlite`.