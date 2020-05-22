<?php namespace RockFinder3Column;
/**
 * Column type for PW range slider
 */
class Range extends \RockFinder3\Column {
  public function applyTo($finder) {
    $finder->query->leftjoin("`{$this->table}` AS `{$this->tableAlias}` ON {$this->tableAlias}.`pages_id` = `pages`.`id`");
    $finder->query->select("CONCAT(`{$this->tableAlias}`.`data`, ',', `{$this->tableAlias}`.`data_max`) AS `{$this->alias}`");
  }
}
