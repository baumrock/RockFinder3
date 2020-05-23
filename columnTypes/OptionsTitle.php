<?php namespace RockFinder3Column;
/**
 * Column type for getting title of options field
 */
class OptionsTitle extends \RockFinder3\Column {
  public function applyTo($finder) {
    $finder->query->leftjoin("`{$this->table}` AS `{$this->tableAlias}` ON `{$this->tableAlias}`.`pages_id` = `pages`.`id`");
    $finder->query->leftjoin("`fields` AS `_fields_{$this->alias}` ON `_fields_{$this->alias}`.`name` = '{$this->name}'");
    $finder->query->leftjoin("`fieldtype_options` AS `_options_{$this->alias}` ON `_options_{$this->alias}`.`option_id` = `{$this->tableAlias}`.`data` AND `_options_{$this->alias}`.`fields_id` = `_fields_{$this->alias}`.`id`");
    $finder->query->select("GROUP_CONCAT(`_options_{$this->alias}`.`title`) AS `{$this->alias}`");
  }
}
