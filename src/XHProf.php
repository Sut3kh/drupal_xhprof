<?php

namespace Drupal\xhprof;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\xhprof\XHProfLib\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class XHProf {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var \Drupal\xhprof\XHProfLib\Storage\StorageInterface
   */
  private $storage;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestMatcherInterface
   */
  private $requestMatcher;

  /**
   * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
   */
  private $urlGenerator;

  /**
   * @var string
   */
  private $runId;

  /**
   * @var bool
   */
  private $enabled = FALSE;

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\xhprof\XHProfLib\Storage\StorageInterface $storage
   * @param \Symfony\Component\HttpFoundation\RequestMatcherInterface $requestMatcher
   * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator
   */
  public function __construct(ConfigFactoryInterface $configFactory, StorageInterface $storage, RequestMatcherInterface $requestMatcher, UrlGeneratorInterface $urlGenerator) {
    $this->configFactory = $configFactory;
    $this->storage = $storage;
    $this->requestMatcher = $requestMatcher;
    $this->urlGenerator = $urlGenerator;
  }

  /**
   * Conditionally enable XHProf profiling.
   */
  public function enable() {
    $flags = $this->configFactory->get('xhprof.config')->get('flags');
    $excludeIndirectFunctions = $this->configFactory->get('xhprof.config')->get('exclude_indirect_functions');

    $modifier = 0;
    foreach ($flags as $flag) {
      $modifier += @constant($flag);
    }

    $options = array();
    if ($excludeIndirectFunctions) {
      $options = array(
        'ignored_functions' => array(
          'call_user_func',
          'call_user_func_array'
        )
      );
    }

    xhprof_enable($modifier, $options);

    $this->enabled = TRUE;
  }

  /**
   * Shutdown and disable XHProf profiling.
   * Report is saved with selected storage.
   *
   * @return array
   */
  public function shutdown($runId) {
    $namespace = $this->configFactory->get('system.site')->get('name');
    $xhprof_data = xhprof_disable();
    $this->enabled = FALSE;

    return $this->storage->saveRun($xhprof_data, $namespace, $runId);
  }

  /**
   * Check whether XHProf is enabled.
   *
   * @return boolean
   */
  public function isEnabled() {
    return $this->enabled;
  }

  /**
   * Return true if XHProf profiling can be
   * enabled for the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return bool
   */
  public function canEnable(Request $request) {
    $config = $this->configFactory->get('xhprof.config');

    //if (extension_loaded('xhprof') && $config->get('enabled') && $this->requestMatcher->matches($request)) {
    if (extension_loaded('uprofiler') && $config->get('enabled') && $this->requestMatcher->matches($request)) {
      $interval = $config->get('interval');

      if ($interval && mt_rand(1, $interval) % $interval != 0) {
        return FALSE;
      }

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Generate a link to the report
   * page for a specific run id.
   *
   * @param string $run_id
   * @param string $type
   *
   * @return string
   */
  public function link($run_id, $type = 'link') {
    $url = $this->urlGenerator->generate('xhprof.run', ['run' => $run_id], UrlGeneratorInterface::ABSOLUTE_PATH);

    /*$url = url('admin/reports/xhprof/' . $run_id, array(
      'absolute' => TRUE,
    ));*/
    return $type == 'url' ? $url : l(t('XHProf output'), $url);
  }

  /**
   * Return the current selected
   * storage.
   *
   * @return \Drupal\xhprof\XHProfLib\Storage\StorageInterface
   */
  public function getStorage() {
    return $this->storage;
  }

  /**
   * Return the run id associated
   * with the current request.
   *
   * @return string
   */
  public function getRunId() {
    return $this->runId;
  }

  /**
   * Create a new unique run id.
   *
   * @return string
   */
  public function createRunId() {
    if (!$this->runId) {
      $this->runId = uniqid();
    }

    return $this->runId;
  }

}
