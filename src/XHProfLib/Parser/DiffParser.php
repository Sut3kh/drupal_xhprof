<?php

namespace Drupal\xhprof\XHProfLib\Parser;

/**
 * Class DiffParser
 */
class DiffParser {

  /**
   * @var Parser
   */
  private $parser1;

  /**
   * @var Parser
   */
  private $parser2;

  /**
   * @param $data1
   * @param $data2
   */
  public function __construct($data1, $data2) {
    $this->parser1 = new Parser($data1);
    $this->parser2 = new Parser($data2);
  }

  /**
   * @return mixed
   */
  public function getDiffTotals() {
    $diff_totals[0] = $this->parser1->getTotals();
    $diff_totals[1] = $this->parser2->getTotals();
    $diff_totals['diff'] = array();
    $diff_totals['diff%'] = array();

    foreach ($diff_totals[0] as $metric => $value) {
      $diff_totals['diff'][$metric] = $diff_totals[1][$metric] - $value;
      $diff_totals['diff%'][$metric] = (($diff_totals[1][$metric] / $value) - 1) * 100;
    }

    return $diff_totals;
  }
}
