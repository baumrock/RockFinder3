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
   * Options that are added to this finder
   * @var WireData
   */
  public $options;

  private $selector;

  /**
   * dataObject cache
   * @var object
   */
  private $dataObject;

  public static function getModuleInfo() {
    return [
      'title' => 'RockFinder3',
      'version' => '1.0.0',
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
    $this->options = $this->wire(new WireData);
  }

  /** ########## CHAINABLE PUBLIC API METHODS ########## */
  
  /**
   * Add columns to finder
   * @param array $columns
   */
  public function addColumns($columns) {
    if(!$this->query) throw new WireException("Setup the selector before calling addColumns()");
    if(!is_array($columns)) throw new WireException("Parameter must be an array");

    // add columns one by one
    foreach($columns as $k=>$v) {
      // skip null value columns
      if($v === null) continue;

      // if key is integer we take the value instead
      if(is_int($k)) {
        $k = $v;
        $v = null;
      }

      // setup initial column name
      $column = $k;

      // if a type is set, get type
      // syntax is type:column, eg addColumns(['mytype:myColumn'])
      $type = null;
      if(strpos($column, ":")) {
        $arr = explode(":", $column);
        $type = $arr[0];
        $column = $arr[1];
      }

      // column name alias
      $alias = $v;

      // add this column
      $this->addColumn($column, $type, $alias);
    }

    return $this;
  }
  
  /**
   * Add options from field
   * @param array|string $field
   * @return void
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
   * Get data object of this finder
   * @return object
   */
  public function getData() {
    // if possible return cached data
    if($this->dataObject) return $this->dataObject;

    $mainData = $this->getMainData();
    $this->loadRelationsData($mainData);
    
    $data = new \RockFinder3\FinderData();
    $data->name = $this->name;
    $data->data = $mainData;
    $data->options = $this->options;
    $data->relations = $this->relations;

    $this->dataObject = $data;
    return $data;
  }
  
  /**
   * Get main data from PW selector
   * 
   * If a column index is provided it will return a plain array of values stored
   * in that column.
   * 
   * @param int $columnindex
   * @return array
   */
  public function getMainData($columnindex = null) {
    // if data is already set return it
    if($this->mainData) return $this->mainData;

    // if no query is set return an empty array
    if(!$this->query) return [];

    $result = $this->query->execute();
    if($columnindex === null) return $result->fetchAll(\PDO::FETCH_OBJ);
    else return $result->fetchAll(\PDO::FETCH_COLUMN, $columnindex);
  }

  /**
   * Return options by name
   * @return array
   */
  public function getOptions($name) {
    return $this->getData()->options->{$name};
  }

  /**
   * Return option object by name and index
   * @return WireData
   */
  public function getOption($name, $index) {
    return $this->getOptions($name)[$index];
  }
  
  /**
   * Load data of all relations
   * @param array $maindata
   * @return void
   */
  public function loadRelationsData($maindata) {
    // TODO
  }
  
  /** ########## END GET DATA ########## */

  /**
   * Add column to finder
   * @param mixed $column
   * @param mixed $type
   * @param mixed $alias
   * @return void
   */
  private function addColumn($column, $type = null, $alias = null) {
    if(!$type) $type = $this->getType($column);
    if(!$alias) $alias = $column;
    $query = $this->query;

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
    
    $col = $colType
      ->getNew($colname, $alias)
      ->applyTo($this);

    // add column to array of columns
    $this->columns->add($col);
  }

  /**
   * Dump this finder to the tracy console
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
    $settings['data'] = $this->getData()->data;
    $json = json_encode($settings);

    $id = uniqid();
    $url = $this->pages->get([
      "has_parent" => 2,
      "name" => ProcessRockFinder3::pageName,
    ])->url;
    $link = "<a href='$url'>$url</a>";
    $msg = "Tabulator assets are not loaded, please try again here: $link";

    if($title) echo "<h2>$title</h2>";
    echo "<div id='tab_$id'>loading...</div>
    <script>
    if(typeof Tabulator == 'undefined') $('#tab_$id').html(\"$msg\");
    else new Tabulator('#tab_$id', $json);
    </script>";
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
    $sql = $this->query->getQuery();
    return $pretty ? $this->prettify($sql) : $sql;
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
      'getData()' => $this->getData(),
    ];
  }
}
