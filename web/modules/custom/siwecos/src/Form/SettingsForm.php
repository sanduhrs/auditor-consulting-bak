<?php

namespace Drupal\siwecos\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure Siwecos settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'siwecos_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['siwecos.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['register'] = [
      '#markup' => $this->t('To get your personal <a href="@siwecos_info_url" target="_blank">SIWECOS</a> security report, please first <a href="@siwecos_register_url" target="_blank">register your domain at SIWECOS</a>, then provide your credentials and watch <a href="@report_url">your personal security report</a>.', [
        '@siwecos_info_url' => 'https://siwecos.de/',
        '@siwecos_register_url' => 'https://siwecos.de/app#/register',
        '@report_url' => Url::fromRoute('siwecos.report')->toString(),
      ]),
      '#prefix' => '<br />',
      '#suffix' => '<br />',
    ];
    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#default_value' => $this->config('siwecos.settings')->get('domain') ?: parse_url((new Url('<front>'))->setAbsolute()->toString(), PHP_URL_HOST),
      '#disabled' => TRUE,
    ];
    $form['domain_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain token'),
      '#default_value' => $this->config('siwecos.settings')->get('domain_token'),
      '#disabled' => TRUE,
    ];
    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#default_value' => $this->config('siwecos.settings')->get('email'),
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => $this->config('siwecos.settings')->get('password'),
    ];
    $form['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API token'),
      '#default_value' => $this->config('siwecos.settings')->get('api_token'),
      '#disabled' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\siwecos\SiwecosService $siwecosService */
    $siwecosService = \Drupal::service('siwecos.service');
    $response = $siwecosService
      ->setDomain($form_state->getValue('domain'))
      ->setEmail($form_state->getValue('email'))
      ->setPassword($form_state->getValue('password'))
      ->login();

    if (!$response) {
      $form_state->setErrorByName('email', $this->t('The value is not correct.'));
      $form_state->setErrorByName('password', $this->t('The value is not correct.'));
    }
    else {
      if ($domain_token = $siwecosService->registerDomain()) {
        $form_state->setValue('domain_token', $domain_token);
      }
      $form_state->setValue('api_token', $siwecosService->getApiToken());
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('siwecos.settings')
      ->set('domain', $form_state->getValue('domain'))
      ->set('domain_token', $form_state->getValue('domain_token'))
      ->set('email', $form_state->getValue('email'))
      ->set('password', $form_state->getValue('password'))
      ->set('api_token', $form_state->getValue('api_token'))
      ->save();

    // Clear cache for the front page to force update siwecostoken meta tag
    // and enable domain verification.
    $cid = (new Url('<front>'))->setAbsolute()->toString() . ':';
    \Drupal::cache('page')->delete($cid);

    /** @var \Drupal\siwecos\SiwecosService $siwecosService */
    $siwecosService = \Drupal::service('siwecos.service');
    $siwecosService->validateDomain(TRUE);

    parent::submitForm($form, $form_state);
  }

}

