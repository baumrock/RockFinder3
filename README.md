![img](logo.svg)

## Combine the power of ProcessWire selectors and SQL

![img](https://i.imgur.com/6FbDwQK.png)

# Preface

## Why this module exists

Initially RockFinder1 was built to feed client side datatables with an array of ProcessWire page data. Loading all pages into memory via a `$pages->find()` query can quickly get inefficient. Querying the database directly via SQL can quickly get very complex on the other hand.

RockFinder is here to help you in such situations and makes finding (or aggregating) data stored in your ProcessWire installation easy, efficient and fun.

Possible use cases:

* Find data for any kind of tabular data (tabulator.info, datatables.net, ag-grid.com).
* Reduce the amount of necessary SQL queries ([see here](https://processwire.com/talk/topic/22205-rockfinder2-combine-the-power-of-pw-selectors-and-sql/?do=findComment&comment=200406)).
* Find data for a CSV or XML export.
* Find data for a REST-API.

## Differences to previous RockFinder modules

* RF3 supports chaining: `$rockfinder->find("template=foo")->addColumns(['foo'])`.
* RF3 fully supports [multi-language](#multi-language).
* RF3 makes it super-easy to [add custom columnTypes](#custom-column-types).
* RF3 makes it easier to use [custom SQL statements](#custom-sql).
* No bloat! The module does just do one thing: Finding data.

## Getting help / Contribute

* If you need help please head over to the PW forum thread and ask your question there: https://processwire.com/talk/topic/23715-rockfinder3-combine-the-power-of-processwire-selectors-and-sql/
* If you found an issue/bug please report it on [GitHub](https://github.com/baumrock/RockFinder3/issues).
* If you can help to improve RockFinder I'm happy to accept [Pull Requests](https://github.com/baumrock/RockFinder3/pulls).

## Example Snippets

TracyDebugger is not necessary for using RockFinder3 but it is recommended. All examples in this readme show dumps of RockFinder instances using the Tracy Console.

**Special thanks to Adrian once more for the brilliant TracyDebugger and the quick help for adding dumping support to the Tracy Console! This was tremendously helpful for developing this module and also for writing these docs.**

![img](hr.svg)

# Basic Concept

The concept of RockFinder is to get the base query of the `$pages->find()` call and modify it for our needs so that we get the best of both worlds: Easy PW selectors and powerful and efficient SQL operations.

In PW every find operation is turned into a `DatabaseQuerySelect` object. This class is great for working with SQL via PHP because you can easily modify the query at any time without complex string concatenation operations:

![img](https://i.imgur.com/iwI7gGB.png)

This is the magic behind RockFinder3! It provides an easy to use API to modify that base query and then fires one efficient SQL query and gets an array of stdClass objects as result.

![img](hr.svg)

# Installation

Install the RockFinder3Master module. The master module is an autoload module that adds a new variable `$rockfinder` to the PW API and also installs the `RockFinder3` module that is responsible for all the finding stuff.

![img](hr.svg)

# Usage

In the most basic setup the only thing you need to provide to a RockFinder is a regular PW selector via the `find()` method:

```php
// either via the API variable
$rockfinder->find("template=foo");

// or via a modules call
$modules->get('RockFinder3')->find("template=foo");
```

![img](hr.svg)

# Adding columns

You'll most likely don't need only ids, so there is the `addColumns()` method for adding additional columns:

```php
$rockfinder
  ->find("template=admin, limit=3")
  ->addColumns(['title', 'created']);
```

![img](https://i.imgur.com/k0gHwXW.png)

This makes it possible to easily add any field data of the requested page. If you want to add all fields of a template to the finder there is a shortcut:

```php
$rockfinder
  ->find("template=foo")
  ->addColumnsFromTemplate("foo");
```

![img](hr.svg)

# Getting data

When using a regular `$pages->find()` you get a `PageArray` as result. When working with RockFinder we don't want to get the PageArray to be more efficient. We usually want plain PHP arrays that we can then use in our PHP code or that we can send to other libraries as data source (for example as rows for a table library).

## getRows()

This returns an array of the result having the `id` as key for every array item:

```php
$finder = $rockfinder
  ->find("template=cat")
  ->addColumns(['title', 'owner']);
$rows = $finder->getRows();
db($rows);
```

![img](https://i.imgur.com/leSeVhf.png)

Having the `id` as item key can be very handy and efficient to get one single array item via its id, eg `db($rows[1071])`:

![img](https://i.imgur.com/zsFBR23.png)

## getRowArray()

Sometimes having custom ids as array item keys is a drawback, though. For example tabulator needs a plain PHP array with auto-increment keys. In such cases you can use `getRowArray()`:

![img](https://i.imgur.com/eyclrNb.png)

![img](hr.svg)

# Dumping data

For small finders Tracy's `dump()` feature is enough, but if you have more complex finders or you have thousands of pages this might get really inconvenient. That's why RockFinder3 ships with a custom `dump()` method that works in the tracy console and turns the result of the finder into a paginated table (using tabulator.info).

For all the dumping methods you can provide two parameters:

1. The title of the dump*
1. The settings for the rendered tabulator table

*Note that if you set the title to TRUE the method will not only dump the tabulator but also the current RockFinder3 object (see the next example).

## dump() or d()

```php
$rockfinder
  ->find("id>0")
  ->addColumns(['title', 'created'])
  ->dump(true);
```

![img](https://i.imgur.com/MbIA7fZ.png)

## barDump() or bd()

For situations where you are not working in the console but maybe in a template file or a module the `barDump()` method might be useful.

![img](https://i.imgur.com/LHhqJk5.png)

## Dumping the SQL of the finder

To understand what is going on it is important to know the SQL that is executed. You can easily dump the SQL query via the `dumpSQL()` or `barDumpSQL()` methods. This even supports chaining:

```php
$rockfinder
  ->find("template=cat")
  ->addColumns(['title'])
  ->dumpSQL()
  ->addColumns(['owner'])
  ->dumpSQL()
  ->dump();
```

![img](https://i.imgur.com/AfUy2OF.png)

![img](hr.svg)

# Renaming columns (column aliases)

Sometimes you have complicated fieldnames like `my_great_module_field_foo` and you just want to get the values of this field as column `foo` in your result:

```php
$rockfinder
  ->find("template=person")
  ->addColumns(['title' => 'Name', 'age' => 'Age in years', 'weight' => 'KG'])
  ->dump();
```

![img](https://i.imgur.com/TIpk3pu.png)

![img](hr.svg)

# Custom column types

You can add custom column types easily. Just place them in a folder and tell RockFinder to scan this directory for columnTypes:

```php
// do this on the master module!
$modules->get('RockFinder3Master')->loadColumnTypes('/your/directory/');
```

See the existing columnTypes as learning examples.

![img](hr.svg)

# Working with options fields

By default RockFinder will query the `data` column in the DB for each requested field. That's fine for lots of fields (like Text or Textarea fields), but for more complex fields this will often just return an ID value instead of the value that we would like to see (like a file name, an option value, etc):

```php
$rockfinder
  ->find("template=cat")
  ->addColumns(['title', 'sex'])
  ->dump();
```

![img](https://i.imgur.com/teIe2va.png)

### Option 1: OptionsValue and OptionsTitle columnTypes

In case of the `Options` Fieldtype we have a `title` and a `value` entry for each option. That's why RockFinder ships with two custom columnTypes that query those values directly from the DB (thanks to a PR from David Karich @RockFinder2). You can even get both values in one single query:

```php
$rockfinder
  ->find("template=cat")
  ->addColumns([
    'title',
    'sex' => 'sex_id',
    'OptionsValue:sex' => 'sex_value',
    'OptionsTitle:sex' => 'sex_title',
  ])
  ->dump();
```

![img](https://i.imgur.com/H4jpr2E.png)

Note that the column aliases are necessary here to prevent duplicate columns with the same name!

### Option 2: Options relation

Option 1 is very handy but also comes with a drawback: It loads all values and all titles into the returned resultset. In the example above this means we'd have around 50x `m`, 50x `f`, 50x `Male` and 50x `female` on 100 rows. Multiply that by the number of rows in your resultset and you get a lot of unnecessary data!

Option 2 lets you save options data in the finder's `getData()->option` property so that you can then work with it at runtime (like via JS in a grid that only renders a subset of the result):

```php
$rockfinder
  ->find("template=cat")
  ->addColumns([
    'title',
    'sex',
  ])
  ->addOptions('sex');
```

![img](https://i.imgur.com/ocF3UJt.png)

```php
$finder->options->sex[2]->value; // f
$finder->options->sex[2]->title; // Female
```

You can also use the helper functions:

```php
$finder->getOptions('sex');
$finder->getOption('sex', 2);
```
![img](https://i.imgur.com/ujyx7gD.png)

![img](hr.svg)

# Multi-Language

Usually data of a field is stored in the `data` db column of the field. On a multi-language setup though, the data is stored in the column for the user's current language, eg `data123`. This makes the queries more complex, because you need to fallback to the default language if the current language's column has no value. RockFinder3 does all that for you behind the scenes and does just return the column value in the users language:

```php
$user->language = $languages->get(1245);
$rockfinder
  ->find("template=cat")
  ->addColumns([
    'title',
    'sex',
  ])
  ->dump();
```

![img](https://i.imgur.com/1R6ukB8.png)

Even setting up new columnTypes is easy! Just use the built in `select` property of the column and it will return the correct SQL query for you:

```php
class Text extends \RockFinder3\Column {
  public function applyTo($finder) {
    $finder->query->leftjoin("`{$this->table}` AS `{$this->tableAlias}` ON `{$this->tableAlias}`.`pages_id` = `pages`.`id`");
    $finder->query->select("{$this->select} AS `{$this->alias}`");
  }
}
```

This will use these values behind the scenes (here for the `title` field):

![img](https://i.imgur.com/gQA22HA.png)

![img](hr.svg)

# Aggregations

Often we need to calculate sums or averages of table data quickly and efficiently. RockFinder3 makes that easy as well:

```php
$avg = $rockfinder->find("template=cat")
    ->addColumn('weight')
    ->getObject("SELECT AVG(weight)");
db($avg);
```

![img](https://i.imgur.com/th6HIMv.png)

```php
$cats = $rockfinder
  ->find("template=cat")
  ->addColumns(['title', 'weight']);
$cats->dump(); // dump finder data to tracy
$obj = $cats->getObject("SELECT SUM(`weight`) AS `total`, COUNT(`id`) AS `cats`, SUM(`weight`)/COUNT(`id`) AS `avg`");
db($obj); // dump result of aggregation
```

![img](https://i.imgur.com/SEdbxXx.png)

What happens behind the scenes is that RockFinder3 gets the current SQL query of the finder and adds that as ` FROM (...sql...) AS tmp` to the query that you provide for aggregation.

This is the resulting SQL query of the example above:

```sql
SELECT SUM(`weight`) AS `total`, COUNT(`id`) AS `cats`, SUM(`weight`)/COUNT(`id`) AS `avg` FROM (
  SELECT
    `pages`.`id` AS `id`,
    `_field_title_605cab16f38ce`.`data` AS `title`,
    `_field_weight_605cab16f3993`.`data` AS `weight`
  FROM `pages`
  LEFT JOIN `field_title` AS `_field_title_605cab16f38ce` ON `_field_title_605cab16f38ce`.`pages_id` = `pages`.`id`
  LEFT JOIN `field_weight` AS `_field_weight_605cab16f3993` ON `_field_weight_605cab16f3993`.`pages_id` = `pages`.`id`
  WHERE (pages.templates_id=44)
  AND (pages.status<1024)
  GROUP BY pages.id
) AS tmp
```

You can even provide a suffix for your query to do things like `GROUP BY` etc:

```php
$rf = $rockfinder->find("template=cat|dog");
$rf->addColumns(['created']);
$rf->dump();
db($rf->getObjects(
  "SELECT COUNT(id) AS `count`, DATE_FORMAT(created, '%Y-%m-%d') AS `date`",
  "GROUP BY DATE_FORMAT(created, '%Y-%m-%d')"
));
```

![img](https://i.imgur.com/QjPfpgM.png)

# Callbacks

RockFinder3 supports row callbacks that are executed on each row of the result. Usage is simple:

## each()

```php
$rockfinder
  ->find("template=cat")
  ->addColumns(['title', 'weight'])
  ->each(function($row) { $row->myTitle = "{$row->title} ({$row->weight} kg)"; })
  ->dump();
```

![img](https://i.imgur.com/qwkOTjG.png)

These callbacks can be a great option, **but keep in mind that they can also be very resource intensive**! That applies even more when you request page objects from within your callback (meaning there will be no benefit at all in using RockFinder compared to a regular `$pages->find()` call).

## addPath()

A special implementation of the `each()` method is the `addPath()` method that will add a path column to your result showing the path of every page. This will **not** load all pages into memory though, because it uses the `$pages->getPath()` method internally.

```php
$rockfinder
  ->find("template=cat")
  ->addColumns(['title', 'weight'])
  ->addPath("de")
  ->dump();
```

![img](https://i.imgur.com/lg2zcWI.png)

If you need the path for linking/redirecting from your data to the pages it might be better to build a custom redirect page that works with the page id, so you don't need the overhead of getting all page paths:

```html
<a href='/your/redirect/url/?id=123'>Open Page 123</a>
```

If you *really* need to access page objects you can get them via the `$finder` parameter of the callback:

```php
$finder->each(function($row, $finder) {
  $row->foo = $finder->pages->get($row->id)->foo;
}
```

![img](hr.svg)

# Joins

What if we had a template `cat` that holds data of the cat, but also references one single owner. And what if we wanted to get a list of all cats including their owners names and age? The owner would be a single page reference field, so the result of this column would be the page id of the owner:

```php
$rockfinder
  ->find("template=cat")
  ->addColumns(['title', 'owner'])
  ->dump();
```

![img](https://i.imgur.com/Y7lgIjb.png)

Joins to the rescue:

```php
$owners = $rockfinder
  ->find("template=person")
  ->addColumns(['title', 'age'])
  ->setName('owner'); // set name of target column
$rockfinder
  ->find("template=cat")
  ->addColumns(['title', 'owner'])
  ->join($owners)
  ->dump();
```

![img](https://i.imgur.com/9JyMKrs.png)

If you don't want to join all columns you can define an array of column names to join. You can also set the `removeID` option to true if you want to remove the column holding the id of the joined data:

```php
->join($owners, ['columns' => ['title'], 'removeID' => true])
```

![img](https://i.imgur.com/zf1imb4.png)

Joins work great on single page reference fields. But what if we had multiple pages referenced in one single page reference field?

![img](hr.svg)

# Relations

Let's take a simple example where we have a page reference field on template `cat` that lets us choose `kittens` for this cat:

![img](https://i.imgur.com/QoKCU7i.png)

This is what happens if we query the field in our finder:

```php
$rockfinder
  ->find("template=cat")
  ->addColumns(['title', 'kittens'])
  ->dump();
```

![img](https://i.imgur.com/JcdULfz.png)

So, how do we get data of those referenced pages? We might want to list the name of the kitten (the `title` field). This could be done in a similar way as we did on the options field above. But what if we also wanted to show other field data of that kitten, like the sex and age? It would get really difficult to show all that informations in one single cell of output!

Relations to the rescue:

```php
// setup kittens finder that can later be added as relation
$kittens = $rockfinder
  ->find("template=kitten")
  ->setName("kittens")
  ->addColumns(['title', 'OptionsTitle:sex', 'age']);

// setup main finder that finds cats
$finder = $rockfinder
  ->find("template=cat,limit=1")
  ->setName("cats")
  ->addColumns(['title', 'kittens'])
  ->addRelation($kittens);

// dump objects
db($finder);
db($finder->relations->first());
```

![img](https://i.imgur.com/IFkxrmW.png)

**NOTE** Look at the result of the `kittens` finder: It returned three rows as result even though we did not define any limit on the initial setup of that finder! That is because RockFinder will automatically return only the rows of the relation that are listed in the column of the main finder!

You can see what happens in the SQL query:

```php
$finder->relations->first()->dumpSQL();
```

```sql
SELECT
  `pages`.`id` AS `id`,
  `_field_title_5eca947b3da27`.`data` AS `title`
FROM `pages`
LEFT JOIN `field_title` AS `_field_title_5eca947b3da27`
  ON `_field_title_5eca947b3da27`.`pages_id` = `pages`.`id`
WHERE (pages.templates_id=51)
AND (pages.status<1024)
AND pages.id IN (258138,258171,258137) /* here is the limit */
GROUP BY pages.id
```

If you need to access those kittens `258138,258171,258137` via PHP you can do this:

```php
$relation = $finder->relations->first();
db($relation->getRowsById("258138,258171,258137"));
db($relation->getRowById(258138));
```

![img](https://i.imgur.com/71UHptF.png)

There's a lot you can do already simply using the RockFinder API, but I promised something about using SQL...

![img](hr.svg)

# Custom SQL

## Option 1: DatabaseQuerySelect

RockFinder3 is heavily based on the `DatabaseQuerySelect` class of ProcessWire. This is an awesome class for building all kinds of SQL `SELECT` statements - from simple to very complex ones. You can access this query object at any time via the `query` property of the finder:

```php
$owners = $rockfinder
  ->find("template=person")
  ->addColumns(['title', 'age', 'weight']);
db($owners->query);
```

![img](https://i.imgur.com/1xCve1R.png)

This means you have full control over your executed SQL command:

```php
$finder = $rockfinder->find(...)->addColumns(...);
$finder->query->select("foo AS foo");
$finder->query->select("bar AS bar");
$finder->query->where("this = that");
```

The only thing you need to take care of is to query the correct tables and columns. This might seem a little hard because many times the names are made unique by a temporary suffix. It's very easy to access these values though:

```php
$owners = $rockfinder
  ->find("template=person")
  ->setName('owner')
  ->addColumns(['title', 'age']);
db($owners->columns->get('age'));
```

![img](https://i.imgur.com/iRnrfPJ.png)

### Example: Group by date

As an example we will create a list of the count of cats and dogs related to their page-creation day. We start with a simple list of all ids of cats and dogs.

Spoiler: There is a shortcut method `groupby()` described in the section about `Predefined methods` below ;)

Also note that since 03/2021 there is another option to get aggregated data from a finder result. In my opinion it is a little easier to read and to learn. See section `Aggregations` above!

```php
$rf = $rockfinder->find("template=cat|dog");
$rf->dumpSQL();
$rf->dump();
```

![img](https://i.imgur.com/G8nZER6.png)

We can't simply add the `created` column because this is a timestamp. We need a formatted date, so we add it as custom SQL:

```php
$rf = $rockfinder->find("template=cat|dog");
$rf->query->select("DATE_FORMAT(pages.created, '%Y-%m-%d') as created");
$rf->dumpSQL();
$rf->dump();
```

![img](https://i.imgur.com/6e0jyVz.png)

Great! Now we need to group the result by the date string:

```php
$rf = $rockfinder->find("template=cat|dog");
$rf->query->select("DATE_FORMAT(pages.created, '%Y-%m-%d') as created");
$rf->query->select("COUNT(id) as cnt");
$rf->query->groupby("DATE_FORMAT(pages.created, '%Y-%m-%d')");
$rf->dumpSQL();
$rf->dump();
```

![img](https://i.imgur.com/7ZmruOD.png)

Wait... That's not what we expected, right? That's because we still have the `pages.id` column in our `SELECT` and `GROUP BY` statement and therefore we end up with all the cats and dogs as unique rows. To get rid of that column we make one important change: Instead of **adding** the `SELECT` and `GROUP BY` statement to the query we **overwrite** them:

```php
$rf = $rockfinder->find("template=cat|dog");
$rf->query->set('select', [
    "DATE_FORMAT(pages.created, '%Y-%m-%d') as created",
    "COUNT(id) as cnt",
]);
$rf->query->set('groupby', [
    "DATE_FORMAT(pages.created, '%Y-%m-%d')",
]);
$rf->dumpSQL();
$rf->dump();
```

![img](https://i.imgur.com/SvkFtCS.png)

Not too complicated, right? You want yearly stats? Easy! Simply change the date format string to `%Y`:

![img](https://i.imgur.com/d11hbCg.png)

## Option 2: SQL String Modification

Another technique is to get the resulting SQL and wrap it around a custom SQL query:

```php
$owners = $rockfinder
  ->find("template=person")
  ->addColumns(['title', 'age', 'weight'])
  ->setName('owner');
$cats = $rockfinder
  ->find("template=cat")
  ->addColumns(['title', 'owner'])
  ->join($owners)
  ->getSQL();
```

Now we have the SQL statement in the `$cats` variable. To get the average age of all owners:

```php
$sql = "SELECT AVG(`owner:age`) FROM ($cats) AS tmp";
db($rockfinder->getObject($sql));
```

![img](https://i.imgur.com/NwqatSv.png)

Get average age of all owners older than 50 years:

```php
$sql = "SELECT
  AVG(`owner:age`) AS `age`,
  `owner:weight` as `weight`
  FROM ($cats) AS tmp
  WHERE `owner:age`>50
";
db($rockfinder->getObject($sql));
```

![img](https://i.imgur.com/05rQ7oQ.png)

Your SQL skills are the limit!

### Predefined Methods

At the moment there is one shortcut using the string modification technique for grouping a result by one column:

```php
$finder = $rockfinder
  ->find("template=cat")
  ->addColumns(['title', 'owner']);
$cats_by_owner = $finder->groupBy('owner', [
  'GROUP_CONCAT(title) as title',
]);
db($cats_by_owner);
```

![img](https://i.imgur.com/I9yl6Zp.png)

Another example could be getting averages:

```php
$finder = $rockfinder
  ->find("template=cat")
  ->addColumns(['title', 'owner', 'weight']);
$cat_weight_by_owner = $finder->groupBy('owner', [
  'AVG(weight) as weight',
]);
db($cat_weight_by_owner);
```

![img](https://i.imgur.com/CY6SdjQ.png)

Of course you can combine both:

```php
$finder = $rockfinder
  ->find("template=cat")
  ->addColumns(['title', 'owner', 'weight']);
$combined = $finder->groupBy('owner', [
  'GROUP_CONCAT(title) as title',
  'AVG(weight) as weight',
]);
db($combined);
```

![img](https://i.imgur.com/sGQWmU3.png)

If you need the SQL statement instead of a PHP array you can set that as one of several options:

```php
$sql = $finder->groupby('foo', [...], ['sql'=>true]);
```

For example you can then JOIN complex queries quite easily:

```php
$foo = $rockfinder->find(...);
$foosql = $foo->getSQL();

$bar = $rockfinder->find(...);
$barsql = $bar->groupby('bar', [...], ['sql'=>true]);

$join = "SELECT
  foo.xx, foo.yy, bar.xx, bar.yy
  FROM ($foosql) AS foo
  LEFT JOIN ($barsql) AS bar ON foo.xx = bar.yy";
db($rockfinder->getObjects($sql));
```

![img](hr.svg)

# Thank you

...for reading thus far and for using RockFinder3. If you find RockFinder3 helpful consider giving it a star on github or [saying thank you](https://www.paypal.me/baumrock). I'm also always happy to get feedback in the PW forum!

**Happy finding :)**
