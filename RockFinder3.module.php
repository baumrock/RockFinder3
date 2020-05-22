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
  }

  /** ########## CHAINABLE PUBLIC API METHODS ########## */
  
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
    
    $data = (object)[];
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
   * Load data of all relations
   * @param array $maindata
   * @return void
   */
  public function loadRelationsData($maindata) {
    // TODO
  }
  
  /** ########## END GET DATA ########## */

  public function __debugInfo() {
    return [
      'name' => $this->name,
      'selector' => $this->selector,
      'getData()' => $this->getData(),
    ];
  }
}
