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
class PhpunitTestsDeleteGroupItemConfirmForm extends ConfirmFormBase {

  /**
   * ID of the group of the item.
   *
   * @var int
   */
  protected $groupid;

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
    return 'phpunit_tests_delete_group_item_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this group item?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('phpunit_tests.group_event', ['event_id' => $this->groupid]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    if ($id) {
      $parts = explode("-", $id);
      $this->groupid = $parts[0];
      $this->id = $parts[1];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->connection->delete('phpunit_test_group_item')
      ->condition('id', $this->id)
      ->execute();
    $this->messenger()->addStatus($this->t('Group item deleted.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
