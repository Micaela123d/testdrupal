<?php

namespace Drupal\phpunit_tests\Form;

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
class PhpunitTestsDeleteGroupConfirmForm extends ConfirmFormBase {

  /**
   * ID of the item to delete.
   *
   * @var int
   */
  protected $id;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new PhpunitClearLogConfirmForm.
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
    return 'phpunit_tests_delete_group_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this group and all of the group items associated with it?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('phpunit_tests.group_report');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $this->id = $id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->connection->delete('phpunit_test_group_item')
      ->condition('phpunit_test_group_id', $this->id)
      ->execute();
    $this->connection->delete('phpunit_test_group')
      ->condition('id', $this->id)
      ->execute();
    $this->messenger()->addStatus($this->t('Group deleted.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
