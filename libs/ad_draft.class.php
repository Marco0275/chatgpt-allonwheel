<?php
// ============================================================
// libs/ad_draft.class.php  Bozze annuncio (punto 2)
//
// 17 lug 2026. L'ospite compila il wizard, la bozza si salva qui, l'account
// arriva dopo. Tutta la logica sta in questa classe: i file del wizard
// dovranno solo chiamarla, non contenere regole proprie. Il wizard e' il
// punto piu' fragile del sito (in produzione si e' gia' rotto): meno codice
// gli si mette dentro, meglio e'.
//
// Richiede: sql/Changelog/2026-07-17_ad_drafts.sql
//
// Uso previsto dal wizard:
//   $t = AdDraft::currentToken();              // legge/crea il cookie
//   AdDraft::save($pdo, $t, $payload, 'free', 2, $email);
//   $d = AdDraft::load($pdo, $t);              // riprendere dove si era
//   AdDraft::claim($pdo, $t, $user_id);        // al login: la bozza e' sua
//   $rows = AdDraft::forUser($pdo, $user_id);  // bozze da pubblicare
//   AdDraft::delete($pdo, $id);                // dopo il travaso
// ============================================================

class AdDraft
{
    /** Nome del cookie. httponly: il token e' una credenziale, il JS non lo tocca. */
    const COOKIE = 'aow_draft';

    /** Giorni di vita di una bozza. Oltre, il cron la cancella (GDPR). */
    const TTL_DAYS = 30;

    /**
     * Token della bozza corrente: lo legge dal cookie o ne crea uno nuovo.
     *
     * Il token NON va mai messo in query string: con esso si legge e si
     * pubblica la bozza. In URL finirebbe nei log del server, nei Referer
     * verso terzi e nella cronologia condivisa.
     */
    public static function currentToken(bool $create = true): string
    {
        $tok = (string)($_COOKIE[self::COOKIE] ?? '');
        // Formato atteso: 64 esadecimali. Qualsiasi altra cosa e' spazzatura
        // (o un tentativo di manipolazione): si scarta.
        if (preg_match('/^[a-f0-9]{64}$/', $tok) === 1) {
            return $tok;
        }
        if (!$create) { return ''; }

        try {
            $tok = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // random_bytes fallisce solo se il sistema non ha entropia: in tal
            // caso NON si ripiega su rand(), sarebbe un token indovinabile.
            error_log('[Allonwheel] AdDraft: random_bytes non disponibile: ' . $e->getMessage());
            return '';
        }

        if (!headers_sent()) {
            setcookie(self::COOKIE, $tok, [
                'expires'  => time() + (self::TTL_DAYS * 86400),
                'path'     => '/',
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax', // Lax e non Strict: si torna qui dal link
                                     // di attivazione ricevuto via email.
            ]);
        }
        $_COOKIE[self::COOKIE] = $tok; // utile nella stessa richiesta
        return $tok;
    }

    /**
     * Salva o aggiorna la bozza. Una riga per token (UPSERT).
     * $payload: solo campi di testo del wizard (verificato: quello step non
     * carica file, quindi non c'e' nulla di binario da gestire).
     */
    public static function save(PDO $pdo, string $token, array $payload,
                                string $listing = 'free', int $step = 1,
                                string $contact_email = ''): bool
    {
        if ($token === '') { return false; }
        $listing = ($listing === 'prem') ? 'prem' : 'free';
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            error_log('[Allonwheel] AdDraft::save json_encode: ' . json_last_error_msg());
            return false;
        }
        $exp = date('Y-m-d H:i:s', time() + (self::TTL_DAYS * 86400));

        try {
            // ON DUPLICATE KEY sul token (chiave UNIQUE): l'ospite che torna
            // indietro e riavanza aggiorna la SUA bozza, non ne crea un'altra.
            // expires_at si sposta in avanti a ogni salvataggio: chi sta
            // lavorando non se la vede scadere sotto le mani.
            $st = $pdo->prepare(
                'INSERT INTO `ad_drafts`
                    (draft_token, payload, listing, step, contact_email, expires_at)
                 VALUES (:t, :p, :l, :s, :e, :x)
                 ON DUPLICATE KEY UPDATE
                    payload       = VALUES(payload),
                    listing       = VALUES(listing),
                    step          = VALUES(step),
                    contact_email = VALUES(contact_email),
                    expires_at    = VALUES(expires_at)'
            );
            return $st->execute([
                ':t' => $token, ':p' => $json, ':l' => $listing,
                ':s' => max(1, $step), ':e' => mb_substr($contact_email, 0, 190), ':x' => $exp,
            ]);
        } catch (PDOException $e) {
            error_log('[Allonwheel] AdDraft::save: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Carica la bozza di quel token. Null se non c'e' o e' scaduta.
     * La scadenza si controlla anche qui e non solo nel cron: fra due giri di
     * cron una bozza scaduta non deve tornare a galla.
     */
    public static function load(PDO $pdo, string $token): ?array
    {
        if ($token === '') { return null; }
        try {
            $st = $pdo->prepare(
                'SELECT id, draft_token, user_id, listing, payload, step, contact_email
                   FROM `ad_drafts`
                  WHERE draft_token = :t AND expires_at > NOW()
                  LIMIT 1'
            );
            $st->execute([':t' => $token]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) { return null; }
            $row['payload'] = json_decode((string)$row['payload'], true) ?: [];
            return $row;
        } catch (PDOException $e) {
            error_log('[Allonwheel] AdDraft::load: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Assegna la bozza a un utente (al login/registrazione).
     * Da quel momento e' sua e il token non serve piu'.
     *
     * Si assegna SOLO una bozza ancora senza proprietario: se avesse gia'
     * user_id, un token rubato o riciclato permetterebbe di rubare la bozza
     * di un altro.
     */
    public static function claim(PDO $pdo, string $token, int $user_id): bool
    {
        if ($token === '' || $user_id <= 0) { return false; }
        try {
            $st = $pdo->prepare(
                'UPDATE `ad_drafts`
                    SET user_id = :u
                  WHERE draft_token = :t AND user_id IS NULL AND expires_at > NOW()'
            );
            $st->execute([':u' => $user_id, ':t' => $token]);
            return $st->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[Allonwheel] AdDraft::claim: ' . $e->getMessage());
            return false;
        }
    }

    /** Bozze di un utente, non ancora pubblicate. */
    public static function forUser(PDO $pdo, int $user_id): array
    {
        if ($user_id <= 0) { return []; }
        try {
            $st = $pdo->prepare(
                'SELECT id, listing, payload, step, updated_at
                   FROM `ad_drafts`
                  WHERE user_id = :u AND expires_at > NOW()
                  ORDER BY updated_at DESC'
            );
            $st->execute([':u' => $user_id]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) { $r['payload'] = json_decode((string)$r['payload'], true) ?: []; }
            return $rows;
        } catch (PDOException $e) {
            error_log('[Allonwheel] AdDraft::forUser: ' . $e->getMessage());
            return [];
        }
    }

    /** Cancella la bozza (dopo il travaso nell'annuncio vero) e svuota il cookie. */
    public static function delete(PDO $pdo, int $id, bool $clear_cookie = true): bool
    {
        if ($id <= 0) { return false; }
        try {
            $ok = $pdo->prepare('DELETE FROM `ad_drafts` WHERE id = :i')->execute([':i' => $id]);
            if ($ok && $clear_cookie && !headers_sent()) {
                setcookie(self::COOKIE, '', ['expires' => time() - 3600, 'path' => '/']);
                unset($_COOKIE[self::COOKIE]);
            }
            return (bool)$ok;
        } catch (PDOException $e) {
            error_log('[Allonwheel] AdDraft::delete: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pulizia bozze scadute. La chiama il cron (scripts/purge_personal_data.php).
     * Una bozza di un ospite contiene email e telefono di chi non si e' mai
     * registrato e non ha dato alcun consenso: non puo' restare per sempre.
     *
     * @return int righe rimosse
     */
    public static function purgeExpired(PDO $pdo): int
    {
        try {
            $st = $pdo->prepare('DELETE FROM `ad_drafts` WHERE expires_at < NOW()');
            $st->execute();
            return $st->rowCount();
        } catch (PDOException $e) {
            error_log('[Allonwheel] AdDraft::purgeExpired: ' . $e->getMessage());
            return 0;
        }
    }
}
