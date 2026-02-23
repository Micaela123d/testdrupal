<?php

namespace Drupal\testsuite\Controller;

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
class CustomTestsController extends ControllerBase {
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
   * Constructs a CustomTestsController object.
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
   * @see Drupal\custom_tests\Form\CustomTestsClearLogConfirmForm
   * @see Drupal\custom_tests\Controller\CustomTestsController::eventDetails()
   */
  public function overview(Request $request) {
    $filter = $this->buildFilterQuery($request);
    $build['custom_test_run_tests_form'] = $this->formBuilder()->getForm('Drupal\testsuite\Form\CustomTestsRunTestsMultistepForm');

    $rows = [];

    // dd($filter);
    $build['custom_test_filter_form'] = $this->formBuilder()->getForm('Drupal\testsuite\Form\CustomTestsFilterForm');
    $header = [
      [
        'data' => $this->t('Date Created'),
        'field' => 'cti.created',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Module'),
        'field' => 'cti.module',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Test Name'),
        'field' => 'cti.name',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Description'),
        'field' => 'cti.description',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Report'),
        'field' => 'cti.report',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
    ];

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select('custom_test_item', 'cti')
      ->extend(PagerSelectExtender::class)
      ->extend(TableSortExtender::class);

    $query->fields(
      'cti', [
        'id',
        'created',
        'module',
        'name',
        'description',
        'report',
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
      $testLink = Link::fromTextAndUrl(
        Html::escape($testItem->name), new Url(
          'testsuite.event', ['event_id' => $testItem->id], [
            'attributes' => [
              'title' => Html::escape($testItem->name),
            ],
          ]
        )
      )->toString();
      $message = $this->formatMessage($testItem->report);
      $rows[] = [
        'data' => [
          $this->dateFormatter->format($testItem->created, 'short'),
          Html::escape($testItem->module),
          $testLink,
          Html::escape($testItem->description),
          $message ? Markup::create($message) : "",
        ],
      ];
    }

    $build['custom_tests_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No log messages available.'),
    ];

    $build['custom_tests_pager'] = ['#type' => 'pager'];

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
    $testItem = $this->database->query('SELECT [cti].* FROM {custom_test_item} [cti] WHERE [cti].[id] = :id', [':id' => $event_id])->fetchObject();

    if (empty($testItem)) {
      throw new NotFoundHttpException();
    }

    $message = $this->formatMessage($testItem->report);

    $build = [];
    $rows = [
      [
      ['data' => $this->t('Id'), 'header' => TRUE],
        Html::escape($testItem->id),
      ],
      [
      ['data' => $this->t('Date Created'), 'header' => TRUE],
        $this->dateFormatter->format($testItem->created, 'long'),
      ],
      [
        ['data' => $this->t('Module'), 'header' => TRUE],
        Html::escape($testItem->module),
      ],
      [
      ['data' => $this->t('Test'), 'header' => TRUE],
        Html::escape($testItem->test),
      ],
      [
        ['data' => $this->t('Test Name'), 'header' => TRUE],
        Html::escape($testItem->name),
      ],
      [
        ['data' => $this->t('Description'), 'header' => TRUE],
        Html::escape($testItem->description),
      ],
      [
      ['data' => $this->t('Results'), 'header' => TRUE],
        $message ? $message : "",
      ],
    ];

    $build['custom_test_item_table'] = [
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
    $session_filters = $request->getSession()->get('custom_tests_overview_filter', []);
    if (empty($session_filters)) {
      return;
    }

    $filters = [];
    foreach ($session_filters as $key => $filter) {
      if ($filter != "" && $filter != []) {
        $filters[$key]['where'] = 'cti.' . $key . ' = ?';
      }
    }

    // Build query.
    $where = $args = $filter_where = [];
    foreach ($session_filters as $key => $filter) {
      if ($filter != "" && $filter != []) {
        if (is_array($filter)) {
          foreach ($filter as $fil) {
            $filter_where[] = $filters[$key]['where'];
            $args[] = $fil;
          }
        }
        else {
          $filter_where[] = $filters[$key]['where'];
          $args[] = $filter;
        }

        if (count($filter_where) > 0) {
          $where[] = '(' . implode(' OR ', $filter_where) . ')';
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
