<?php
/**
 * SEPA QR Code Generator
 * Generates SEPA payment QR code data for orders
 * Uses SepaQrData library to generate EPC QR code standard data
 */

require_once __DIR__ . '/lib/SepaQrData.php';

use SepaQr\SepaQrData;

class SepaQrCodeGenerator
{
    /**
     * Configuration for SEPA payment
     */
    private $config = array(
        'beneficiary_name' => 'Janssens David',
        'beneficiary_iban' => 'BE11063793878448',
        'beneficiary_bic' => 'GKCCBEBB',
        'currency' => 'EUR'
    );

    public function __construct($config = array())
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * Generate SEPA QR code data for an order
     * Returns the raw SEPA data that can be encoded into a QR code
     *
     * @param int $orderId Order ID
     * @param float $amount Order amount
     * @param string $reference Order reference (e.g., order ID)
     * @return string SEPA QR code data (EPC standard format)
     * @throws Exception
     */
    public function generateQrData($orderId, $amount, $reference = '')
    {
        if (empty($this->config['beneficiary_iban'])) {
            throw new Exception('Beneficiary IBAN is not configured');
        }

        if (empty($this->config['beneficiary_name'])) {
            throw new Exception('Beneficiary name is not configured');
        }

        try {
            $sepaData = new SepaQrData();
            $sepaData->setName($this->config['beneficiary_name'])
                ->setIban($this->config['beneficiary_iban'])
                ->setAmount((float)$amount);

            // Set BIC if configured
            if (!empty($this->config['beneficiary_bic'])) {
                $sepaData->setBic($this->config['beneficiary_bic']);
            }

            // Set currency if not EUR
            if ($this->config['currency'] !== 'EUR') {
                $sepaData->setCurrency($this->config['currency']);
            }

            // Set remittance text with order reference
            if (!empty($reference)) {
                $remittanceText = $reference;
                if (strlen($remittanceText) <= 140) {
                    $sepaData->setRemittanceText($remittanceText);
                }
            }

            return (string)$sepaData;
        } catch (Exception $e) {
            throw new Exception('Failed to generate SEPA QR data: ' . $e->getMessage());
        }
    }

    /**
     * Set configuration
     */
    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * Get configuration
     */
    public function getConfig($key = null)
    {
        if ($key === null) {
            return $this->config;
        }
        return isset($this->config[$key]) ? $this->config[$key] : null;
    }
}

