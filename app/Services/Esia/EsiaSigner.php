<?php

namespace App\Services\Esia;

class EsiaSigner
{
    const PKCS7_FLAG = PKCS7_DETACHED;

    public function __construct(
        protected string  $certPath,
        protected string  $privateKeyPath,
        protected ?string $privateKeyPassword = null,
        protected ?string $tmpPath = null
    )
    {
        $this->certPath = $certPath;
        $this->privateKeyPath = $privateKeyPath;
        $this->privateKeyPassword = $privateKeyPassword;
        $this->tmpPath = $tmpPath ?? sys_get_temp_dir();
    }


    protected function getRandomString(): string
    {
        return md5(uniqid(mt_rand(), true));
    }

    protected function urlSafeBase64EncodeString(string $string): string
    {
        return rtrim(strtr(base64_encode(trim($string)), '+/', '-_'), '=');
    }


    /**
     * @throws \Exception
     */
    public function sign(string $message): string
    {
        $this->checkFilesExists();

        $certContent = file_get_contents($this->certPath);
        $keyContent = file_get_contents($this->privateKeyPath);

        // Get certificate
        try {
            $cert = openssl_x509_read($certContent);
        } catch (\Throwable $throwable) {
            throw new \Exception('Cannot read the certificate: ' . openssl_error_string(), 500);
        }

        if (empty($cert)) {
            throw new \Exception('Cannot read the certificate: ' . openssl_error_string(), 500);
        }

        \Log::debug('Cert: ' . print_r($cert, true), ['cert' => $cert]);


        // Get privateKey
        try {
            $privateKey = openssl_pkey_get_private($keyContent, $this->privateKeyPassword);
        } catch (\Throwable $throwable) {
            throw new \Exception('Cannot read the private key: ' . openssl_error_string(), 500);
        }

        if (empty($privateKey)) {
            throw new \Exception('Cannot read the private key: ' . openssl_error_string(), 500);
        }

        \Log::debug('Private key: : ' . print_r($privateKey, true), ['privateKey' => $privateKey]);


        // directories for sign
        $messageFile = $this->tmpPath . DIRECTORY_SEPARATOR . $this->getRandomString();
        $signFile = $this->tmpPath . DIRECTORY_SEPARATOR . $this->getRandomString();
        file_put_contents($messageFile, $message);

        $signResult = openssl_pkcs7_sign(
            $messageFile,
            $signFile,
            $cert,
            $privateKey,
            [],
            self::PKCS7_FLAG
        );

        if ($signResult) {
            \Log::debug('Sign success');
        } else {
            \Log::error('SSL error: ' . openssl_error_string());
            throw new \Exception('Cannot sign the message', 500);
        }

        $signed = file_get_contents($signFile);

        # split by section
        $signed = explode("\n\n", $signed);

        # get third section which contains sign and join into one line
        $sign = str_replace("\n", '', $signed[3]);

        unlink($signFile);
        unlink($messageFile);

        return $this->urlSafeBase64EncodeString($sign);
    }

    /**
     * @throws \Exception
     */
    protected function checkFilesExists(): void
    {
        if (is_dir($this->certPath) || !file_exists($this->certPath)) {
            throw new \Exception('Certificate does not exist', 500);
        }
        if (!is_readable($this->certPath)) {
            throw new \Exception('Cannot read the certificate', 500);
        }
        if (is_dir($this->privateKeyPath) || !file_exists($this->privateKeyPath)) {
            throw new \Exception('Private key does not exist', 500);
        }
        if (!is_readable($this->privateKeyPath)) {
            throw new \Exception('Cannot read the private key', 500);
        }
        if (!is_dir($this->tmpPath) || !file_exists($this->tmpPath)) {
            throw new \Exception('Temporary folder is not found', 500);
        }
        if (!is_writable($this->tmpPath)) {
            throw new \Exception('Temporary folder is not writable', 500);
        }
    }
}
