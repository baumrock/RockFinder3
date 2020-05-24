<?php namespace ProcessWire;
/**
 * Combine the power of ProcessWire selectors and SQL
 *
 * @author Bernhard Baumrock, 22.05.2020
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
class RockFinder3 extends WireData implements Module {

  public $name;

  /** @var DatabaseQuerySelect */
  public $query;

  /** @var RockFinder3Master */
  public $master;

  /**
   * Columns that are added to this finder
   * @var WireArray
   */
  public $columns;

  /**
   * Relations that are added to this finder
   * @var WireArray
   */
  public $relations;

  /**
   * Joins that are added to this finder
   * @var WireArray
   */
  public $joins;

  /**
   * Options that are added to this finder
   * @var WireData
   */
  public $options;

  /** @var array */
  private $rows;

  public $selector;

  /**
   * Reference to the main finder (used by joined finders)
   * @var RockFinder3
   */
  public $main;

  /** @var string */
  private $joinColName;

  /** @var bool */
  private $removeJoinCol;

  /** @var array */
  private $limitRowsTo;

  public static function getModuleInfo() {
    return [
      'title' => 'RockFinder3',
      'version' => '1.0.1',
      'summary' => 'Combine the power of ProcessWire selectors and SQL',
      'autoload' => false,
      'singular' => false,
      'icon' => 'search',
      'requires' => [
        'RockFinder3Master',
      ],
      'installs' => [], // the process module is optional
    ];
  }

  public function __construct() {
    $this->name = uniqid();
    $this->master = $this->modules->get('RockFinder3Master');
    $this->columns = $this->wire(new WireArray);
    $this->relations = $this->wire(new WireArray());
    $this->joins = $this->wire(new WireArray());
    $this->options = $this->wire(new WireData);
  }

  /** ########## CHAINABLE PUBLIC API METHODS ########## */

  /**
   * Add a single column by name
   * @return RockFinder3
   */
  public function addColumn($colname) {
    return $this->addColumns([$colname]);
  }

  /**
   * Add columns to finder
   * @param array $columns
   * @return RockFinder3
   */
  public function addColumns($columns) {
    if(!$this->query) throw new WireException("Setup the selector before calling addColumns()");
    if(!is_array($columns)) throw new WireException("Parameter must be an array");

    // add columns one by one
    foreach($columns as $name=>$value) {
      // skip null value columns
      if($value === null) continue;

      // if key is integer we take the value instead
      if(is_int($name)) {
        $name = $value;
        $alias = null;
      }
      else $alias = $value;

      // if a type is set, get type
      // syntax is type:column, eg addColumns(['mytype:myColumn'])
      $type = null;
      if(strpos($name, ":")) {
        $arr = explode(":", $name);
        $type = $arr[0];
        $name = $arr[1];
      }

      // add this column
      $this->_addColumn($name, $type, $alias);
    }

    return $this;
  }

  /**
   * Add options from field
   * @param array|string $field
   * @return RockFinder3
   */
  public function addOptions($field) {
    if(is_array($field)) {
      foreach($field as $f) $this->addOptions($f);
      return $this;
    }

    $fieldname = (string)$field;
    $field = $this->fields->get($fieldname);
    if(!$field) throw new WireException("Field $fieldname not found");

    $data = [];
    foreach($field->type->getOptions($field) as $option) {
      $opt = $this->wire(new WireData()); /** @var WireData $opt */
      $opt->value = $option->value;
      $opt->title = $option->title;
      $data[$option->id] = $opt;
    }
    $this->options->$fieldname = $data;
    return $this;
  }

  /**
   * Add relation to this finder
   *
   * @param RockFinder3 $relation
   * @param bool $returnAll
   * @return RockFinder3
   */
  public function addRelation($relation, $returnAll = false) {
    if(!$returnAll) $relation->limitRowsTo = $this;
    if(!$this->columns->has($relation->name)) {
      throw new WireException($relation->name . " not found: The name of your relation must exist as column in the main finder");
    }
    $this->relations->add($relation);
    return $this;
  }

  /**
   * Set selector of this finder
   * @param string|array|DatabaseQuerySelect $selector
   * @return RockFinder3
   */
  public function find($selector) {
    $this->selector = $selector;

    // prepare the query selector property
    if($selector instanceof DatabaseQuerySelect) {
      $query = $selector;
    }
    else {
      // get ids of base selector
      $selector = $this->wire(new Selectors($selector));
      $pf = $this->wire(new PageFinder());
      $query = $pf->find($selector, ['returnQuery' => true]);

      // modify the base query to our needs
      // we only need the page id
      // setting the alias via AS is necessary for hideColumns() feature
      $query->set('select', ['`pages`.`id` AS `id`']);
    }

    // save this query object for later
    $this->query = $query;

    // support chaining
    return $this;
  }

  /**
   * Join slave finder to master finder
   *
   * @param RockFinder3 $slave
   * @param array $options
   * @return RockFinder3
   */
  public function join($slave, $options = []) {
    // check if a column with slave name exists
    if(!$this->columns->has($slave->name)) {
      throw new WireException($slave->name . " not found: The name of the finder to be joined must exist as column in the main finder");
    }

    // setup options
    $opt = $this->wire(new WireData()); /** @var WireData $opt */
    $opt->setArray([
      'columns' => null, // null = join all columns, otherwise define an array
      'removeID' => false, // dont remove the column used for the join
    ]);
    $opt->setArray($options);

    // create new join finder
    /** @var RockFinder3 */
    $join = $this->modules->get('RockFinder3');
    $join->find($slave->selector);
    $join->main = $this; // main finder
    $join->joinColName = $slave->name; // colname for join
    $join->removeJoinCol = $opt->removeID; // remove join base column?
    $join->setName("join_{$slave->name}_".uniqid());

    // add columns
    if(!$opt->columns) $opt->columns = $slave->columns;
    foreach($opt->columns as $colname) {
      $col = $slave->columns->get($colname);
      $join->addColumnType($col);
    }

    // apply join to this finder
    $this->joins->add($join);
    $this->applyJoin($join);

    return $this;
  }

  /**
   * Save this finder to the global array of finders
   * The finder can then be joined by other finders etc.
   */
  public function save($name = null) {
    if($name) $this->setName($name);
    $this->master->finders->add($this);
    return $this;
  }

  /**
   * Set name of this finder
   * @return RockFinder3
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /** ########## END CHAINABLE PUBLIC API METHODS ########## */

  /** ########## GET DATA ########## */

  /**
   * Return options by name
   * @return array
   */
  public function getOptions($name) {
    return $this->options->{$name};
  }

  /**
   * Return option object by name and index
   * @return WireData
   */
  public function getOption($name, $index) {
    return $this->getOptions($name)[$index];
  }

  /**
   * Get plain row array ready for tabulator
   * This returns only the array values without page-id-keys
   * otherwise the resulting tabulator array on the client side is invalid.
   * @return array
   */
  public function getRowArray() {
    return array_values($this->getRows());
  }

  /**
   * Get object by its id property
   * @param string|int $id
   * @return stdClass
   */
  public function getRowById($id) {
    return $this->getRows()[(int)$id];
  }

  /**
   * Get rows by id string
   * @param string|array $ids
   * @return array
   */
  public function getRowsById($ids) {
    $rows = [];
    if(is_string($ids)) $ids = explode(",", $ids);
    foreach($ids as $id) $rows[] = $this->getRowById($id);
    return $rows;
  }

  /**
   * Get all rows of this finder
   * @return array
   */
  public function getRows() {
    if($this->rows) return $this->rows;

    // check if a row limit is set for this finder
    // this is the case when a relation is set to return only the subset
    // of rows that are listed in the main finder
    $this->applyRowLimit();

    // now execute the query
    $result = $this->query->execute();
    $rows = $result->fetchAll(\PDO::FETCH_OBJ);
    return $this->rows = $this->master->addRowIds($rows);
  }

  /** ########## END GET DATA ########## */

  /**
   * Add column to finder
   * @param mixed $column
   * @param mixed $type
   * @param mixed $alias
   * @return void
   */
  private function _addColumn($column, $type = null, $alias = null) {
    if(!$type) $type = $this->getType($column);
    if(!$alias) $alias = $column;

    // add this column to columns array
    $colname = (string)$column;
    if($this->columns->has($colname)) {
      // if the column does already exist we append a unique id
      // this can happen when requesting title and value of an options field
      // https://i.imgur.com/woxCx78.png
      $colname .= "_".uniqid();
    }

    // get the column object and apply its changes to the current finder
    $colType = $this->master->columnTypes->get("type=$type");
    if(!$colType) throw new WireException("No column type class for $type");

    $col = $colType->getNew($colname, $alias);
    $this->addColumnType($col);
  }

  /**
   * Add column type to this finder
   * @param \RockFinder3\Column $col
   * @return void
   */
  public function addColumnType($col) {
    $col->applyTo($this);
    $this->columns->add($col);
  }

  /**
   * Apply join to current finder
   * @param RockFinder3 $join
   * @return void
   */
  public function applyJoin($join) {
    $this->query->leftjoin($join->getJoinSQL());
    foreach($join->columns as $col) {
      $this->query->select("GROUP_CONCAT(DISTINCT `{$join->name}`.`{$col->alias}`) AS `{$join->joinColName}:{$col->alias}`");
    }
    if($join->removeJoinCol) {
      $select = $this->query->select;
      foreach($select as $i=>$_select) {
        if(strpos($_select, " AS `{$join->joinColName}`")) unset($select[$i]);
      }
      $this->query->set('select', array_values($select));
    }
  }

  private function applyRowLimit() {
    if(!$this->limitRowsTo) return;

    // get ids that point to that relation
    $ids = [];
    $finder = $this->limitRowsTo;
    $column = $this->name; // colname = name of current relation
    foreach($finder->getRows() as $row) {
      $ids = array_merge($ids, explode(",", $row->$column));
    }

    // now restrict the relation to these ids
    $ids = implode(",", $ids);
    $this->query->where("pages.id IN ($ids)");
  }

  /**
   * Dump this finder to the tracy console
   *
   * Set title to TRUE to dump the finder object.
   *
   * @return void
   */
  public function dump($title = null, $options = null) {
    $settings = $this->wire(new WireData()); /** @var WireData $settings */
    $settings->setArray([
      'layout' => 'fitColumns',
      'autoColumns' => true,
      'pagination' => "local",
      'paginationSize' => 10,
      'paginationSizeSelector' => true,
    ]);
    $settings->setArray($options ?: []);
    $settings = $settings->getArray();
    $settings['data'] = $this->getRowArray();
    $json = json_encode($settings);
    $id = uniqid();

    if($title === true) db($this);
    elseif($title) echo "<h2>$title</h2>";
    echo "<div id='tab_$id'>loading...</div>
    <script>
    if(typeof Tabulator == 'undefined') {
      var link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://unpkg.com/tabulator-tables@4.6.3/dist/css/tabulator.min.css';
      document.getElementsByTagName('head')[0].appendChild(link);
      tracyJSLoader.load('https://unpkg.com/tabulator-tables@4.6.3/dist/js/tabulator.min.js', function() {
        new Tabulator('#tab_$id', $json);
      });
    }
    else new Tabulator('#tab_$id', $json);
    </script>";

    return $this;
  }

  /**
   * Dump SQL of current finder to console
   * @return void
   */
  public function dumpSQL() {
    db($this->getSQL());
    return $this;
  }

  /**
   * Get the type of this column
   *
   * The type is then used for getting the proper data for the column.
   *
   * @param string $column
   * @return string
   */
  public function ___getType($column) {
    // is this column part of the pages table?
    if($this->isBaseColumn($column)) return 'BaseColumn';

    // is it a pw field?
    $field = $this->fields->get($column);
    if($field) {
      // file and image fields
      if($field->type instanceof FieldtypeFile) return 'Multi';
      if($field->type instanceof FieldtypePage) return 'Multi';
      if($field->type instanceof FieldtypeOptions) return 'Multi';

      // by default we take it as text field
      return 'Text';
    }
    else return 'NotFound';
  }

  /**
   * Return current sql query string
   * @return string
   */
  public function getSQL($pretty = true) {
    if(!$this->query) return;

    // make sure that the row limit is applied before returning the sql
    $this->applyRowLimit();

    // return sql
    $sql = $this->query->getQuery();
    return $pretty ? $this->prettify($sql) : $sql;
  }

  /**
   * Get sql for join
   * @return string
   */
  public function getJoinSQL($pretty = true) {
    // the current instance is the slave finder
    // for better readability give it a good name
    $slave = $this;

    // get sql subquery
    // for better readability we add spaces to indent the subquery
    $subquery = str_replace("\n", "\n  ", $slave->getSQL($pretty));

    // get the column of the main finder where the join is based on
    $col = $slave->main->columns->get($slave->joinColName);

    // return the sql query to join the subquery as new table alias
    return "(\n  $subquery\n) AS `{$slave->name}` ON `{$slave->name}`.`id` = `{$col->tableAlias}`.`data`";
  }

  /**
   * Is this column part of the 'pages' db table?
   * @return bool
   */
  private function isBaseColumn($column) {
    return in_array($column, $this->master->baseColumns);
  }

  /**
   * Prettify SQL string
   * @return string
   */
  private function prettify($sql) {
    $str = str_replace("SELECT ", "SELECT\n  ", $sql);
    $str = str_replace("`,", "`,\n  ", $str);

    // undo double breaks on joined sql
    $str = str_replace("`,\n  \n", "`,\n", $str);

    return $str;
  }

  public function __debugInfo() {
    return [
      'name' => $this->name,
      'selector' => $this->selector,
      'columns' => $this->columns,
      'options' => $this->options,
      'relations' => $this->relations,
      'joins' => $this->joins,
      'getRows()' => $this->getRows(),
    ];
  }
}
