<?php

namespace Drupal\xhprof\DataCollector;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webprofiler\DataCollector\DrupalDataCollectorTrait;
use Drupal\webprofiler\DrupalDataCollectorInterface;
use Drupal\xhprof\ProfilerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Class XHProfDataCollector
 */
class XHProfDataCollector extends DataCollector implements DrupalDataCollectorInterface {

  use StringTranslationTrait, DrupalDataCollectorTrait;

  /**
   * @var \Drupal\xhprof\ProfilerInterface
   */
  private $profiler;

  /**
   * @var array
   */
  private $summary;

  /**
   * @var array
   */
  private $possibileMetrics;

  /**
   * @param \Drupal\xhprof\ProfilerInterface $profiler
   */
  public function __construct(ProfilerInterface $profiler) {
    $this->profiler = $profiler;
  }

  /**
   * {@inheritdoc}
   */
  public function collect(Request $request, Response $response, \Exception $exception = NULL) {
    $this->data['run_id'] = $this->profiler->getRunId();
  }

  /**
   * @return string
   */
  public function getRunId() {
    return $this->data['run_id'];
  }

  /**
   * @return bool
   */
  public function getShowSummaryData() {
    return \Drupal::config('xhprof.config')->get('show_summary_toolbar');
  }

  /**
   * @return string
   */
  public function getCalls() {
    return $this->getMetric($this->data['run_id'], 'ct');
  }

  /**
   * @return string
   */
  public function getWt() {
    return $this->getMetric($this->data['run_id'], 'wt');
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'xhprof';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('XHProf');
  }

  /**
   * {@inheritdoc}
   */
  public function hasPanel() {
    return FALSE;
  }

  /**
   * @param int $runId
   *
   * @return array
   */
  private function getMetric($runId, $metric) {
    if (!isset($this->summary)) {
      /** @var \Drupal\xhprof\ProfilerInterface $profiler */
      $profiler = \Drupal::service('xhprof.profiler');

      /** @var \Drupal\xhprof\XHProfLib\Run $run */
      $run = $profiler->getRun($runId);

      /** @var \Drupal\xhprof\XHProfLib\Report\ReportEngine $reportEngine */
      $reportEngine = \Drupal::service('xhprof.report_engine');
      $report = $reportEngine->getReport(NULL, NULL, $run, NULL, NULL);

      $this->summary = $report->getSummary();
      $this->possibileMetrics = $report->getPossibleMetrics();
    }

    $unit = isset($this->possibileMetrics[$metric]) ? $this->possibileMetrics[$metric][1] : '';

    return SafeMarkup::format('@value @unit', array(
      '@value' => $this->summary[$metric],
      '@unit' => $unit
    ));
  }
}
