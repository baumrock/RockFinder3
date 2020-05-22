<?php namespace RockFinder3Column;
/**
 * Column type for non-existing fields
 */
class NotFound extends \RockFinder3\Column {
  public function applyTo($finder) {
    $finder->query->select("'Field not found' AS `{$this->alias}`");
  }
}
