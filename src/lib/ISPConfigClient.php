<?php
declare(strict_types=1);

namespace ISPConfigMonitoring;

use SoapClient;
use SoapFault;
use Exception;

/**
 * ISPConfig API Client Wrapper
 * 
 * Provides a simplified interface for communicating with ISPConfig SOAP API
 * Includes session management, error handling, and retry logic.
 */
class ISPConfigClient
{
    private SoapClient $client;
    private string $username;
    private string $password;
    private ?string $sessionId = null;
    private array $config;
    private int $retryAttempts = 3;
    private int $retryDelay = 2; // seconds

    /**
     * Initialize ISPConfig client
     *
     * @param array $config Configuration array with keys:
     *                      - soap_uri: ISPConfig SOAP endpoint URL
     *                      - soap_location: SOAP location URL
     *                      - username: ISPConfig username
     *                      - password: ISPConfig password
     *                      - verify_ssl: Whether to verify SSL certificates (default: true)
     * @throws ISPConfigException
     */
    public function __construct(array $config)
    {
        $this->validateConfig($config);
        $this->config = $config;
        $this->username = $config['username'];
        $this->password = $config['password'];
        
        $this->initializeSoapClient();
    }

    /**
     * Validate configuration array
     *
     * @param array $config
     * @throws ISPConfigException
     */
    private function validateConfig(array $config): void
    {
        $required = ['soap_uri', 'soap_location', 'username', 'password'];
        
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new ISPConfigException("Missing required configuration: {$key}");
            }
        }

        if (!filter_var($config['soap_uri'], FILTER_VALIDATE_URL)) {
            throw new ISPConfigException("Invalid SOAP URI");
        }
    }

    /**
     * Initialize SOAP client
     *
     * @throws ISPConfigException
     */
    private function initializeSoapClient(): void
    {
        try {
            $soapOptions = [
                'location' => $this->config['soap_location'],
                'uri' => $this->config['soap_uri'],
                'trace' => true,
                'exceptions' => true,
                'keep_alive' => true,
            ];

            // SSL verification settings
            $verifySSL = $this->config['verify_ssl'] ?? true;
            if (!$verifySSL) {
                $soapOptions['stream_context'] = stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ]);
            }

            $this->client = new SoapClient(null, $soapOptions);
        } catch (SoapFault $e) {
            throw new ISPConfigException("Failed to initialize SOAP client: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Login to ISPConfig and get session ID
     *
     * @return string Session ID
     * @throws ISPConfigException
     */
    public function login(): string
    {
        if ($this->sessionId !== null) {
            return $this->sessionId;
        }

        try {
            $this->sessionId = $this->executeWithRetry(function () {
                return $this->client->login($this->username, $this->password);
            });

            if (empty($this->sessionId)) {
                throw new ISPConfigException("Login failed: Empty session ID returned");
            }

            return $this->sessionId;
        } catch (SoapFault $e) {
            throw new ISPConfigException("Login failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Logout from ISPConfig
     *
     * @return bool
     */
    public function logout(): bool
    {
        if ($this->sessionId === null) {
            return true;
        }

        try {
            $this->client->logout($this->sessionId);
            $this->sessionId = null;
            return true;
        } catch (SoapFault $e) {
            error_log("Logout failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all websites from ISPConfig
     *
     * @return array Array of website records
     * @throws ISPConfigException
     */
    public function getWebsites(): array
    {
        $sessionId = $this->login();

        try {
            $websites = $this->executeWithRetry(function () use ($sessionId) {
                return $this->client->sites_web_domain_get($sessionId, ['active' => 'y']);
            });

            return is_array($websites) ? $websites : [];
        } catch (SoapFault $e) {
            throw new ISPConfigException("Failed to get websites: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get a specific website by ID
     *
     * @param int $websiteId
     * @return array|null Website record or null if not found
     * @throws ISPConfigException
     */
    public function getWebsite(int $websiteId): ?array
    {
        $sessionId = $this->login();

        try {
            $website = $this->executeWithRetry(function () use ($sessionId, $websiteId) {
                return $this->client->sites_web_domain_get($sessionId, ['domain_id' => $websiteId]);
            });

            if (is_array($website) && !empty($website)) {
                return $website[0] ?? null;
            }

            return null;
        } catch (SoapFault $e) {
            throw new ISPConfigException("Failed to get website {$websiteId}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute a function with retry logic
     *
     * @param callable $function
     * @return mixed
     * @throws Exception
     */
    private function executeWithRetry(callable $function)
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retryAttempts) {
            try {
                return $function();
            } catch (Exception $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < $this->retryAttempts) {
                    error_log("Attempt {$attempt} failed, retrying in {$this->retryDelay} seconds...");
                    sleep($this->retryDelay);
                    
                    // Reset session ID on authentication errors
                    if (strpos($e->getMessage(), 'session') !== false) {
                        $this->sessionId = null;
                    }
                }
            }
        }

        throw $lastException;
    }

    /**
     * Get website statistics
     *
     * @param int $websiteId
     * @return array Statistics data
     * @throws ISPConfigException
     */
    public function getWebsiteStats(int $websiteId): array
    {
        $sessionId = $this->login();

        try {
            $stats = $this->executeWithRetry(function () use ($sessionId, $websiteId) {
                return $this->client->sites_web_domain_get_stats($sessionId, $websiteId);
            });

            return is_array($stats) ? $stats : [];
        } catch (SoapFault $e) {
            throw new ISPConfigException("Failed to get website stats for {$websiteId}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generic method to call any ISPConfig API function
     *
     * @param string $method SOAP method name
     * @param array $params Method parameters (excluding session ID)
     * @return mixed
     * @throws ISPConfigException
     */
    public function call(string $method, array $params = [])
    {
        $sessionId = $this->login();

        try {
            return $this->executeWithRetry(function () use ($method, $sessionId, $params) {
                array_unshift($params, $sessionId);
                return call_user_func_array([$this->client, $method], $params);
            });
        } catch (SoapFault $e) {
            throw new ISPConfigException("Failed to call {$method}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get all email accounts from ISPConfig
     *
     * @return array Array of email records
     * @throws ISPConfigException
     */
    public function getEmails(): array
    {
        $sessionId = $this->login();

        try {
            $emails = $this->executeWithRetry(function () use ($sessionId) {
                return $this->client->sites_mail_user_get($sessionId, ['active' => 'y']);
            });

            return is_array($emails) ? $emails : [];
        } catch (SoapFault $e) {
            throw new ISPConfigException("Failed to get emails: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get a specific email account by ID
     *
     * @param int $emailId
     * @return array|null Email record or null if not found
     * @throws ISPConfigException
     */
    public function getEmail(int $emailId): ?array
    {
        $sessionId = $this->login();

        try {
            $email = $this->executeWithRetry(function () use ($sessionId, $emailId) {
                return $this->client->sites_mail_user_get($sessionId, ['mailuser_id' => $emailId]);
            });

            if (is_array($email) && !empty($email)) {
                return $email[0] ?? null;
            }

            return null;
        } catch (SoapFault $e) {
            throw new ISPConfigException("Failed to get email {$emailId}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get all mail domains from ISPConfig
     *
     * @return array Array of mail domain records
     * @throws ISPConfigException
     */
    public function getMailDomains(): array
    {
        $sessionId = $this->login();

        try {
            $domains = $this->executeWithRetry(function () use ($sessionId) {
                return $this->client->sites_mail_domain_get($sessionId, ['active' => 'y']);
            });

            return is_array($domains) ? $domains : [];
        } catch (SoapFault $e) {
            throw new ISPConfigException("Failed to get mail domains: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get a specific mail domain by ID
     *
     * @param int $domainId
     * @return array|null Mail domain record or null if not found
     * @throws ISPConfigException
     */
    public function getMailDomain(int $domainId): ?array
    {
        $sessionId = $this->login();

        try {
            $domain = $this->executeWithRetry(function () use ($sessionId, $domainId) {
                return $this->client->sites_mail_domain_get($sessionId, ['mail_domain_id' => $domainId]);
            });

            if (is_array($domain) && !empty($domain)) {
                return $domain[0] ?? null;
            }

            return null;
        } catch (SoapFault $e) {
            throw new ISPConfigException("Failed to get mail domain {$domainId}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get mail domain statistics
     *
     * @param int $domainId
     * @return array Statistics data
     * @throws ISPConfigException
     */
    public function getMailDomainStats(int $domainId): array
    {
        $sessionId = $this->login();

        try {
            $stats = $this->executeWithRetry(function () use ($sessionId, $domainId) {
                return $this->client->sites_mail_domain_get_stats($sessionId, $domainId);
            });

            return is_array($stats) ? $stats : [];
        } catch (SoapFault $e) {
            throw new ISPConfigException("Failed to get mail domain stats for {$domainId}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Destructor - ensure logout
     */
    public function __destruct()
    {
        $this->logout();
    }
}

/**
 * Custom exception class for ISPConfig errors
 */
class ISPConfigException extends Exception
{
}
