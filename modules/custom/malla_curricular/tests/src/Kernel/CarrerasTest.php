<?php

namespace Drupal\Tests\malla_curricular\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Pruebas Kernel para las consultas de carreras y cursos.
 *
 * @group malla_curricular
 */
class CarrerasTest extends KernelTestBase {

  /**
   * Módulos a instalar para esta prueba.
   *
   * @var array
   */
  protected static $modules = [
    'malla_curricular',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Instalar las tablas de nuestro módulo
    $this->installSchema('malla_curricular', ['carreras', 'cursos']);

    // Insertar datos de prueba
    $this->insertarDatosPrueba();
  }

  /**
   * Inserta datos de prueba en las tablas.
   */
  protected function insertarDatosPrueba() {
    $database = \Drupal::database();

    // Insertar carrera 1: Ingeniería Industrial (ID será 1)
    $database->insert('carreras')
      ->fields([
        'nombre' => 'Ingeniería Industrial',
        'codigo' => 'II-01',
        'duracion_semestres' => 10,
        'total_creditos' => 210,
        'total_horas_teoricas' => 3200,
        'total_horas_practicas' => 2800,
        'total_horas' => 6000,
      ])
      ->execute();

    // Insertar carrera 2: Administración (ID será 2)
    $database->insert('carreras')
      ->fields([
        'nombre' => 'Administración de Empresas',
        'codigo' => 'AD-01',
        'duracion_semestres' => 8,
        'total_creditos' => 180,
        'total_horas_teoricas' => 2800,
        'total_horas_practicas' => 2000,
        'total_horas' => 4800,
      ])
      ->execute();

    // Insertar cursos para Ingeniería Industrial (id_carrera = 1)
    $this->insertarCursosIndustriales($database);

    // Insertar cursos para Administración (id_carrera = 2)
    $this->insertarCursosAdministracion($database);
  }

  /**
   * Inserta cursos de prueba para Ingeniería Industrial.
   */
  protected function insertarCursosIndustriales($database) {
    // Semestre 1 - 6 cursos
    $database->insert('cursos')
      ->fields(['id_carrera', 'codigo_curso', 'nombre', 'semestre', 'creditos', 'horas_teoricas', 'horas_practicas', 'total_horas', 'area', 'condicion', 'numero_orden'])
      ->values([1, 'II101', 'Matemática Básica', 1, 4, 3, 2, 5, 'General', 'Obligatorio', 1])
      ->values([1, 'II102', 'Física General', 1, 4, 3, 2, 5, 'General', 'Obligatorio', 2])
      ->values([1, 'II103', 'Química General', 1, 4, 3, 2, 5, 'General', 'Obligatorio', 3])
      ->values([1, 'II104', 'Introducción a la Ingeniería Industrial', 1, 3, 2, 2, 4, 'General', 'Obligatorio', 4])
      ->values([1, 'II105', 'Comunicación Integral', 1, 3, 2, 2, 4, 'General', 'Obligatorio', 5])
      ->values([1, 'II106', 'Metodología del Trabajo Universitario', 1, 2, 1, 2, 3, 'General', 'Obligatorio', 6])
      ->execute();

    // Semestre 2 - algunos cursos para probar
    $database->insert('cursos')
      ->fields(['id_carrera', 'codigo_curso', 'nombre', 'semestre', 'creditos', 'horas_teoricas', 'horas_practicas', 'total_horas', 'area', 'condicion', 'numero_orden'])
      ->values([1, 'II201', 'Cálculo Diferencial', 2, 4, 3, 2, 5, 'General', 'Obligatorio', 1])
      ->values([1, 'II202', 'Álgebra Lineal', 2, 4, 3, 2, 5, 'General', 'Obligatorio', 2])
      ->values([1, 'II203', 'Física Mecánica', 2, 4, 3, 2, 5, 'General', 'Obligatorio', 3])
      ->execute();
  }

  /**
   * Inserta cursos de prueba para Administración.
   */
  protected function insertarCursosAdministracion($database) {
    $database->insert('cursos')
      ->fields(['id_carrera', 'codigo_curso', 'nombre', 'semestre', 'creditos', 'horas_teoricas', 'horas_practicas', 'total_horas', 'area', 'condicion', 'numero_orden'])
      ->values([2, 'AD101', 'Administración I', 1, 4, 3, 2, 5, 'General', 'Obligatorio', 1])
      ->values([2, 'AD102', 'Contabilidad Básica', 1, 4, 3, 2, 5, 'General', 'Obligatorio', 2])
      ->values([2, 'AD103', 'Economía General', 1, 4, 3, 2, 5, 'General', 'Obligatorio', 3])
      ->execute();
  }

  /**
   * PRUEBA 1: Verificar que hay carreras en la base de datos.
   */
  public function testExistenCarreras() {
    $database = \Drupal::database();

    $count = $database->select('carreras')
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertEquals(2, $count, 'Debe haber exactamente 2 carreras de prueba');
  }

  /**
   * PRUEBA 2: Verificar que Ingeniería Industrial existe.
   */
  public function testExisteIngenieriaIndustrial() {
    $database = \Drupal::database();

    $carrera = $database->select('carreras', 'c')
      ->fields('c', ['nombre', 'codigo', 'duracion_semestres'])
      ->condition('c.codigo', 'II-01')
      ->execute()
      ->fetchObject();

    $this->assertNotEmpty($carrera, 'La carrera II-01 debe existir');
    $this->assertEquals('Ingeniería Industrial', $carrera->nombre);
    $this->assertEquals(10, $carrera->duracion_semestres);
  }

  /**
   * PRUEBA 3: Verificar cursos por carrera.
   */
  public function testCursosPorCarrera() {
    $database = \Drupal::database();

    // Obtener ID de Industrial
    $id_industrial = $database->select('carreras', 'c')
      ->fields('c', ['id_carrera'])
      ->condition('c.codigo', 'II-01')
      ->execute()
      ->fetchField();

    // Contar cursos de Industrial
    $count_cursos = $database->select('cursos', 'cu')
      ->condition('cu.id_carrera', $id_industrial)
      ->countQuery()
      ->execute()
      ->fetchField();

    // Deberían ser 9 cursos (6 del sem1 + 3 del sem2)
    $this->assertEquals(9, $count_cursos, 'Industrial debe tener 9 cursos de prueba');
  }

  /**
   * PRUEBA 4: Verificar cursos por semestre.
   */
  public function testCursosPorSemestre() {
    $database = \Drupal::database();

    $id_industrial = $database->select('carreras', 'c')
      ->fields('c', ['id_carrera'])
      ->condition('c.codigo', 'II-01')
      ->execute()
      ->fetchField();

    $cursos_semestre1 = $database->select('cursos', 'cu')
      ->fields('cu', ['codigo_curso', 'nombre'])
      ->condition('cu.id_carrera', $id_industrial)
      ->condition('cu.semestre', 1)
      ->execute()
      ->fetchAll();

    $this->assertCount(6, $cursos_semestre1, 'Semestre 1 debe tener 6 cursos');
    $this->assertEquals('II101', $cursos_semestre1[0]->codigo_curso);
  }

  /**
   * PRUEBA 5: Probar el método display del controlador (simulado).
   */
  public function testConsultaSimilarAlControlador() {
    $database = \Drupal::database();

    // Esta es la misma consulta que hace tu controlador en display()
    $query = $database->select('carreras', 'c')
      ->fields('c', ['id_carrera', 'nombre', 'codigo', 'duracion_semestres'])
      ->orderBy('c.nombre', 'ASC');

    $result = $query->execute()->fetchAll();

    $this->assertCount(2, $result, 'La consulta debe devolver 2 carreras');
    $this->assertEquals('Administración de Empresas', $result[0]->nombre); // Por orden alfabético
  }

  /**
   * PRUEBA 6: Verificar totales de créditos.
   */
  public function testTotalesCreditos() {
    $database = \Drupal::database();

    $id_industrial = $database->select('carreras', 'c')
      ->fields('c', ['id_carrera'])
      ->condition('c.codigo', 'II-01')
      ->execute()
      ->fetchField();

    $total_creditos = $database->select('cursos', 'cu')
      ->condition('cu.id_carrera', $id_industrial)
      ->fields('cu', ['creditos'])
      ->execute()
      ->fetchCol();

    $suma_creditos = array_sum($total_creditos);

    // Suma de los 9 cursos insertados
    $this->assertEquals(32, $suma_creditos); // 4+4+4+3+3+2 +4+4+4 = 32
  }

}