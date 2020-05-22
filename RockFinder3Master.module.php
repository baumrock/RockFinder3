<?php namespace ProcessWire;
/**
 * RockFinder3 Master module
 *
 * @author Bernhard Baumrock, 22.05.2020
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
class RockFinder3Master extends WireData implements Module {

  /** @var WireArray */
  public $finders;

  public static function getModuleInfo() {
    return [
      'title' => 'RockFinder3Master',
      'version' => '1.0.0',
      'summary' => 'Master Instance of RockFinder3 that is attached as PW API Variable',
      'autoload' => 9000,
      'singular' => true,
      'icon' => 'search',
      'requires' => [],
      'installs' => [
        'RockFinder3',
      ],
    ];
  }

  public function init() {
    $this->wire('RockFinder3', $this);
    $this->finders = $this->wire(new WireArray());
  }

  /**
   * Return a new RockFinder3
   */
  public function find($selector) {
    /** @var RockFinder3 */
    $finder = $this->modules->get('RockFinder3');
    return $finder->find($selector);
  }

  /**
   * Uninstall actions
   */
  public function ___uninstall() {
    // we do remove both modules manually here
    // the process module can not be listed in getModuleInfo because it is optional
    // if the process module is installed, it causes RockFinder3 to re-install
    // that's why we remove it again here to make sure it is uninstalled
    $this->modules->uninstall('ProcessRockFinder3');
    $this->modules->uninstall('RockFinder3');
  }

  public function __debugInfo() {
    return [
      'finders' => $this->finders,
    ];
  }
}
