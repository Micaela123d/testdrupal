<?php

namespace Drupal\user_geo_address\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MapsBlockConfiguration.
 */
class APIConfiguration extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'user_geo_address.apiconfiguration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_geo_address_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('user_geo_address.apiconfiguration');
    $form['google_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Map API Key'),
      '#description' => $this->t('Add API key for Google Map.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('google_api_key'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('user_geo_address.apiconfiguration')
      ->set('google_api_key', $form_state->getValue('google_api_key'))
      ->save();
  }

}
