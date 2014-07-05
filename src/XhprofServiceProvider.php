<?php

namespace Drupal\xhprof;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\xhprof\Compiler\StoragePass;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Defines a service profiler for the xhprof module.
 */
class XhprofServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->addCompilerPass(new StoragePass());

    if (FALSE !== $container->hasDefinition('profiler')) {
      $container->register('xhprof.webprofiler', 'Drupal\xhprof\DataCollector\XHProfDataCollector')
        ->addArgument(new Reference(('xhprof.xhprof')))
        ->addTag('data_collector', array(
          'template' => '@xhprof/Collector/xhprof.html.twig',
          'id' => 'xhprof',
          'title' => 'XHProf',
          'priority' => 50
        ));
    }
  }

}
