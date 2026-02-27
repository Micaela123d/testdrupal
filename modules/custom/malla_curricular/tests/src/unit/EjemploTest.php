<?php
namespace Drupal\Tests\malla_curricular\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @group malla_curricular
 */
class EjemploTest extends UnitTestCase {

  /**
   * Prueba simple que NO necesita el contenedor.
   */
  public function testFuncionamientoBasico() {
    // ✅ Esto SÍ funciona en pruebas unitarias
    $verdad = TRUE;
    $this->assertTrue($verdad, 'Esto siempre debería pasar');
  }

  /**
   * Prueba de matemáticas simple.
   */
  public function testMatematicas() {
    $suma = 2 + 2;
    $this->assertEquals(4, $suma);
  }

}