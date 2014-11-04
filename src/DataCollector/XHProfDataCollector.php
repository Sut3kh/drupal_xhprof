<?php

namespace Drupal\xhprof\DataCollector;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webprofiler\DataCollector\DrupalDataCollectorTrait;
use Drupal\webprofiler\DrupalDataCollectorInterface;
use Drupal\xhprof\ProfilerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class XHProfDataCollector extends DataCollector implements DrupalDataCollectorInterface {

  use StringTranslationTrait, DrupalDataCollectorTrait;

  /**
   * @var \Drupal\xhprof\ProfilerInterface
   */
  private $profiler;

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
   * {@inheritdoc}
   */
  public function getName() {
    return 'xhprof';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('Assets');
  }

  /**
   * {@inheritdoc}
   */
  public function hasPanel() {
    return FALSE;
  }

}
