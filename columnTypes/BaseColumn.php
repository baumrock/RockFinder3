<?php namespace RockFinder3Column;
class BaseColumn extends \RockFinder3\Column {
  public function applyTo($finder) {
    $finder->query->select("`pages`.`{$this->name}` AS `{$this->alias}`");
  }
}
