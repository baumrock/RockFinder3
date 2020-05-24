<?php namespace RockFinder3;
abstract class Column extends \ProcessWire\Wire {
  public $name;
  public $alias;
  public $type;
  public $table;
  public $tableAlias;
  public $select;

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
   * Get method to support $columns->each('name') etc.
   */
  public function get($key) {
    return $this->$key;
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
    $col->setSelect();
    return $col;
  }

  /**
   * Set the data-column that should be selected via SQL
   * 
   * This is usually `table`.`data` but for multilang it is `table`.`data123`
   * If the multilang column is empty it should return the default's lang value
   * so it gets even more complicated. This method handles all that and can
   * simply be used in columTypes via $query->select("{$this->select} AS `{$this->alias}`);
   * @return void
   */
  public function setSelect() {
    $lang = $this->user->language;
    $type = $this->fields->get($this->name)->type;
    $this->select = "`{$this->tableAlias}`.`data`";

    // early exit if user has default language
    if($lang->isDefault) return;

    // early exit if field is single-language
    if(!$type instanceof \ProcessWire\FieldtypeLanguageInterface) return;

    // multi-lang field
    $this->select = "COALESCE(NULLIF(`{$this->tableAlias}`.`data{$lang->id}`, ''), `{$this->tableAlias}`.`data`)";
  }

  public function __debugInfo() {
    return [
      'name' => $this->name,
      'alias' => $this->alias,
      'type' => $this->type,
      'table' => $this->table,
      'tableAlias' => $this->tableAlias,
      'select' => $this->select,
    ];
  }
}
