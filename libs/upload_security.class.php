<?php
// libs/upload_security.class.php — Upload sicuro per DOCUMENTI (zero-trust).
// Validazione MAGIC BYTES (finfo), allowlist MIME, dimensione max, nome HASH.
// dir.15: il codice NON crea cartelle in upload_image; scrive solo il file in
// una cartella gia' esistente.
class UploadSecurity
{
    // MIME reali ammessi -> estensione sicura imposta dal server (non dal client)
    public const DOC_MIME = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
    ];
    public const MAX_BYTES = 15728640; // 15 MB

    // Valida $_FILES[campo] e salva in $destDir. Ritorna esito con nome hash.
    public static function storeDocument(array $file, string $destDir): array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return self::err('Invalid upload.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return self::err('Upload error (code ' . (int)$file['error'] . ').');
        }
        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            return self::err('File empty or too large (max 15 MB).');
        }
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return self::err('Invalid upload source.');
        }
        // MIME reale dai magic bytes, non dall'estensione/Content-Type del client
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = (string)$finfo->file($file['tmp_name']);
        if (!isset(self::DOC_MIME[$mime])) {
            return self::err('File type not allowed (PDF, JPG, PNG, WEBP only).');
        }
        if (!is_dir($destDir) || !is_writable($destDir)) {
            return self::err('Storage folder missing or not writable.');
        }
        $ext   = self::DOC_MIME[$mime];
        $hash  = bin2hex(random_bytes(16)) . '.' . $ext; // nome non indovinabile
        $dest  = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $hash;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return self::err('Could not store the file.');
        }
        @chmod($dest, 0644);
        return [
            'ok'            => true,
            'stored_name'   => $hash,
            'original_name' => self::cleanName((string)($file['name'] ?? 'document')),
            'mime'          => $mime,
            'size'          => $size,
        ];
    }

    private static function cleanName(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^\w.\- ]+/u', '_', $name);
        $name = is_string($name) ? $name : 'document';
        $name = trim($name);
        return $name !== '' ? mb_substr($name, 0, 200) : 'document';
    }

    private static function err(string $m): array
    {
        return ['ok' => false, 'error' => $m];
    }
}
