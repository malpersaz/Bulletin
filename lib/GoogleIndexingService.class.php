<?php
/**
 * GoogleIndexingService - Helper to notify Google Search Console of URL updates and deletions
 * 
 * Amed Framework
 * @author Antigravity AI
 */

use Google\Client;
use Google\Service\Indexing;
use Google\Service\Indexing\UrlNotification;

class GoogleIndexingService
{
    private $indexingService = null;
    private $isEnabled = false;

    public function __construct()
    {
        $googleConfig = Registry::get('google_config');
        $credentialsPath = isset($googleConfig['credentials_path']) ? $googleConfig['credentials_path'] : '';

        // Fallback to environment variable if configuration value is not set
        if (empty($credentialsPath)) {
            $credentialsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');
        }

        if (!empty($credentialsPath) && file_exists($credentialsPath)) {
            try {
                $client = new Client();
                $client->setAuthConfig($credentialsPath);
                $client->addScope(Indexing::INDEXING);
                $this->indexingService = new Indexing($client);
                $this->isEnabled = true;
            } catch (Exception $e) {
                trigger_error('Google Indexing API initialization failed: ' . $e->getMessage(), E_USER_WARNING);
            }
        }
    }

    /**
     * Notify Google Search Console that a URL has been added or updated
     * 
     * @param string $url Absolute URL of the page
     * @return bool True if notification was successfully sent
     */
    public function notifyUpdate(string $url): bool
    {
        if (!$this->isEnabled || !$this->indexingService) {
            return false;
        }

        try {
            $urlNotification = new UrlNotification();
            $urlNotification->setUrl($url);
            $urlNotification->setType('URL_UPDATED');
            $this->indexingService->urlNotifications->publish($urlNotification);
            return true;
        } catch (Exception $e) {
            trigger_error('Google Indexing API notifyUpdate failed for ' . $url . ': ' . $e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Notify Google Search Console that a URL has been deleted
     * 
     * @param string $url Absolute URL of the page
     * @return bool True if notification was successfully sent
     */
    public function notifyDelete(string $url): bool
    {
        if (!$this->isEnabled || !$this->indexingService) {
            return false;
        }

        try {
            $urlNotification = new UrlNotification();
            $urlNotification->setUrl($url);
            $urlNotification->setType('URL_DELETED');
            $this->indexingService->urlNotifications->publish($urlNotification);
            return true;
        } catch (Exception $e) {
            trigger_error('Google Indexing API notifyDelete failed for ' . $url . ': ' . $e->getMessage(), E_USER_WARNING);
            return false;
        }
    }
}
