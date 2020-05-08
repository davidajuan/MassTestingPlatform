<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */
/**
 * DocumentPush Controller
 * Source: https://stackoverflow.com/a/17886252
 *
 * @package Controllers
 */
class DocumentPush extends CI_Controller {
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $params = [
            'host' => Config::HEALTH_NETWORK_SMTP_URL,
            'port' => Config::HEALTH_NETWORK_SMTP_PORT,
            'username' => Config::HEALTH_NETWORK_USER_NAME,
            'password' => Config::HEALTH_NETWORK_USER_PWD,
            'debugLevel' => getenv('EMAIL_LOG_LEVEL') ?? null,
            'debugLogger' => $this->logger ?? null,
        ];
        $this->load->library('secure_email', $params);

        // Don't allow browser
        if(!$this->input->is_cli_request()) {
            $ret = [
                'status' => 'error',
                'message' => 'Missing permissions',
            ];
            echo json_encode($ret) . PHP_EOL;
            die();
        }
    }

    public function index(string $customDate = '') {
        // Manage vars
        $date = new DateTime($customDate);
        $dateDir = Config::DATA_DIR . $date->format('Y-m-d') . '/';

        $heathNetworkAttachment = $dateDir . Config::FILENAME_PATIENT_MASTER;

        $emailSubject = 'Patient Files for Covid19 - Detroit - ' . $date->format('Y-m-d');
        $emailBodyHtml = 'Patient Files for Covid19 - Detroit - ' . $date->format('Y-m-d');
        $emailBodyPlain = $emailBodyHtml;
        $cntFilesSent = 0;

        // Check data dir exists
        if (!file_exists($dateDir)) {
            throw new Exception('Date directory does not exist: ' . $dateDir);
        }

        // Check for the required documents
        if (!file_exists($heathNetworkAttachment)) {
            throw new Exception('File to push does not exist: ' . $heathNetworkAttachment);
        }
        else {
            try {
                $messageId = $this->secure_email->sendEmail(
                    Config::HEALTH_NETWORK_MAIL_FROM, '',
                    Config::HEALTH_NETWORK_MAIL_TO, '',
                    $emailSubject,
                    $emailBodyHtml,
                    $emailBodyPlain,
                    $heathNetworkAttachment
                );
                $this->logger->info(sprintf('File sent to: %s, messageId: %s', Config::HEALTH_NETWORK_MAIL_TO, $messageId));
                $cntFilesSent++;
            } catch (Exception $e) {
                $this->logger->error(sprintf('Failed to send to: %s, Exception: %s', Config::HEALTH_NETWORK_MAIL_TO, $e));
            }
        }

        $message = sprintf('Script finished for %s', $date->format('Y-m-d'));
        $this->logger->notice($message);
        $ret = [
            'status' => 'success',
            'message' => $message,
            'files_sent' => $cntFilesSent
        ];
        echo json_encode($ret) . PHP_EOL;
    }
}
