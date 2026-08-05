<?php
// ============================================================
// libs/site_settings.class.php  Lettura/scrittura impostazioni di sito
//
// 20 lug 2026. Wrapper minimale su site_settings (chiave/valore). Nasce per
// l'immagine hero dell'index, ma vale per qualunque impostazione futura.
//
// Difensiva: se la tabella non esiste ancora (patch non applicata), get()
// restituisce il default e non butta giu' la pagina. Cosi' l'index continua
// a funzionare anche prima della migrazione.
// ============================================================

class SiteSettings
{
    /** Cache di richiesta: si legge il DB una volta sola per chiave. */
    private static array $cache = [];

    /**
     * Valore di un'impostazione, o $default se assente/tabella mancante.
     */
    public static function get(PDO $pdo, string $key, string $default = ''): string
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        try {
            $st = $pdo->prepare('SELECT setting_value FROM `site_settings` WHERE setting_key = :k LIMIT 1');
            $st->execute([':k' => $key]);
            $val = $st->fetchColumn();
            $out = ($val === false) ? $default : (string)$val;
        } catch (PDOException $e) {
            // Tabella non ancora creata o errore: si usa il default.
            error_log('[Allonwheel] SiteSettings::get: ' . $e->getMessage());
            $out = $default;
        }
        self::$cache[$key] = $out;
        return $out;
    }

    /**
     * Salva un'impostazione (UPSERT). Ritorna true se andata a buon fine.
     */
    public static function set(PDO $pdo, string $key, string $value, ?int $adminId = null): bool
    {
        try {
            $st = $pdo->prepare(
                'INSERT INTO `site_settings` (setting_key, setting_value, updated_by)
                 VALUES (:k, :v, :u)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)'
            );
            $ok = $st->execute([':k' => $key, ':v' => $value, ':u' => $adminId]);
            if ($ok) { self::$cache[$key] = $value; }
            return $ok;
        } catch (PDOException $e) {
            error_log('[Allonwheel] SiteSettings::set: ' . $e->getMessage());
            return false;
        }
    }
}
