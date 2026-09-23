<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class OrderScanQrCode
{
    public const CONTENT_ID = 'orderqr';

    public static function pngFor(string $url): string
    {
        if (!class_exists(QrCode::class)) {
            throw new RuntimeException('endroid/qr-code is not available');
        }

        $qrCode = QrCode::create($url)
            ->setSize(300)
            ->setMargin(10);

        return (new PngWriter())->write($qrCode)->getString();
    }
}
