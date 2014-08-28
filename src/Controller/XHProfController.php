<?php

/**
 * @file
 * Contains \Drupal\xhprof\Controller\XHProfController.
 */

namespace Drupal\xhprof\Controller;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\xhprof\XHProfLib\Report\ReportConstants;
use Drupal\xhprof\XHProfLib\Report\ReportEngine;
use Drupal\xhprof\XHProfLib\Report\ReportInterface;
use Drupal\xhprof\XHProfLib\Run;
use Drupal\xhprof\XHProf;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class XHProfController
 */
class XHProfController extends ControllerBase {

  /**
   * @var \Drupal\xhprof\XHProf
   */
  private $xhprof;

  /**
   * @var \Drupal\xhprof\XHProfLib\Report\ReportEngine
   */
  private $reportEngine;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('xhprof.xhprof'),
      $container->get('xhprof.report_engine')
    );
  }

  /**
   * @param \Drupal\xhprof\XHProf $xhprof
   * @param \Drupal\xhprof\XHProfLib\Report\ReportEngine $reportEngine
   */
  public function __construct(XHProf $xhprof, ReportEngine $reportEngine) {
    $this->xhprof = $xhprof;
    $this->reportEngine = $reportEngine;
  }

  /**
   *
   */
  public function runsAction() {
    $runs = $run = $this->xhprof->getStorage()->getRuns();

    // Table attributes
    $attributes = array('id' => 'xhprof-runs-table');

    // Table header
    $header = array();
    $header[] = array('data' => t('View'));
    $header[] = array('data' => t('Path'), 'field' => 'path');
    $header[] = array('data' => t('Date'), 'field' => 'date', 'sort' => 'desc');

    // Table rows
    $rows = array();
    foreach ($runs as $run) {
      $row = array();
      $row[] = $this->l($run['run_id'], 'xhprof.run', array('run' => $run['run_id']));
      $row[] = isset($run['path']) ? $run['path'] : '';
      $row[] = format_date($run['date'], 'small');
      $rows[] = $row;
    }

    $build['table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => $attributes
    );

    return $build;
  }

  /**
   * @param \Drupal\xhprof\XHProfLib\Run $run
   * @param Request $request
   *
   * @return string
   */
  public function runAction(Run $run, Request $request) {
    $length = $request->get('length', 100);
    $sort = $request->get('sort', 'wt');

    $report = $this->reportEngine->getReport(NULL, NULL, $run, NULL, NULL, $sort, NULL, NULL);

    $build['#title'] = $this->t('XHProf view report for %id', array('%id' => $run->getId()));

    $descriptions = ReportConstants::getDescriptions();

    $build['length'] = array(
      '#type' => 'inline_template',
      '#template' => ($length == -1) ? '<h3>Displaying all functions, sorted by {{ sort }}.</h3>' : '<h3>Displaying top {{ length }} functions, sorted by {{ sort }}. [{{ all }}]</h3>',
      '#context' => array(
        'length' => $length,
        'all' => $this->l('show all', 'xhprof.run', array(
            'run' => $run->getId(),
            'length' => -1
          )),
        'sort' => Xss::filter($descriptions[$sort], array()),
      ),
    );

    // TODO: render the overall summary
    //$totals = $report->getTotals();

    $build['table'] = array(
      '#theme' => 'table',
      '#header' => $this->getRunHeader($report),
      '#rows' => $this->getRunRows($report, $length),
      '#attributes' => array('class' => array('responsive')),
      '#attached' => array(
        'library' => array(
          'xhprof/xhprof',
        ),
      ),
    );

    return $build;
  }

  /**
   * @param \Drupal\xhprof\XHProfLib\Run $run1
   * @param \Drupal\xhprof\XHProfLib\Run $run2
   *
   * @return string
   */
  public function diffAction(Run $run1, Run $run2) {
    //drupal_add_css(drupal_get_path('module', 'xhprof') . '/xhprof.css');

    return ''; //xhprof_display_run(array($run1, $run2), $symbol = NULL);
  }

  /**
   * @param \Drupal\xhprof\XHProfLib\Run $run
   * @param $symbol
   *
   * @return string
   */
  public function symbolAction(Run $run, $symbol) {
    //drupal_add_css(drupal_get_path('module', 'xhprof') . '/xhprof.css');

    return ''; //xhprof_display_run(array($run_id), $symbol);
  }

  /**
   * @param string $class
   *
   * @return string
   */
  private function abbrClass($class) {
    $parts = explode('\\', $class);
    $short = array_pop($parts);

    if (strlen($short) >= 40) {
      $short = substr($short, 0, 30) . " … " . substr($short, -5);
    }

    return String::format('<abbr title="@class">@short</abbr>', array('@class' => $class, '@short' => $short));
  }

  /**
   * @param ReportInterface $report
   *
   * @return array
   */
  private function getRunHeader($report) {
    $headers = array('fn', 'ct', 'ct_perc');

    $metrics = $report->getMetrics();

    foreach ($metrics as $metric) {
      $headers[] = $metric;
      $headers[] = $metric . '_perc';
      $headers[] = 'excl_' . $metric;
      $headers[] = 'excl_' . $metric . '_perc';
    }

    $descriptions = ReportConstants::getDescriptions();
    foreach ($headers as &$header) {
      $header = String::format($descriptions[$header]);
    }

    return $headers;
  }

  /**
   * @param ReportInterface $report
   * @param $length
   *
   * @return array
   */
  private function getRunRows($report, $length) {
    $symbols = $report->getSymbols($length);

    foreach ($symbols as &$symbol) {
      $symbol[0] = $this->abbrClass($symbol[0]);
    }

    return $symbols;
  }

}
