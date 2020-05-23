<?php namespace RockFinder3;
class FinderData extends \ProcessWire\Wire {
  public $name;
  public $data;
  public $options;
  public $relations;

  public function __debugInfo() {
    return [
      'name' => $this->name,
      'data' => $this->data,
      'options' => $this->options,
      'relations' => $this->relations,
    ];
  }
}
