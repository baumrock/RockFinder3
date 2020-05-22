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
d($RockFinder3->find("template=admin, limit=3"));
```














![img](hr.svg)

# Improvements to RockFinder2

## RockFinder3 supports chaining

```php
db($RockFinder3->find("template=foo")->addColumns(['foo', 'bar'])->getData());
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
