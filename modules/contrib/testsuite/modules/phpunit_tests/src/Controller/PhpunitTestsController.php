<?php

namespace Drupal\phpunit_tests\Controller;

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
class PhpunitTestsController extends ControllerBase {
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
   * Constructs a PhpunitTestsController object.
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
   * Messages are truncated at 56 chars.
   * Full-length messages can be viewed on the message details page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   *
   * @see Drupal\phpunit_tests\Form\PhpunitTestsClearLogConfirmForm
   * @see Drupal\phpunit_tests\Controller\PhpunitTestsController::eventDetails()
   */
  public function overview(Request $request) {
    $filter = $this->buildFilterQuery($request);

    // dd($filter);
    $build['phpunit_test_run_tests_form'] = $this->formBuilder()->getForm('Drupal\phpunit_tests\Form\PhpunitTestsRunTestsMultistepForm');

    $rows = [];

    $build['phpunit_test_filter_form'] = $this->formBuilder()->getForm('Drupal\phpunit_tests\Form\PhpunitTestsFilterForm');
    $header = [
      [
        'data' => $this->t('Date Created'),
        'field' => 'pt.created',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Area'),
        'field' => 'pti.area',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Module'),
        'field' => 'pti.module',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Directory'),
        'field' => 'pti.directory',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Test'),
        'field' => 'pti.test',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Report'),
        'field' => 'pti.error_report',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
    ];

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select('phpunit_test_item', 'pti')
      ->extend(PagerSelectExtender::class)
      ->extend(TableSortExtender::class);
    $query->fields(
          'pt', [
            'id',
            'created',
          ]
      );
    $query->fields(
          'pti', [
            'module',
            'test',
            'area',
            'directory',
            'error_report',
          ]
      );

    $query->leftJoin('phpunit_test', 'pt', '[pt].[id] = [pti].[phpunit_test_id]');

    if (!empty($filter['where'])) {
      $query->where($filter['where'], $filter['args']);
    }

    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $testItem) {
      $test = Html::escape($testItem->test);
      $directory = Html::escape($testItem->directory);
      $testLink = Link::fromTextAndUrl(
        $test ? $test : "All {$directory} Tests", new Url(
                'phpunit_tests.event', ['event_id' => $testItem->id], [
                  'attributes' => [
                    'title' => $test,
                  ],
                ]
            )
        )->toString();
      $message = $this->formatMessage($testItem->error_report);
      $rows[] = [
        'data' => [
          $this->dateFormatter->format($testItem->created, 'short'),
          Html::escape($testItem->module),
          Html::escape($testItem->area),
          $directory,
          $testLink,
          $message ? Markup::create($message) : "",
        ],
      ];
    }

    $build['phpunit_tests_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No log messages available.'),
    ];

    $build['phpunit_tests_pager'] = ['#type' => 'pager'];

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
    $testItem = $this->database->query('SELECT [pti].*, [pt].created FROM {phpunit_test_item} [pti] LEFT JOIN {phpunit_test} [pt] ON [pt].[id] = [pti].[phpunit_test_id] WHERE [pt].[id] = :id', [':id' => $event_id])->fetchObject();

    if (empty($testItem)) {
      throw new NotFoundHttpException();
    }

    $message = $this->formatMessage($testItem->error_report);

    $build = [];
    $rows = [
      [
      ['data' => $this->t('Id'), 'header' => TRUE],
        Html::escape($testItem->phpunit_test_id),
      ],
      [
      ['data' => $this->t('Date Created'), 'header' => TRUE],
        $this->dateFormatter->format($testItem->created, 'long'),
      ],
      [
      ['data' => $this->t('Area'), 'header' => TRUE],
        Html::escape($testItem->area),
      ],
      [
      ['data' => $this->t('Module'), 'header' => TRUE],
        Html::escape($testItem->module),
      ],
      [
      ['data' => $this->t('Test Directory'), 'header' => TRUE],
        Html::escape($testItem->directory),
      ],
      [
      ['data' => $this->t('Test'), 'header' => TRUE],
        Html::escape($testItem->test),
      ],
      [
      ['data' => $this->t('Results'), 'header' => TRUE],
        $message ? Markup::create($message) : "",
      ],
    ];

    $build['phpunit_test_item_table'] = [
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
    $session_filters = $request->getSession()->get('phpunit_tests_overview_filter', []);
    if (empty($session_filters)) {
      return;
    }

    // dd($session_filters);
    $filters = [];
    foreach ($session_filters as $key => $filter) {
      if ($filter != "") {
        $filters[$key]['where'] = 'pti.' . $key . ' = ?';
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
