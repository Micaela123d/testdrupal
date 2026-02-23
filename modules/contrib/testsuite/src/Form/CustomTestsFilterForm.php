<?php

namespace Drupal\testsuite\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\testsuite\BaseTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the database logging filter form.
 *
 * @internal
 */
class CustomTestsFilterForm extends FormBase {
  use BaseTrait;

  /**
   * The array of filters.
   *
   * @var array
   */
  public $filters = [
    'module',
    'test',
  ];

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * CustomTestsFilterForm constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database.
   */
  public function __construct(
    Connection $connection,
  ) {
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
    return 'custom_tests_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $session_filters = $this->getRequest()->getSession()->get('custom_tests_overview_filter', []);

    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter log messages'),
      '#description' => $this->t('Custom tests do not use dev dependencies so they can be ran anywhere. There is a small interface that just ensures that the columns in this table will be filled when you create the test. The return results are up to you. Custom tests can be put in any custom module. For an example of how to make them look at the module in tests_module folder of the testsuite module.'),
      '#open' => TRUE,
      '#attached' => [
        'library' => [
          'testsuite/testsuite.filters',
        ],
      ],
    ];

    $form['filters']['filters-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'testsuite-tests-filter-form-wrapper',
      ],
    ];

    if ($modules = $this->getModules()) {
      $moduleDropdown = $form_state->hasValue('module') ? $form_state->getValue('module') : '';
      $module_dropdown = !empty($session_filters['module']) ? $session_filters['module'] : $moduleDropdown;

      $form['filters']['filters-container']['module-container'] = [
        '#type' => 'container',
      ];
      $form['filters']['filters-container']['module-container']['module'] = [
        '#title' => 'Module',
        '#type' => 'select',
        '#multiple' => FALSE,
        '#size' => 4,
        '#options' => $modules,
        '#default_value' => $module_dropdown,
      ];
    }

    if ($customTests = $this->getTests()) {
      $testDropdown = $form_state->hasValue('test') ? $form_state->getValue('test') : '';
      $test_dropdown = !empty($session_filters['test']) ? $session_filters['test'] : $testDropdown;

      $form['filters']['filters-container']['tests-container'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'testsuite-tests-container',
        ],
      ];
      $form['filters']['filters-container']['tests-container']['test'] = [
        '#title' => 'Tests',
        '#type' => 'select',
        '#multiple' => TRUE,
        '#size' => 4,
        '#options' => $customTests,
        '#default_value' => $test_dropdown,
      ];
    }

    $form['filters']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];
    if (!empty($session_filters)) {
      $form['filters']['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => [],
        '#submit' => ['::resetForm'],
      ];
    }

    return $form;
  }

  /**
   * Gathers a list of uniquely defined database library module names.
   *
   * @return array
   *   List of uniquely defined database library module names.
   */
  public function getModules() {
    return $this->connection->query("SELECT DISTINCT([module]) FROM {custom_test_item} ORDER BY [module]")->fetchAllKeyed(0, 0);
  }

  /**
   * Gathers a list of uniquely defined database library module names.
   *
   * @return array
   *   List of uniquely defined database library module names.
   */
  public function getTests() {
    return $this->connection->query("SELECT DISTINCT([test]) FROM {custom_test_item} ORDER BY [test]")->fetchAllKeyed(0, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('module') != NULL) {
      if (!preg_match($this->regex['string_space'], $form_state->getValue('module'))) {
        $form_state->setErrorByName('module', $this->t('Invalid option.'));
      }
    }
    if ($form_state->getValue('test') != NULL) {
      foreach ($form_state->getValue('test') as $test) {
        if (!preg_match($this->regex['string'], $test)) {
          $form_state->setErrorByName('test', $this->t('Invalid option.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $session_filters = $this->getRequest()->getSession()->get('custom_tests_overview_filter', []);

    foreach ($this->filters as $name) {
      if ($form_state->hasValue($name)) {
        $session_filters[$name] = $form_state->getValue($name);
      }
    }
    $this->getRequest()->getSession()->set('custom_tests_overview_filter', $session_filters);
  }

  /**
   * Resets the filter form.
   */
  public function resetForm() {
    $this->getRequest()->getSession()->remove('custom_tests_overview_filter');
  }

}
