<?php
namespace App\Logger;

use Psr\Log\LoggerInterface;

class LoggerService
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}