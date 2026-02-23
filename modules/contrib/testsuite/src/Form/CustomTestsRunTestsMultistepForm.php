<?php

namespace Drupal\testsuite\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\testsuite\BaseTrait;
use Drupal\testsuite\CustomTestsResourceService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The run test multi step class.
 */
class CustomTestsRunTestsMultistepForm extends FormBase {
  use BaseTrait;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The time interface.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeInterface;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Load the Resource Service.
   *
   * @var \Drupal\testsuite\CustomTestsResourceService
   */
  protected $customTestsResourceService;

  /**
   * Load Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * CustomTestsRunTestsMultistepForm constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Load messenger service.
   * @param \Drupal\Component\Datetime\TimeInterface $timeInterface
   *   The time interface.
   * @param \Drupal\testsuite\CustomTestsResourceService $customTestsResourceService
   *   The load resource service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config service.
   */
  public function __construct(
    Connection $connection,
    MessengerInterface $messenger,
    TimeInterface $timeInterface,
    CustomTestsResourceService $customTestsResourceService,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->timeInterface = $timeInterface;
    $this->customTestsResourceService = $customTestsResourceService;
    $this->configFactory = $configFactory;
    ini_set('max_execution_time', 0);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('datetime.time'),
      $container->get('testsuite.load_resource.service'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'testsuite_run_tests_multistep';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Set the page for the first time.
    if (!$form_state->has('page')) {
      $form_state->set('page', 1);
    }

    // Empty container to display the error messges.
    $form['messages'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'form-errors',
      ],
    ];

    switch ($form_state->get('page')) {
      case 2:
        $form['content'] = $this->buildSecondPage($form, $form_state);
        break;

      default:
        $form['content'] = $this->buildFirstPage($form, $form_state);
        break;
    }

    $form['content']['#type'] = 'container';
    $form['content']['#attributes']['id'] = 'form-content';

    return $form;
  }

  /**
   * Validation handler for page 1.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   */
  public function formFirstNextValidate(array &$form, FormStateInterface $form_state) {
    // Check module.
    if ($form_state->isValueEmpty('module')) {
      $form_state->setErrorByName('module', $this->t('You must select something to filter by.'));
    }
  }

  /**
   * Submission handler function for page 1.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   */
  public function formFirstNextSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set(
      'stored_values',
      array_merge(
        $form_state->get('stored_values') ?? [],
        [
          'module' => $form_state->getValue('module'),
        ]
      )
    );
    $form_state->setValues($form_state->get('stored_values'));
    $form_state->set('page', 2);
    // Set the form to rebuild so the form shows the next page when using Ajax.
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submission handler for back button of the page 2.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   */
  public function formSecondPageTwoBack(array &$form, FormStateInterface $form_state) {
    $form_user_inputs = $form_state->getUserInput();
    $form_state->set('stored_values', array_merge(
      $form_state->get('stored_values') ?? [],
      [
        'test' => $form_user_inputs['test'],
      ]
    ));
    $form_state->setValues($form_state->get('stored_values'));
    $form_state->set('page', 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Validation handler for page 2.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   */
  public function formSecondNextValidate(array &$form, FormStateInterface $form_state) {
    // Check test.
    if ($form_state->getValue('test') == []) {
      $form_state->setErrorByName('test', $this->t('You must select something to filter by.'));
    }
    foreach ($form_state->getValue('test') as $test) {
      if (!preg_match($this->regex['php_file_path'], $test)) {
        $form_state->setErrorByName('test', $this->t('Invalid option.'));
      }
    }
  }

  /**
   * Submission handler function for page 2.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   */
  public function formSecondNextSubmit(array &$form, FormStateInterface $form_state) {
    $form_values = array_merge(
      $form_state->get('stored_values') ?? [],
      [
        'test' => $form_state->getValue('test'),
      ]
    );

    // Run the test or save items under group.
    $values = $this->mapFormValues($form_values);

    $form_state->set('tests_ran', $this->runTestsFromFormValues($values));
    // $form_state->setValues($form_state->get('stored_values'));
    $form_state->set('page', 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Callback function to handler the ajax behavior of the buttons.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   With the commands to be executed by the Drupal Ajax API.
   */
  public function formAjaxChangePage(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Dsiplay the form error messages if it has any.
    if ($form_state->hasAnyErrors()) {
      $messages = $this->messenger->deleteAll();
      $form['messages']['content'] = [
        '#theme'        => 'status_messages',
        '#message_list' => $messages,
      ];
    }

    $response->addCommand(new ReplaceCommand('#form-content', $form['content']));
    $response->addCommand(new ReplaceCommand('#form-errors', $form['messages']));

    return $response;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Rerturn to the first page.
    $form_state->set('page', 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Builds the first page of the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   *
   * @return array
   *   The render array with the items on this page.
   */
  private function buildFirstPage(array &$form, FormStateInterface $form_state) {

    $build['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Run Tests Part 1'),
      '#description' => $this->t('To create custom tests copy the custom module provided into the custom directory and change the custom_tests.info.yml.txt to custom_tests.info.yml. To create them in other custom modules follow the example modules directory structure.'),
      '#open' => TRUE,
      '#attached' => [
        'library' => [
          'testsuite/testsuite.filters',
        ],
      ],
    ];

    $build['filters']['filters-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'testsuite-tests-filter-form-wrapper',
      ],
    ];

    if ($modulesWithTests = $this->customTestsResourceService->getResource('modules')) {
      $moduleDropdown = $form_state->hasValue('module') ? $form_state->getValue('module') : '';

      $build['filters']['filters-container']['module-container'] = [
        '#type' => 'container',
      ];
      $build['filters']['filters-container']['module-container']['module'] = [
        '#title' => 'Module',
        '#type' => 'select',
        '#multiple' => FALSE,
        '#size' => 4,
        '#options' => $modulesWithTests,
        '#default_value' => $moduleDropdown,
      ];

      $build['filters']['actions'] = [
        '#type' => 'actions',
        '#attributes' => ['class' => ['container-inline']],
      ];

      $build['filters']['actions']['next'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->t('Next'),
        '#submit' => ['::formFirstNextSubmit'],
        '#validate' => ['::formFirstNextValidate'],
        '#ajax' => [
          'callback' => '::formAjaxChangePage',
          'event' => 'click',
          'progress' => [
            'type' => 'fullScreen',
          ],
        ],
      ];
    }

    return $build;
  }

  /**
   * Builds the second page of the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   *
   * @return array
   *   The render array with the items on this page.
   */
  private function buildSecondPage(array &$form, FormStateInterface $form_state) {
    $build['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Run Tests Part 2'),
      '#open' => TRUE,
      '#attached' => [
        'library' => [
          'testsuite/testsuite.filters',
        ],
      ],
    ];

    $build['filters']['filters-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'testsuite-tests-filter-form-wrapper',
      ],
    ];

    $moduleDropdown = $form_state->hasValue('module') ? $form_state->getValue('module') : '';
    if ($tests = $this->customTestsResourceService->getResource('tests', $moduleDropdown)) {
      $testDropdown = $form_state->hasValue('test') ? $form_state->getValue('test') : '';

      $build['filters']['filters-container']['tests-container'] = [
        '#type' => 'container',
      ];
      $build['filters']['filters-container']['tests-container']['test'] = [
        '#title' => 'Tests',
        '#type' => 'select',
        '#multiple' => TRUE,
        '#size' => 4,
        '#options' => $tests,
        '#default_value' => $testDropdown,
      ];
    }

    $build['filters']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#submit' => ['::formSecondPageTwoBack'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::formAjaxChangePage',
        'event' => 'click',
        'progress' => [
          'type' => 'fullScreen',
        ],
      ],
    ];

    $build['filters']['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Submit'),
      '#submit' => ['::formSecondNextSubmit'],
      '#validate' => ['::formSecondNextValidate'],
    ];

    return $build;
  }

  /**
   * Trim and map the form values to by save in the user fields.
   *
   * @param array $form_values
   *   Array with the values passed by the form.
   *
   * @return array
   *   Array of mapped values.
   */
  private function mapFormValues(array $form_values) {
    $values['module'] = trim($form_values['module']);
    $tests = [];
    foreach ($form_values['test'] as $test) {
      $tests[] = $test;
    }
    $values['test'] = $tests;

    return $values;
  }

  /**
   * Runs phpunit tests and saves results to the database.
   *
   * @param array $values
   *   Array with the values to set.
   *
   * @return bool
   *   True or False if there was a problem creating a test.
   */
  private function runTestsFromFormValues(array $values) {
    $ifError = FALSE;
    $errorCount = 0;
    $testCount = 0;
    foreach ($values['test'] as $file) {
      $className = basename($file, '.php');
      $class = '\Drupal\\' . $values['module'] . '\CustomTSTests\\' . $className;
      require_once $file;
      if (class_exists($class)) {
        $obj = new $class();
        $results = $this->connection->insert('custom_test_item')
          ->fields(
          [
            'test' => $className,
            'module' => $obj->getModuleName(),
            'name' => $obj->getName(),
            'description' => $obj->getDescription(),
            'report' => $obj->runTest(),
            'created' => $this->timeInterface->getRequestTime(),
          ]
        )
          ->execute();
        if ($results) {
          $testCount++;
        }
        else {
          $errorCount++;
          $ifError = TRUE;
        }
      }
    }

    if (!$ifError) {
      $this->messenger->addStatus($testCount . ' custom test has been recorded.');
      return TRUE;
    }
    else {
      $this->messenger->addStatus($errorCount . ' custom test could not not been recorded. ' . $testCount . ' custom test has been recorded.. Please be sure all packages are loaded and the phpunit.xml is created with the right data.');
      return FALSE;
    }
  }

}
