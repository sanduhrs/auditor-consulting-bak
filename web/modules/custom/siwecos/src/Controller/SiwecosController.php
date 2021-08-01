<?php

namespace Drupal\siwecos\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\siwecos\SiwecosService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Siwecos routes.
 */
class SiwecosController extends ControllerBase {

  // @see https://www.ralfarbpalette.de/ral-classic/ral-6024-verkehrsgrun
  const RGBA_GREEN = '0,131,81,1';

  // @see https://www.ralfarbpalette.de/ral-classic/ral-1023-verkehrsgelb
  const RGBA_YELLOW = '247,181,0,1';

  // @see https://www.ralfarbpalette.de/ral-classic/ral-3020-verkehrsrot
  const RGBA_RED = '187,30,16,1';

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The siwecos.service service.
   *
   * @var \Drupal\siwecos\SiwecosService
   */
  protected $siwecosService;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\siwecos\SiwecosService $siwecos_service
   *   The siwecos service.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      SiwecosService $siwecos_service
  ) {
    $this->config = $config_factory->get('siwecos.settings');
    $this->siwecosService = $siwecos_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('siwecos.service')
    );
  }

  /**
   * Builds the response.
   */
  public function build() {
    $result = $this->siwecosService->getScanResult();

    $build['header']['seal'] = [
      '#type' => 'inline_template',
      '#template' => '<a style="float: right;" href="https://siwecos.de/scanned-by-siwecos/?data-siwecos={{ domain }}"><svg width="150" height="58" id="siwecos-seal" data-format="{{ format }}"/></a>',
      '#context' => [
        'domain' => $this->config->get('domain'),
        'format' => 'd.m.y',
      ],
      '#attached' => [
        'library' => [
          'siwecos/seal',
        ],
      ],
    ];

    list($total_score, $scanner_count) = array_values(
      self::getScannerStats($result->scanners ?? [])
    );

    $build['header']['score'] = [
      '#type' => 'inline_template',
      '#template' => <<<EOF
<div class="siwecos__score">
  <div class="siwecos__circle-progress" style="float: left;margin-right: 2em;" data-size="50" data-value="{{ (total_score/100) }}" data-fill="{&quot;color&quot;: &quot;rgba({{ rgba }})&quot;}"><strong>{{ total_score }}<i>%</i></strong></div>
  <h2>{{ domain }}</h2>
  <div>{{ date_start|format_date }}</div>
  <div>
    <a href="https://siwecos.de/en/support/total-score" target="_blank">{{ "What does the SIWECOS Score mean?"|trans }}</a>
  </div>
</div>
EOF,
      '#context' => [
        'domain' => $this->config->get('domain') ?: parse_url((new Url('<front>'))->setAbsolute()->toString(), PHP_URL_HOST),
        'date_start' => isset($result->scanStarted->date) ? strtotime($result->scanStarted->date) : 0,
        'date_end' => isset($result->scanFinished->date) ? strtotime($result->scanFinished->date) : 0,
        'total_score' => $total_score,
        'rgba' => self::getRgba($total_score),
      ],
      '#attached' => [
        'library' => [
          'siwecos/siwecos',
        ],
      ],
    ];

    if (!$result) {
      $build['register'] = [
        '#markup' => $this->t('To get your personal <a href="@siwecos_info_url" target="_blank">SIWECOS</a> security report, please first <a href="@siwecos_register_url" target="_blank">register yourself at SIWECOS</a>, then got to the <a href="@settings_url">settings page</a> and provide your credentials.', [
          '@siwecos_info_url' => 'https://siwecos.de/',
          '@siwecos_register_url' => 'https://siwecos.de/app#/register',
          '@settings_url' => Url::fromRoute('siwecos.settings_form')->toString(),
        ]),
        '#prefix' => '<br />',
        '#suffix' => '<br />',
      ];
      return $build;
    }

    foreach ($result->scanners as $key => $scanner) {
      $build['scanner-fieldset-' . $key] = [
        '#type' => 'fieldset',
        '#title' => $scanner->scanner_type,
        '#markup' => $this->t('Last scan: Fri, 07/30/2021 - 11:20'),
      ];

      foreach ($scanner->result as $item_id => $item) {
        $build['scanner-fieldset-' . $key][$item_id . '-name'] = [
          '#type' => 'details',
          '#title' => '<span class="siwecos__status-icon siwecos__status-icon--' . $item->scoreTypeRaw . '"></span>' . strip_tags($item->name),
          '#attached' => [
            'library' => [
              'siwecos/siwecos',
            ],
          ],
        ];
        $build['scanner-fieldset-' . $key][$item_id . '-name']['report'] = [
          '#type' => 'inline_template',
          '#template' => <<<EOF
<strong>{{ description|striptags }}</strong>
<ul>
  {% for details in test_details %}
  <li>{{ details.report|striptags }}</li>
  {% endfor %}
</ul>
<a href="{{ link }}" target="_blank">{{ 'More information'|trans }}</a>
EOF,
          '#context' => [
            'name' => $item->name,
            'has_error' => (bool) $item->hasError,
            'error_message' => $item->errorMessage,
            'score' => $item->score,
            'test_details' => $item->testDetails,
            'link' => $item->link,
            'description' => $item->description,
            'report' => $item->report,
            'score_type_raw' => $item->scoreTypeRaw,
          ],
        ];
      }
    }
    return $build;
  }

  /**
   * Get an RGBA value for a score.
   *
   * @param int $score
   *   An integer between 0 and 100.
   *
   * @return string
   *   An RGBA formatted string.
   */
  public static function getRgba($score) {
    if ($score >= 75) {
      return self::RGBA_GREEN;
    }
    elseif ($score >= 50) {
      return self::RGBA_YELLOW;
    }
    return self::RGBA_RED;
  }

  /**
   * Get an raw score type for a score.
   *
   * @param int $score
   *   An integer between 0 and 100.
   *
   * @return string
   *   An raw score string.
   */
  public static function getRequirementSeverity($score) {
    if ($score >= 75) {
      return REQUIREMENT_OK;
    }
    elseif ($score >= 50) {
      return REQUIREMENT_WARNING;
    }
    return REQUIREMENT_ERROR;
  }

  /**
   * Get an raw score type for a score.
   *
   * @param int $score
   *   An integer between 0 and 100.
   *
   * @return string
   *   An raw score string.
   */
  public static function getRequirementSeverityDescription($score) {
    if ($score >= 75) {
      return t('Most SIWECOS tests are passing.');
    }
    elseif ($score >= 50) {
      return t('Only some SIWECOS tests are passing.');
    }
    return t('Just a few or none SIWECOS tests are passing.');
  }

  /**
   * Get scanner statistics.
   *
   * @param array $scanners
   *   The scanners array.
   *
   * @return array
   *   An associative array with total_score and scanner_count values.
   */
  public static function getScannerStats(array $scanners): array {
    $score = 0;
    $scanner_count = 0;
    foreach ($scanners as $scanner) {
      $score += $scanner->score;
      $scanner_count++;
    }
    $total_score = round($score / ($scanner_count ?: 1));
    return [
      'total_score' => $total_score,
      'scanner_count' => $scanner_count,
    ];
  }

}
