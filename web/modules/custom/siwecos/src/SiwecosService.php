<?php

namespace Drupal\siwecos;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;

/**
 * Siwecos service class.
 *
 * @package Drupal\siwecos
 */
class SiwecosService {

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The API Url.
   *
   * @var string
   */
  protected $apiUrl;

  /**
   * The API Token.
   *
   * @var string
   */
  protected $apiToken;

  /**
   * The email.
   *
   * @var string
   */
  protected $email;

  /**
   * The password.
   *
   * @var string
   */
  protected $password;

  /**
   * The domain.
   *
   * @var string
   */
  protected $domain;

  /**
   * The domain token.
   *
   * @var string
   */
  protected $domainToken;

  /**
   * The verification status.
   *
   * @var bool
   */
  protected $verified;

  /**
   * Constructs a SiwecosService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory object.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client object.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      ClientInterface $http_client
  ) {
    $this->config = $config_factory->get('siwecos.settings');
    $this->httpClient = $http_client;

    $this->apiUrl = $this->config->get('api_url');
    $this->apiToken = $this->config->get('api_token');
    $this->email = $this->config->get('email');
    $this->password = $this->config->get('password');
    $this->domain = $this->config->get('domain');
    $this->domainToken = $this->config->get('domain_token');
  }

  /**
   * Get API Url.
   *
   * @return string
   *   The API Url string.
   */
  public function getApiUrl(): string {
    return $this->apiUrl;
  }

  /**
   * Set API Url.
   *
   * @param string $api_url
   *   The API Url string.
   *
   * @return $this
   */
  public function setApiUrl(string $api_url): self {
    $this->apiUrl = $api_url;
    return $this;
  }

  /**
   * Get API Token.
   *
   * @return string
   *   The API Token string.
   */
  public function getApiToken(): string {
    return $this->apiToken;
  }

  /**
   * Set API Token.
   *
   * @param string $api_token
   *   The API Token string.
   *
   * @return $this
   */
  public function setApiToken(string $api_token): self {
    $this->apiToken = $api_token;
    return $this;
  }

  /**
   * Get email.
   *
   * @return string
   *   The email string.
   */
  public function getEmail(): string {
    return $this->email;
  }

  /**
   * Set email.
   *
   * @param string $email
   *   The email string.
   *
   * @return $this
   */
  public function setEmail(string $email): self {
    $this->email = $email;
    return $this;
  }

  /**
   * Get password.
   *
   * @return string
   *   The password string.
   */
  public function getPassword(): string {
    return $this->password;
  }

  /**
   * Set password.
   *
   * @param string $password
   *   The password string.
   *
   * @return $this
   */
  public function setPassword(string $password): self {
    $this->password = $password;
    return $this;
  }

  /**
   * Get domain.
   *
   * @return string
   *   The domain string.
   */
  public function getDomain(): string {
    return $this->domain;
  }

  /**
   * Set domain.
   *
   * @param string $domain
   *   The domain string.
   *
   * @return $this
   */
  public function setDomain(string $domain): self {
    $this->domain = $domain;
    return $this;
  }

  /**
   * Get domain token.
   *
   * @param bool $update
   *   A boolean whether to update the token from the webservice.
   *
   * @return string
   *   The domain token string.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getDomainToken(bool $update = FALSE): string {
    if ($this->domainToken && !$update) {
      return $this->domainToken;
    }
    else {
      foreach ($this->getDomains() as $domain) {
        if (parse_url($domain->domain, PHP_URL_HOST) === $this->domain) {
          $this->setDomainToken($domain->domainToken);
          return $this->domainToken;
        }
      }
    }
    return '';
  }

  /**
   * Set domain token.
   *
   * @param string $domain_token
   *   The domain token string.
   *
   * @return $this
   */
  public function setDomainToken(string $domain_token): self {
    $this->domainToken = $domain_token;
    return $this;
  }

  /**
   * Login to Siwecos.
   *
   * @return object|false
   *   Return a data object on success or false on error.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function login() {
    try {
      $request = new Request(
        'POST',
        $this->apiUrl . '/users/login',
        [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json;charset=UTF-8',
        ],
        json_encode(['email' => $this->email, 'password' => $this->password])
      );
      $response = $this->httpClient->send($request);
      $object = json_decode($response->getBody()->getContents());

      if (!$object->token) {
        return FALSE;
      }
      $this->setApiToken($object->token);
      return $object->token;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Get registered domains.
   *
   * @return array|false
   *   An array of registered domains.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getDomains() {
    try {
      $request = new Request(
        'POST',
        $this->apiUrl . '/domains/listDomains',
        [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json;charset=UTF-8',
          'userToken' => $this->apiToken,
        ]
      );
      $response = $this->httpClient->send($request);
      $object = json_decode($response->getBody()->getContents());

      if (!$object->domains) {
        return [];
      }
      return $object->domains;
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Validate domain.
   */
  public function validateDomain(bool $scan = FALSE) {
    $domains = $this->getDomains();

    $found = FALSE;
    foreach ($domains as $domain) {
      if ($domain->domain === $this->domain) {
        $found = TRUE;
        $this->verified = $domain->verificationStatus;
        $this->domainToken = $domain->domainToken;
        break;
      }
    }

    if (!$found) {
      $found = $this->registerDomain();
    }

    if ($found && !$this->verified) {
      $this->verified = $this->verifyDomain();

      // New domain => init scan.
      $scan = $scan || $this->verified;
    }

    if ($scan) {
      $this->startScan();
    }
  }

  /**
   * Verify domain.
   *
   * @return bool
   *   Boolean whether verification was successful.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function verifyDomain() {
    try {
      $request = new Request(
        'POST',
        $this->apiUrl . '/domains/verifyDomain',
        [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json;charset=UTF-8',
          'userToken' => $this->apiToken,
        ],
        json_encode(['domain' => 'http://' . $this->domain])
      );
      $response = $this->httpClient->send($request);
      $object = json_decode($response->getBody()->getContents());

      if ($object->message !== 'Page successful validated') {
        return FALSE;
      }
      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Start scan.
   *
   * @return \Psr\Http\Message\ResponseInterface|false
   *   A response object or false.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function startScan() {
    try {
      $request = new Request(
        'POST',
        $this->apiUrl . '/scan/start',
        [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json;charset=UTF-8',
          'userToken' => $this->apiToken,
        ],
        json_encode(['domain' => 'http://' . $this->domain, 'dangerLevel' => 10])
      );
      $response = $this->httpClient->send($request);
      $object = json_decode($response->getBody()->getContents());

      if (!$object->id) {
        return FALSE;
      }
      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Register domain.
   *
   * @return string
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function registerDomain() {
    try {
      $request = new Request(
        'POST',
        $this->apiUrl . '/domains/addNewDomain',
        [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json;charset=UTF-8',
          'userToken' => $this->apiToken,
        ],
        json_encode(['domain' => 'http://' . $this->domain, 'danger_level' => 10])
      );
      $response = $this->httpClient->send($request);
      $object = json_decode($response->getBody()->getContents());

      if (!$object->domainId) {
        return FALSE;
      }
      $this->domainToken = $object->domainToken;
      return $object->domainToken;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Get scan results.
   *
   * @return object|false
   *   Return a data object on success or false on error.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getScanResult() {
    try {
      $request = new Request(
        'GET',
        $this->apiUrl . '/scan/result?domain=' . $this->domain,
        [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json;charset=UTF-8',
          'userToken' => $this->apiToken,
        ]
      );
      $response = $this->httpClient->send($request);
      $object = json_decode($response->getBody()->getContents());

      if (!$object->scanStarted) {
        return FALSE;
      }
      return $object;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

}
