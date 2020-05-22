<?php namespace RockFinder3Column;
class BaseColumn extends \RockFinder3\Column {
  public function applyTo($finder) {
    $finder->query->select("`{$this->name}` AS `{$this->alias}`");
  }
}
