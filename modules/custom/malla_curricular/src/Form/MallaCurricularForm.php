<?php

namespace Drupal\malla_curricular\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para la malla curricular.
 */
class MallaCurricularForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'malla_curricular_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Guardar'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Lógica para guardar datos
  }

}