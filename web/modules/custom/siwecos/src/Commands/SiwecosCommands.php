<?php

namespace Drupal\siwecos\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class SiwecosCommands extends DrushCommands {

  /**
   * Command description here.
   *
   * @command siwecos:commandName
   * @aliases foo
   */
  public function commandName() {
    /** @var \Drupal\siwecos\SiwecosService $service */
    $service = \Drupal::service('siwecos.service');

    $service
      ->setDomain('auditor.email')
      ->setEmail('stefan@auditor.email')
      ->setPassword('--1TCr0wd+')
      ->setApiToken('5z1GiFJRpSsX0JoQ9IXoPt1MN4Yh1wzTRr1u9q6082');

//    $response = $service->login();
//    echo print_r($response, TRUE), PHP_EOL;

//    $response = $service->getDomains();
//    echo print_r($response, TRUE), PHP_EOL;

    $response = $service->registerDomain();
    echo print_r($response, TRUE), PHP_EOL;

//    $response = $service->getDomains();
//    echo print_r($response, T1RUE), PHP_EOL;

    $response = $service->validateDomain(TRUE);
    echo print_r($response, TRUE), PHP_EOL;

//    $response = $service->verifyDomain();
//    echo print_r($response, TRUE), PHP_EOL;

//    $response = $service->startScan();
//    echo print_r($response, TRUE), PHP_EOL;

//    $response = $service->getScanResult();
//    echo print_r($response, TRUE), PHP_EOL;
  }

  /**
   * An example of the table output format.
   *
   * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @field-labels
   *   group: Group
   *   token: Token
   *   name: Name
   * @default-fields group,token,name
   *
   * @command siwecos:token
   * @aliases token
   *
   * @filter-default-field name
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   */
  public function token($options = ['format' => 'table']) {
    $all = \Drupal::token()->getInfo();
    foreach ($all['tokens'] as $group => $tokens) {
      foreach ($tokens as $key => $token) {
        $rows[] = [
          'group' => $group,
          'token' => $key,
          'name' => $token['name'],
        ];
      }
    }
    return new RowsOfFields($rows);
  }
}
