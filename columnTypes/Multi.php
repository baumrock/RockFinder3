<?php namespace RockFinder3Column;
/**
 * Column type for multi-value fields like options, page reference, etc
 */
class Multi extends \RockFinder3\Column {
  public function applyTo($finder) {
    $finder->query->leftjoin("`{$this->table}` AS `{$this->tableAlias}` ON {$this->tableAlias}.pages_id=pages.id");
    $finder->query->select("GROUP_CONCAT(DISTINCT `{$this->tableAlias}`.data ORDER BY `{$this->tableAlias}`.sort SEPARATOR ',') AS `{$this->alias}`");
  }
}
