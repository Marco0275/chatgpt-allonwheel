<?php
// libs/pdf_helper.class.php — Wrapper robusto per mPDF (vendor/autoload.php).
// Se mPDF non e' installato, degrada senza fatal (ritorna false).
class PdfHelper
{
    public static function autoloadPath(): string { return __DIR__ . '/../vendor/autoload.php'; }

    public static function available(): bool
    {
        if (class_exists('\\Mpdf\\Mpdf')) { return true; }
        $a = self::autoloadPath();
        if (is_file($a)) { require_once $a; }
        return class_exists('\\Mpdf\\Mpdf');
    }

    /** Genera il PDF da HTML e lo invia come download. false se mPDF assente o errore. */
    public static function download(string $html, string $filename): bool
    {
        if (!self::available()) { return false; }
        try {
            $tmp = sys_get_temp_dir() . '/aow_mpdf';
            if (!is_dir($tmp)) { @mkdir($tmp, 0775, true); }
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $tmp]);
            $mpdf->WriteHTML($html);
            $mpdf->Output($filename, 'D');
            return true;
        } catch (\Throwable $e) {
            error_log('[Allonwheel] PDF error: ' . $e->getMessage());
            return false;
        }
    }
}
