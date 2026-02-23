<?php

namespace Drupal\testsuite\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form before clearing out the logs.
 *
 * @internal
 */
class CustomTestsClearLogConfirmForm extends ConfirmFormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new DblogClearLogConfirmForm.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('database')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_tests_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the recent logs?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('testsuite.custom_report');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->getRequest()->getSession()->remove('custom_tests_overview_filter');
    $this->connection->truncate('custom_test_item')->execute();
    $this->messenger()->addStatus($this->t('Database log cleared.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
