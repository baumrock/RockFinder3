<?php namespace RockFinder3;
abstract class Column extends \ProcessWire\Wire {
  public $name;
  public $alias;
  public $type;
  public $table;
  public $tableAlias;

  public function __construct() {
    // set classname as "type" property so we can query the master array:
    // $master->columnTypes->get("type=foo")->getNew();
    $this->type = $this->className;
  }
  
  /**
   * Abstract function that every columnType must implement
   */
  abstract public function applyTo(\ProcessWire\RockFinder3 $finder);

  /**
   * Set the table name for this column
   * @return string
   */
  public function setTable() {
    $this->table = "field_".$this->name;
  }

  /**
   * Set table alias name for this column
   *
   * We prepend the original table name with an underscore
   * to prevent "non unique table alias" errors.
   *
   * @param string $column
   * @return string
   */
  public function setTableAlias() {
    $this->tableAlias = "_{$this->table}_".uniqid();
  }

  /**
   * Get a new instance of this columnType
   * @return Column
   */
  public function getNew($name, $alias) {
    $class = "\RockFinder3Column\\{$this->type}";
    $col = new $class();
    $col->name = $name;
    $col->alias = $alias;
    $col->setTable();
    $col->setTableAlias();
    return $col;
  }

  public function __debugInfo() {
    return [
      'name' => $this->name,
      'alias' => $this->alias,
      'type' => $this->type,
      'table' => $this->table,
      'tableAlias' => $this->tableAlias,
    ];
  }
}
