<?php

namespace Drupal\phpcs_tests\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\testsuite\BaseTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for dblog routes.
 */
class PhpcsTestsController extends ControllerBase {
  use BaseTrait;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('database'),
          $container->get('module_handler'),
          $container->get('date.formatter'),
          $container->get('form_builder')
      );
  }

  /**
   * Constructs a DbLogController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(
    Connection $database,
    ModuleHandlerInterface $module_handler,
    DateFormatterInterface $date_formatter,
    FormBuilderInterface $form_builder,
  ) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->dateFormatter = $date_formatter;
    $this->formBuilder = $form_builder;
    $this->userStorage = $this->entityTypeManager()->getStorage('user');
  }

  /**
   * Displays a listing of database log messages.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   *
   * @see Drupal\phpcs_tests\Form\PhpcsTestsClearLogConfirmForm
   * @see Drupal\phpcs_tests\Controller\PhpcsTestsController::eventDetails()
   */
  public function overview(Request $request) {
    $filter = $this->buildFilterQuery($request);
    $rows = [];

    $build['phpcs_test_run_tests_form'] = $this->formBuilder()->getForm('Drupal\phpcs_tests\Form\PhpcsTestsRunTestsMultistepForm');

    $build['phpcs_test_filter_form'] = $this->formBuilder()->getForm('Drupal\phpcs_tests\Form\PhpcsTestsFilterForm');
    $header = [
      [
        'data' => $this->t('Date Created'),
        'field' => 'p.created',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Area'),
        'field' => 'p.area',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Type'),
        'field' => 'p.module',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Line Number'),
        'field' => 'p.line_number',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('File'),
        'field' => 'p.file_checked',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Error Type'),
        'field' => 'p.error_type',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Severity'),
        'field' => 'p.severity',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Error'),
        'field' => 'p.error_report',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
    ];

    /**
     *
     *
* @var \Drupal\Core\Database\Query\SelectInterface $query
*/
    $query = $this->database->select('phpcs_test_item', 'p')
      ->extend(PagerSelectExtender::class)
      ->extend(TableSortExtender::class);
    $query->fields(
        'p', [
          'id',
          'area',
          'module',
          'line_number',
          'file_checked',
          'error_type',
          'severity',
          'error_report',
          'created',
        ]
    );

    if (!empty($filter['where'])) {
      $query->where($filter['where'], $filter['args']);
    }

    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $testItem) {
      $file_checked = Html::escape($testItem->file_checked);
      $fileChecked = Link::fromTextAndUrl(
            $this->getFileName($file_checked), new Url(
                'phpcs_tests.event', ['event_id' => $testItem->id], [
                  'attributes' => [
                    'title' => $file_checked,
                  ],
                ]
            )
        )->toString();
      $message = $this->formatMessage($testItem->error_report);
      $rows[] = [
        'data' => [
          $this->dateFormatter->format($testItem->created, 'short'),
          Html::escape($testItem->area),
          Html::escape($testItem->module),
          Html::escape($testItem->line_number),
          $fileChecked,
          Html::escape($testItem->error_type),
          Html::escape($testItem->severity),
          $message ? Markup::create($message) : "",
        ],
      ];
    }

    $build['phpcs_tests_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No log messages available.'),
    ];

    $build['phpcs_tests_pager'] = ['#type' => 'pager'];

    return $build;

  }

  /**
   * Displays details about a specific database log message.
   *
   * @param int $event_id
   *   Unique ID of the database log message.
   *
   * @return array
   *   If the ID is located in the Database Logging table, a build array in the
   *   format expected by \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If no event found for the given ID.
   */
  public function eventDetails($event_id) {
    $testItem = $this->database->query('SELECT [p].* FROM {phpcs_test_item} [p] WHERE [p].[id] = :id', [':id' => $event_id])->fetchObject();

    if (empty($testItem)) {
      throw new NotFoundHttpException();
    }

    $build = [];
    $rows = [
      [
      ['data' => $this->t('Id'), 'header' => TRUE],
        Html::escape($testItem->id),
      ],
      [
      ['data' => $this->t('Area'), 'header' => TRUE],
        Html::escape($testItem->area),
      ],
      [
      ['data' => $this->t('Type'), 'header' => TRUE],
        Html::escape($testItem->module),
      ],
      [
      ['data' => $this->t('Date Created'), 'header' => TRUE],
        $this->dateFormatter->format($testItem->created, 'long'),
      ],
      [
      ['data' => $this->t('Line Number'), 'header' => TRUE],
        Html::escape($testItem->line_number),
      ],
      [
      ['data' => $this->t('File'), 'header' => TRUE],
        Html::escape($testItem->file_checked),
      ],
      [
      ['data' => $this->t('Error Type'), 'header' => TRUE],
        Html::escape($testItem->error_type),
      ],
      [
      ['data' => $this->t('Severity'), 'header' => TRUE],
        Html::escape($testItem->severity),
      ],
      [
      ['data' => $this->t('Error'), 'header' => TRUE],
        Html::escape($testItem->error_report),
      ],
    ];

    $build['phpcs_test_item_table'] = [
      '#type' => 'table',
      '#rows' => $rows,
    ];

    return $build;
  }

  /**
   * Builds a query for database log administration filters based on session.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array|null
   *   An associative array with keys 'where' and 'args' or NULL if there were
   *   no filters set.
   */
  protected function buildFilterQuery(Request $request) {
    $session_filters = $request->getSession()->get('phpcs_tests_overview_filter', []);
    if (empty($session_filters)) {
      return;
    }

    // dd($session_filters);
    $filters = [];
    foreach ($session_filters as $key => $filter) {
      if ($filter != "") {
        $filters[$key]['where'] = 'p.' . $key . ' = ?';
      }
    }

    // Build query.
    $where = $args = [];
    foreach ($session_filters as $key => $filter) {
      if ($filter != "") {
        $filter_where = [];
        $filter_where[] = $filters[$key]['where'];
        $args[] = $filter;

        if (count($filter_where) > 0) {
          $where[] = '(' . implode(' AND ', $filter_where) . ')';
        }
      }
    }
    $where = !empty($where) ? implode(' AND ', $where) : '';

    return [
      'where' => $where,
      'args' => $args,
    ];
  }

}
