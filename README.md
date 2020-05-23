# RockFinder3

Combine the power of ProcessWire selectors and SQL

# Preface

### Why this module exists

The basic concept of RockFinder is to make it easy to get large numbers of data while keeping the number of SQL queries and consumed memory as low as possible. Regular `$pages->find()` queries do load all found pages into memory which can be a no-go. We have `$pages->findMany()` that theoretically lets you load thousands of pages, but what if you only wanted to get an average value of some thousand rows of data? Looping them in PHP to get the average value would really not be the best option. Writing custom SQL on the other hand can be quite complex, because you need to understand how PW works under the hood. Field data is stored in separate DB tables that you need to join, you need to take care about access control, page status (unpublished, trashed, hidden, etc) and finally one might have a multilanguage setup...

RockFinder is here to help you in such situations and makes finding (or aggregating) data stored in your ProcessWire installation easy, efficient and hopefully fun.

### Getting help / Contribute

* If you need help please head over to the PW forum thread and ask your question there: // TODO
* If you found an issue/bug please report it on [GitHub](https://github.com/baumrock/RockFinder3/issues).
* If you can help to improve RockFinder I'm happy to accept [Pull Requests](https://github.com/baumrock/RockFinder3/pulls).

### Example Snippets

TracyDebugger is not necessary for using RockFinder3 but it is recommended. All examples in this readme show dumps of RockFinder instances using Tracy. The ProcessModule of RockFinder3 does use Tracy for dumping the results, so TracyDebugger is required for the ProcessModule to run.

If `$finder` is used in the examples it is supposed that you defined that variable before:

```php
// use this
$finder = $modules->get("RockFinder3");
$finder->find("template=foo");

// or this
$finder = $RockFinder3->find("template=foo");
```

![img](hr.svg)

# Basic Concept

The concept of RockFinder is to get the base query of the `$pages->find()` call and modify it for our needs so that we get the best of both worlds: Easy PW selectors and powerful SQL operations.

In PW every find operation is turned into a `DatabaseQuerySelect` object. This class is great for working with SQL via PHP because you can easily modify the query at any time without complex string concatenation operations:

![img](https://i.imgur.com/iwI7gGB.png)

This is the magic behind RockFinder3! It provides an easy to use API to modify that base query and then fires one efficient SQL query and gets an array of stdClass objects as result.

![img](hr.svg)

# Installation

Install the RockFinder3Master module. The master module is an autoload module that adds a new variable `$RockFinder3` to the PW API and also installs the `RockFinder3` module that is responsible for all the finding stuff.

![img](hr.svg)

# Usage

In the most basic setup the only thing you need to provide to a RockFinder is a regular PW selector via the `find()` method:

```php
db($RockFinder3->find("template=admin, limit=3"));
```

## Adding columns

You'll most likely don't need only ids, so there is the `addColumns()` method for adding additional columns:

```php
$finder = $RockFinder3
  ->find("template=admin, limit=3")
  ->addColumns(['title', 'created']);
db($finder);
```

![img](https://i.imgur.com/k0gHwXW.png)

This makes it possible to easily add any field data of the requested page.

## Dumping data

For small finders Tracy's `dump()` feature is enough, but if you have more complex finders or you have thousands of pages this might get really inconvenient. That's why RockFinder3 ships with a custom `dump()` method that works in the tracy console and turns the result of the finder into a paginated table (using tabulator.info):

```php
$finder = $RockFinder3
  ->find("id>0")
  ->addColumns(['title', 'created']);
$finder->dump();
```

![img](https://i.imgur.com/dfHdrG7.png)

## Renaming columns (column aliases)

Sometimes you have complicated fieldnames like `my_great_module_field_foo` and you just want to get the values of this field as column `foo` in your result:

```php
$finder->addColumns(['my_great_module_field_foo' => 'foo']);
```

## Custom column types

You can add custom column types easily. Just place them in a folder and tell RockFinder to scan this directory for columnTypes:

```php
$modules->get('RockFinder3Master')->loadColumnTypes('/your/directory/');
```

See the existing columnTypes as learning examples.

## Working with options fields

By default RockFinder will query the `data` column in the DB for each requested field. That's fine for lots of fields (like Text or Textarea fields), but for more complex fields this will often just return an ID value instead of the value that we would like to see (like a file name, an option value, etc):

```php
$RockFinder3
  ->find("template=cat")
  ->addColumns(['title', 'sex'])
  ->dump();
```

![img](https://i.imgur.com/teIe2va.png)

### Option 1: OptionsValue and OptionsTitle columnTypes

In case of the `Options` Fieldtype we have a `title` and a `value` entry for each option. That's why RockFinder ships with two custom columnTypes that query those values directly from the DB (thanks to a PR from David Karich @RockFinder2). You can even get both values in one single query:

```php
$RockFinder3
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
$finder = $RockFinder3
  ->find("template=cat")
  ->addColumns([
    'title',
    'sex',
  ])
  ->addOptions('sex');
db($finder->getData());
```

![img](https://i.imgur.com/3tFUwFk.png)

```php
$data = $finder->getData();
$data->options->sex[2]->value; // f
$data->options->sex[2]->title; // Female
```

You can also use the helper functions:

```php
$finder->getOptions('sex');
$finder->getOption('sex', 2);
```
![img](https://i.imgur.com/ujyx7gD.png)

![img](hr.svg)

# Multi-Language


![img](hr.svg)

# Joins



![img](hr.svg)

# Differences to RockFinder 1 and 2

## RockFinder1

* Had a custom implementation for querying the DB.

## RockFinder2

* Already worked based on the internal DB query selector engine.


RockFinder2 has an API to be queried via www.example.com/your-secred-rockfinder-url

This should really not be part of the rockfinder module! If there is a need for such a feature we can put it in a separate module or use RockFinder2 instead.

## RockFinder3 supports chaining

```php
db($RockFinder3->find("template=foo")->addColumns(['foo', 'bar'])->getData());
```

## RockFinder3 has a columns property

All columns added to the finder are available as `$finder->columns`.

![img](https://i.imgur.com/9DUqXbG.png)

This is a WireArray so you can easily get your desired column via PW magic:

```php
$finder->columns->find("type=BaseColumn");
$finder->columns->get("myFooColumn"); // using the name property
```










The usage is very similar to a regular `$pages->find()` query, but the returned result is very different:

* The result is not a PageArray but an instance of RockFinder3
* The data array is a regular PHP array of plain PHP objects (not PW Pages)
* By default a find() operation does only return IDs of the found pages

**Note:** By default the `find()` will sort the result according to the internal PW `$pages->find()` call. On large datasets of thousands of pages this sorting can make the query slow, which is bad if the sort order does not matter (eg because the result is sorted on the client side, like in a JS-datagrid).

```php
$finder->find("template=admin, limit=3", ['nosort'=>true]);
```

# Thank you

...for reading the docs and using RockFinder3. If you find RockFinder3 helpful consider giving it a star on github or [buying me a drink](https://www.paypal.me/baumrock). I'm also always happy to get feedback in the PW forum!

Happy finding!
