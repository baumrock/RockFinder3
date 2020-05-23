<?php namespace RockFinder3Column;
/**
 * Column type for regular PW fields like text input
 */
class Text extends \RockFinder3\Column {
  public function applyTo($finder) {
    $finder->query->leftjoin("`{$this->table}` AS `{$this->tableAlias}` ON `{$this->tableAlias}`.`pages_id` = `pages`.`id`");
    $finder->query->select("{$this->select} AS `{$this->alias}`");
  }
}
