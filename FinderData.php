<?php namespace RockFinder3;
class FinderData extends \ProcessWire\Wire {
  public $name;
  public $data;

  public function __debugInfo() {
    return [
      'name' => $this->name,
      'data' => $this->data,
    ];
  }
}
