<?php
// libs/ads_documents.class.php — Modello documenti tecnici annuncio (PDO+prepared).
// Annunci in DUE tabelle (02_free_ads/03_ads): discriminatore `ad_table`,
// niente FK cross-table, integrita' verificata a livello applicativo.
require_once __DIR__ . '/upload_security.class.php';

class AdsDocuments
{
    public const TABLES = ['02_free_ads', '03_ads'];
    public const TYPES  = ['technical_sheet', 'floorplan', 'certificate', 'manual', 'other'];

    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    // Cartella fisica (gia' esistente) dei documenti — convenzione upload_image/<sezione>/
    public static function storageDir(): string
    {
        return dirname(__DIR__) . '/upload_image/ads_documents/';
    }
    public static function isTable(string $t): bool { return in_array($t, self::TABLES, true); }
    public static function isType(string $t): bool  { return in_array($t, self::TYPES, true); }

    public function add(int $idAds, string $adTable, string $type, string $stored,
                        string $original, ?string $mime, ?int $size, ?int $uploadedBy): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO `ads_documents`
                (id_ads, ad_table, document_type, file_name, original_name, mime, size_bytes, uploaded_by)
             VALUES (:a, :t, :d, :f, :o, :m, :s, :u)'
        );
        $st->execute([
            ':a' => $idAds, ':t' => $adTable, ':d' => $type, ':f' => $stored,
            ':o' => $original, ':m' => $mime, ':s' => $size, ':u' => $uploadedBy,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function listByAd(int $idAds, string $adTable): array
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM `ads_documents` WHERE id_ads = :a AND ad_table = :t ORDER BY uploaded_at DESC'
        );
        $st->execute([':a' => $idAds, ':t' => $adTable]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM `ads_documents` WHERE id = :id LIMIT 1');
        $st->execute([':id' => $id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    // L'annuncio del documento appartiene a $userId? (anti-IDOR)
    public function ownsAd(int $idAds, string $adTable, int $userId): bool
    {
        if (!self::isTable($adTable)) return false;
        $st = $this->pdo->prepare(
            sprintf('SELECT 1 FROM `%s` WHERE id_ads = :a AND id_user = :u LIMIT 1', $adTable)
        );
        $st->execute([':a' => $idAds, ':u' => $userId]);
        return (bool)$st->fetchColumn();
    }

    // L'annuncio e' pubblicamente visibile (status approvato)?
    public function adIsPublic(int $idAds, string $adTable): bool
    {
        if (!self::isTable($adTable)) return false;
        $st = $this->pdo->prepare(
            sprintf("SELECT 1 FROM `%s` WHERE id_ads = :a AND status = 'approved' LIMIT 1", $adTable)
        );
        $st->execute([':a' => $idAds]);
        return (bool)$st->fetchColumn();
    }

    // Elimina solo se proprietario; ritorna file_name (per unlink) oppure null.
    public function deleteOwned(int $id, int $userId): ?string
    {
        $doc = $this->get($id);
        if (!$doc) return null;
        if (!$this->ownsAd((int)$doc['id_ads'], (string)$doc['ad_table'], $userId)) return null;
        $this->pdo->prepare('DELETE FROM `ads_documents` WHERE id = :id')->execute([':id' => $id]);
        return (string)$doc['file_name'];
    }

    // Log download (chi/quando, IP hash GDPR)
    public function logDownload(int $idDoc, ?int $userId, ?string $ipHash): void
    {
        $this->pdo->prepare(
            'INSERT INTO `ads_document_downloads` (id_document, id_user, ip_hash) VALUES (:d, :u, :h)'
        )->execute([':d' => $idDoc, ':u' => $userId, ':h' => $ipHash]);
    }

    // Incrementa il contatore download nelle statistiche venditore (upsert)
    public function bumpPdfDownloads(int $idAds, string $adTable): void
    {
        if (!self::isTable($adTable)) return;
        $this->pdo->prepare(
            'INSERT INTO `seller_statistics` (id_ads, ad_table, pdf_downloads)
             VALUES (:a, :t, 1)
             ON DUPLICATE KEY UPDATE pdf_downloads = pdf_downloads + 1'
        )->execute([':a' => $idAds, ':t' => $adTable]);
    }
}
