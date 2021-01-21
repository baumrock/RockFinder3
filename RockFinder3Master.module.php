<?php namespace ProcessWire;
/**
 * RockFinder3 Master module
 *
 * @author Bernhard Baumrock, 22.05.2020
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
require("Column.php");
require("FinderData.php");
class RockFinder3Master extends WireData implements Module {

  /** @var WireArray */
  public $finders;

  /** @var array */
  public $baseColumns;

  /** @var WireArray */
  public $columnTypes;

  public static function getModuleInfo() {
    return [
      'title' => 'RockFinder3Master',
      'version' => '1.0.7',
      'summary' => 'Master Instance of RockFinder3 that is attached as PW API Variable',
      'autoload' => 9000,
      'singular' => true,
      'icon' => 'search',
      'requires' => [],
      'installs' => [
        'RockFinder3',
      ],
    ];
  }

  public function init() {
    $this->wire('rockfinder', $this);
    $this->finders = $this->wire(new WireArray());
    $this->columnTypes = $this->wire(new WireArray());
    $this->getBaseColumns();
    $this->loadColumnTypes(__DIR__."/columnTypes");
  }

  /**
   * Load all column type definitions in given directory
   * @return void
   */
  public function loadColumnTypes($dir) {
    $dir = Paths::normalizeSeparators($dir);
    foreach($this->files->find($dir, ['extensions'=>['php']]) as $file) {
      $file = $this->info($file);
      // try to load the columnType class
      try {
        require_once($file->path);
        $class = "\RockFinder3Column\\{$file->filename}";
        $colType = new $class();
        $colType->type = $file->filename;
        $this->columnTypes->add($colType);
      } catch (\Throwable $th) {
        $this->error($th->getMessage());
      }
    }
  }

  /**
   * Get info for given file
   * @return WireData
   */
  public function info($file) {
    $info = $this->wire(new WireData()); /** @var WireData $info */
    $info->setArray(pathinfo($file));
    $info->path = $file;
    return $info;
  }

  /**
   * Get the columns that are part of the 'pages' db table
   * Those columns need to be treaded differently in queries.
   * @return array
   */
  public function getBaseColumns() {
    $db = $this->config->dbName;
    $result = $this->database->query("SELECT `COLUMN_NAME`
      FROM `INFORMATION_SCHEMA`.`COLUMNS`
      WHERE `TABLE_SCHEMA`='$db'
      AND `TABLE_NAME`='pages';");
    return $this->baseColumns = $result->fetchAll(\PDO::FETCH_COLUMN);
  }

  /**
   * Return a new RockFinder3
   *
   * This makes it easy to get new finders via the API variable:
   * $finder = $rockfinder->find("template=foo")->addColumns(...);
   *
   * @return RockFinder3
   */
  public function find($selector) {
    /** @var RockFinder3 */
    $finder = $this->modules->get('RockFinder3');
    return $finder->find($selector);
  }

  /**
   * Get first row of resultset as stdClass object
   * @return object
   */
  public function getObject($sql) {
    return $this->getObjects($sql)[0];
  }

  /**
   * Execute sql query and return result as array of stdClass objects
   * @return array
   */
  public function getObjects($sql) {
    $result = $this->database->query($sql);
    return $this->addRowIds($result->fetchAll(\PDO::FETCH_OBJ));
  }

  /**
   * Set array keys to id property of objects if one exists
   * This makes it possible to access rows by their id key
   * @return array
   */
  public function addRowIds($_rows) {
    $rows = [];
    foreach($_rows as $row) {
      if(!property_exists($row, "id")) return $_rows;
      $rows[(int)$row->id] = $row;
    }
    return $rows;
  }

  public function __debugInfo() {
    return [
      'finders' => $this->finders,
      'baseColumns' => $this->baseColumns,
    ];
  }
}
