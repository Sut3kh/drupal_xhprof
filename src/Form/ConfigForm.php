<?php

namespace Drupal\xhprof\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\xhprof\XHProfLib\Storage\StorageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigForm
 */
class ConfigForm extends ConfigFormBase {

  /**
   * @var \Drupal\xhprof\XHProfLib\Storage\StorageManager
   */
  private $storageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('xhprof.storage_manager')
    );
  }

  /**
   * @param \Drupal\xhprof\XHProfLib\Storage\StorageManager $storageManager
   */
  public function __construct(StorageManager $storageManager) {
    $this->storageManager = $storageManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xhprof_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('xhprof.config');

    $description = extension_loaded('xhprof') ? t('Profile requests with the xhprof php extension.') : '<span class="warning">' . t('You must enable the <a href="!url">xhprof php extension</a> to use this feature.', array('!url' => url('http://techportal.ibuildings.com/2009/12/01/profiling-with-xhprof/'))) . '</span>';
    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable profiling of page views and <a href="!drush">drush</a> requests.', array('!drush' => url('https://github.com/drush-ops/drush'))),
      '#default_value' => $config->get('enabled'),
      '#description' => $description,
      '#disabled' => !extension_loaded('xhprof'),
    );

    $form['settings'] = array(
      '#title' => $this->t('Profiling settings'),
      '#type' => 'details',
      '#open' => TRUE,
      '#states' => array(
        'invisible' => array(
          'input[name="enabled"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['settings']['exclude'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Exclude'),
      '#default_value' => $config->get('exclude'),
      '#description' => $this->t('Path to exclude for profiling. One path per line.')
    );

    $form['settings']['interval'] = array(
      '#type' => 'number',
      '#title' => 'Profiling interval',
      '#min' => 0,
      '#default_value' => $config->get('interval'),
      '#description' => $this->t('The approximate number of requests between XHProf samples. Leave zero to profile all requests.'),
    );

    $flags = array(
      'XHPROF_FLAGS_CPU' => $this->t('Cpu'),
      'XHPROF_FLAGS_MEMORY' => $this->t('Memory'),
      'XHPROF_FLAGS_NO_BUILTINS' => $this->t('Exclude PHP builtin functions'),
    );
    $form['settings']['flags'] = array(
      '#type' => 'checkboxes',
      '#title' => 'Profile',
      '#options' => $flags,
      '#default_value' => $config->get('flags'),
      '#description' => $this->t('Flags to choose what profile.'),
    );

    $form['settings']['exclude_indirect_functions'] = array(
      '#type' => 'checkbox',
      '#title' => 'Exclude indirect functions',
      '#default_value' => $config->get('exclude_indirect_functions'),
      '#description' => $this->t('Exclude functions like %call_user_func and %call_user_func_array.', array(
          '%call_user_func' => 'call_user_func',
          '%call_user_func_array' => 'call_user_func_array'
        )),
    );

    $options = $this->storageManager->getStorages();
    $form['settings']['storage'] = array(
      '#type' => 'radios',
      '#title' => $this->t('XHProf storage'),
      '#default_value' => $config->get('storage'),
      '#options' => $options,
      '#description' => $this->t('Choose the XHProf storage class.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('xhprof.config')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('exclude', $form_state->getValue('exclude'))
      ->set('interval', $form_state->getValue('interval'))
      ->set('storage', $form_state->getValue('storage'))
      ->set('flags', $form_state->getValue('flags'))
      ->set('exclude_indirect_functions', $form_state->getValue('exclude_indirect_functions'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
