<?php namespace ProcessWire;
/**
 * Process Module to test RockFinder3 easily via Tracy Console
 *
 * @author Bernhard Baumrock, 22.05.2020
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
class ProcessRockFinder3 extends Process {
  public static function getModuleInfo() {
    return [
      'title' => 'RockFinder3 Tester',
      'version' => '1.0.0',
      'summary' => 'Process Module to test RockFinder3 easily via Tracy Console',
      'icon' => 'search',
      'requires' => [
        'RockFinder3',
        'TracyDebugger',
      ],
      'installs' => [],
      
      // page that you want created to execute this module
      'page' => [
        'name' => 'rockfinder3',
        'parent' => 'setup',
        'title' => 'RockFinder3'
      ],
    ];
  }

  public function init() {
    parent::init(); // always remember to call the parent init
  }

  /**
   * 
   */
  public function execute() {
    $this->headline('RockFinder3 Tester');
    $this->browserTitle('RockFinder3 Tester');
    $nl = "\n";
    $this->config->scripts->add("https://unpkg.com/tabulator-tables@4.6.3/dist/js/tabulator.min.js");
    $this->config->styles->add("https://unpkg.com/tabulator-tables@4.6.3/dist/css/tabulator.min.css");
    return '<strong>Open the tracy console and try this dump:</strong>
      <pre><code>$f = new RockFinder2();'.$nl
      .'$f->find("template=admin");'.$nl
      .'$f->addColumns(["title", "created", "status"]);'.$nl
      .'$f->dump();</code></pre>
      <style>#tracy-debug-panel-ConsolePanel {
        left: 0 !important;
        top: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
      }</style>';
  }
}