<?php

/**
 * @file
 * Contains \Drupal\purge_purger_varnishpoc\Form\VarnishTagConfigForm.
 */

namespace Drupal\purge_purger_varnishpoc\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\purge_ui\Form\PurgerConfigFormBase;
use Drupal\purge_purger_varnishpoc\Entity\VarnishTagPurgerSettings;

/**
 * Configuration elements for the Varnish cache tags purger.
 */
class VarnishTagConfigForm extends PurgerConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purge_purger_varnishpoc.configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = VarnishTagPurgerSettings::load($this->getId($form_state));

     // HTTP Settings.
    $form['http_settings'] = [
      '#title' => $this->t('HTTP Settings'),
      '#description' => $this->t('Configure how custom outbound HTTP requests should be formed.'),
      '#type' => 'details',
      '#open' => TRUE,
    ];
    // @TODO: url needs to be split out into hostname, port, path, etc.
    $form['http_settings']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Varnish URL'),
      '#default_value' => $settings->url,
      '#required' => TRUE,
      '#description' => $this->t('The URL of the Varnish instance to send <code>BAN</code> requests to.')
    ];
    $form['http_settings']['header'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header'),
      '#default_value' => $settings->header,
      '#required' => TRUE,
      '#description' => $this->t('The outbound HTTP header that identifies the tag to be purged.')
    ];

    // Performance.
    $form['performance'] = [
      '#title' => $this->t('Performance'),
      '#description' => $this->t('Tune HTTP-based processing and ensure a good trade off between capacity and system stability.'),
      '#type' => 'details',
      '#open' => TRUE,
    ];
    $form['performance']['timeout'] = [
      '#type' => 'number',
      '#step' => 0.1,
      '#min' => 1,
      '#title' => $this->t('Timeout'),
      '#default_value' => $settings->timeout,
      '#required' => TRUE,
      '#description' => $this->t('Float describing the timeout of the request in seconds.')
    ];
    $form['performance']['connect_timeout'] = [
      '#type' => 'number',
      '#step' => 0.1,
      '#min' => 0.5,
      '#title' => $this->t('Connection timeout'),
      '#default_value' => $settings->connect_timeout,
      '#required' => TRUE,
      '#description' => $this->t('Float describing the number of seconds to wait while trying to connect to a server.')
    ];
    $form['performance']['max_requests'] = [
      '#type' => 'number',
      '#step' => 1,
      '#min' => 1,
      '#max' => 500,
      '#title' => $this->t('Maximum requests'),
      '#default_value' => $settings->max_requests,
      '#required' => TRUE,
      '#description' => $this->t('Maximum number of HTTP requests that can be made during the runtime of one request (including CLI). The higher this number is set, the more - CLI based - scripts can process but this can also badly influence your end-user performance when using runtime-based queue processors.')
    ];
    $form['performance']['execution_time_consumption'] = [
      '#type' => 'number',
      '#step' => 0.01,
      '#min' => 0.20,
      '#max' => 0.90,
      '#field_suffix' => '%',
      '#title' => $this->t('Execution time consumption'),
      '#default_value' => $settings->execution_time_consumption,
      '#required' => TRUE,
      '#description' => $this->t("Percentage of PHP's maximum execution time that can be allocated to processing. When PHP's setting is set to 0 (e.g. on CLI), the max requests setting will be used for capacity limiting. Whenever you notice Drupal requests timing out, lower this percentage.")
    ];

    // @todo Implement repeatable row options to configure *when* invalidations
    // are considered successful or not, for instance a option that says "when
    // HTTP response code: 200, 404".
    $form['success_conditions'] = [
      '#title' => $this->t('Success conditions'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = VarnishTagPurgerSettings::load($this->getId($form_state));
    $settings->url = $form_state->getValue('url');
    $settings->header = $form_state->getValue('header');
    $settings->timeout = $form_state->getValue('timeout');
    $settings->connect_timeout = $form_state->getValue('connect_timeout');
    $settings->max_requests = $form_state->getValue('max_requests');
    $settings->execution_time_consumption = $form_state->getValue('execution_time_consumption');
    $settings->save();
    return parent::submitForm($form, $form_state);
  }

}