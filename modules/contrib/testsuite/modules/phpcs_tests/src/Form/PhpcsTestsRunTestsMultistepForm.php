<?php

namespace Drupal\phpcs_tests\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\phpcs_tests\PhpcsTestsResourceService;
use Drupal\testsuite\BaseTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The run test multi step class.
 */
class PhpcsTestsRunTestsMultistepForm extends FormBase {
  use BaseTrait;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The time interface.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeInterface;

  /**
   * Load the Resource Service.
   *
   * @var \Drupal\phpcs_tests\PhpcsTestsResourceService
   */
  protected $phpcsTestsResourceService;

  /**
   * Load Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * PhpcsTestsRunTestsMultistepForm constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Load messenger service.
   * @param \Drupal\Component\Datetime\TimeInterface $timeInterface
   *   The load resource service.
   * @param \Drupal\phpcs_tests\PhpcsTestsResourceService $phpcsTestsResourceService
   *   The load resource service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config service.
   */
  public function __construct(
    Connection $connection,
    MessengerInterface $messenger,
    TimeInterface $timeInterface,
    PhpcsTestsResourceService $phpcsTestsResourceService,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->timeInterface = $timeInterface;
    $this->phpcsTestsResourceService = $phpcsTestsResourceService;
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
          $container->get('phpcs_tests.load_resource.service'),
          $container->get('config.factory')
      );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'phpcs_tests_run_tests_multistep';
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
    // Check type.
    if ($form_state->isValueEmpty('type')) {
      $form_state->setErrorByName('type', $this->t('You must select something to filter by.'));
    }
    if (!in_array($form_state->getValue('type'), $this->types)) {
      $form_state->setErrorByName('type', $this->t('Invalid option.'));
    }

    // Check area.
    if ($form_state->isValueEmpty('area')) {
      $form_state->setErrorByName('area', $this->t('You must select something to filter by.'));
    }
    if (!in_array($form_state->getValue('area'), $this->areas)) {
      $form_state->setErrorByName('area', $this->t('Invalid option.'));
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
                'type' => $form_state->getValue('type'),
                'area' => $form_state->getValue('area'),
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
    $stored_values = $form_state->get('stored_values');
    if ($stored_values['type'] == 'module') {
      $data = [
        'module' => $form_user_inputs['module'],
      ];
    }
    else {
      $data = [
        'theme' => $form_user_inputs['theme'],
      ];
    }

    $form_state->set('stored_values', array_merge(
          $stored_values ?? [],
          $data
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
    if ($form_state->getValue('module') != NULL) {
      if (!preg_match($this->regex['string_space'], $form_state->getValue('module'))) {
        $form_state->setErrorByName('module', $this->t('Invalid option.'));
      }
    }
    if ($form_state->getValue('theme') != NULL) {
      if (!preg_match($this->regex['string_space'], $form_state->getValue('theme'))) {
        $form_state->setErrorByName('theme', $this->t('Invalid option.'));
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
    $stored_values = $form_state->get('stored_values');
    if ($stored_values['type'] == 'module') {
      $data = [
        'module' => $form_state->getValue('module'),
      ];
    }
    else {
      $data = [
        'theme' => $form_state->getValue('theme'),
      ];
    }

    $form_values = array_merge(
          $stored_values ?? [],
          $data
      );

    // Run the test or save items under group.
    $values = $this->mapFormValues($form_values);

    $form_state->set('tests_ran', $this->runTestsFromFormValues($values));
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
    // Return to the first page.
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

    $testTypeDropdown = $form_state->hasValue('type') ? $form_state->getValue('type') : '';

    $types = [];
    foreach ($this->types as $type) {
      $types[$type] = ucwords($type);
    }

    $build['filters']['filters-container']['test-type-container'] = [
      '#type' => 'container',
    ];
    $build['filters']['filters-container']['test-type-container']['type'] = [
      '#title' => 'Test Type',
      '#type' => 'select',
      '#multiple' => FALSE,
      '#size' => 4,
      '#options' => $types,
      '#default_value' => $testTypeDropdown,
    ];

    $areaDropdown = $form_state->hasValue('area') ? $form_state->getValue('area') : '';

    $build['filters']['filters-container']['area-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'testsuite-tests-areas-container',
      ],
    ];
    $build['filters']['filters-container']['area-container']['area'] = [
      '#title' => 'Area',
      '#type' => 'select',
      '#multiple' => FALSE,
      '#size' => 4,
      '#options' => [
        'core' => $this->t('Core'),
        'contrib' => $this->t('Contrib'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => $areaDropdown,
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

    $module = $form_state->hasValue('module') ? $form_state->getValue('module') : '';
    $theme = $form_state->hasValue('theme') ? $form_state->getValue('theme') : '';
    $area = $form_state->hasValue('area') ? $form_state->getValue('area') : '';

    if ($form_state->getValue('type') == 'module') {
      if ($modules = $this->phpcsTestsResourceService->getResource('module', $area)) {
        $build['filters']['filters-container']['module-container'] = [
          '#type' => 'container',
        ];
        $build['filters']['filters-container']['module-container']['module'] = [
          '#title' => 'Module',
          '#type' => 'select',
          '#multiple' => FALSE,
          '#size' => 4,
          '#options' => $modules,
          '#default_value' => $module,
        ];

        $build['filters']['actions']['next'] = [
          '#type' => 'submit',
          '#button_type' => 'primary',
          '#value' => $this->t('Submit'),
          '#submit' => ['::formSecondNextSubmit'],
          '#validate' => ['::formSecondNextValidate'],
        ];
      }
    }
    else {
      if ($themes = $this->phpcsTestsResourceService->getResource('theme', $area)) {
        $build['filters']['filters-container']['theme-container'] = [
          '#type' => 'container',
        ];
        $build['filters']['filters-container']['theme-container']['theme'] = [
          '#title' => 'Theme',
          '#type' => 'select',
          '#multiple' => FALSE,
          '#size' => 4,
          '#options' => $themes,
          '#default_value' => $theme,
        ];

        $build['filters']['actions']['next'] = [
          '#type' => 'submit',
          '#button_type' => 'primary',
          '#value' => $this->t('Next'),
          '#submit' => ['::formSecondNextSubmit'],
          '#validate' => ['::formSecondNextValidate'],
          '#ajax' => [
            'callback' => '::formAjaxChangePage',
            'event' => 'click',
            'progress' => [
              'type' => 'fullScreen',
            ],
          ],
        ];
      }
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
    $values['type'] = trim($form_values['type']);
    $values['area'] = trim($form_values['area']);
    if ($values['type'] == 'module') {
      $values['module'] = trim($form_values['module']);
    }
    else {
      $values['theme'] = trim($form_values['theme']);
    }

    return $values;
  }

  /**
   * Creates a log entry.
   *
   * @param string $area
   *   The area the module is located. Core, contrib or custom.
   * @param string $module
   *   The module name.
   * @param string $lineNumber
   *   The line number where the eroor is.
   * @param string $fileChecked
   *   The file with the error.
   * @param string $errorType
   *   The error type.
   * @param string $severity
   *   The severity of the error.
   * @param string $errorReport
   *   The error reported.
   *
   * @return bool
   *   True or false depending on if insert succeeds.
   */
  public function createLog($area, $module, $lineNumber, $fileChecked, $errorType, $severity, $errorReport) {
    return $this->connection->insert('phpcs_test_item')
      ->fields(
              [
                'area' => $area,
                'module' => $module,
                'line_number' => $lineNumber,
                'file_checked' => $fileChecked,
                'error_type' => $errorType,
                'severity' => $severity,
                'error_report' => $errorReport,
                'created' => $this->timeInterface->getRequestTime(),
              ]
          )
      ->execute();
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
    $item = $values['type'] == 'module' ? $values['module'] : $values['theme'];
    $reportArray = $this->phpcsTestsResourceService->getPhpcsReport($values['type'], $values['area'], $item);
    if (count($reportArray) > 0) {
      $ifDbError = FALSE;
      $count = 0;
      $result = "";
      foreach ($reportArray as $line) {
        try {
          $result = $this->connection->insert('phpcs_test_item')
            ->fields(
                [
                  'area' => $values['area'],
                  'module' => $item,
                  'line_number' => $line['line'],
                  'file_checked' => $line['file'],
                  'error_type' => $line['type'],
                  'severity' => $line['severity'],
                  'error_report' => $line['error'],
                  'created' => $this->timeInterface->getRequestTime(),
                ]
            )
            ->execute();
        }
        catch (\Exception $e) {
          $this->messenger->addStatus($e . ' Database eroor.');
        }
        if (!$result) {
          $ifDbError = TRUE;
        }
        else {
          $count++;
        }
      }
      if ($ifDbError) {
        $this->messenger->addStatus('Phpcs test could not not been recorded. Please be sure the correct composer packages are loaded.');
        return FALSE;
      }
      else {
        $this->messenger->addStatus($count . ' Phpcs tests has been recorded.');
        return TRUE;
      }
    }
    else {
      $this->messenger->addStatus('Congratulations! You have no errors.');
      return TRUE;
    }
  }

}
