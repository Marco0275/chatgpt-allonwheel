<?php
// includes/form_consent.php  (cartella di destinazione: /includes/)
// P0.3 - Consenso privacy sui form che raccolgono dati personali.
//  - Checkbox OBBLIGATORIA e NON pre-selezionata (Art. 7 GDPR).
//  - Registro della prova nella tabella `consent_log` (stesso schema dei cookie),
//    con timestamp, IP pseudonimizzato (SHA-256), versione testo e nome del form.
//
// Versione dell'informativa accettata: aggiornala quando cambi privacy.php.
if (!defined('AOW_PRIVACY_VERSION')) { define('AOW_PRIVACY_VERSION', '2026-01'); }

/**
 * HTML della checkbox di consenso, da inserire nel form (vicino al submit).
 * $privacyUrl: percorso all'informativa (assoluto dalla radice).
 */
function aow_privacy_consent_field(string $privacyUrl = '/privacy.php'): string
{
    $u   = htmlspecialchars($privacyUrl, ENT_QUOTES, 'UTF-8');
    $tpl = function_exists('t')
        ? t('consent.privacy', 'I have read the <a href="%s" target="_blank" rel="noopener">privacy policy</a> and consent to the processing of my personal data to respond to my request.')
        : 'I have read the <a href="%s" target="_blank" rel="noopener">privacy policy</a> and consent to the processing of my personal data to respond to my request.';
    $txt = sprintf($tpl, $u);
    $ver = htmlspecialchars(AOW_PRIVACY_VERSION, ENT_QUOTES, 'UTF-8');

    return
        '<p class="privacy-consent" style="margin:10px 0">'
      . '<label style="font-weight:normal">'
      . '<input type="checkbox" name="privacy_consent" value="1" required aria-required="true" /> '
      . $txt
      . '</label>'
      . '<input type="hidden" name="privacy_version" value="' . $ver . '" />'
      . '</p>';
}

/** True se la checkbox di consenso e' spuntata. */
function aow_privacy_consent_ok(): bool
{
    return !empty($_POST['privacy_consent']);
}

/**
 * Registra la prova del consenso nella tabella `consent_log`.
 * $form: nome del form (es. 'contact', 'quote_request', 'wanted', 'rent_request').
 */
function aow_log_form_consent(PDO $pdo, string $form): void
{
    $ip      = $_SERVER['REMOTE_ADDR'] ?? '';
    $salt    = defined('AOW_CONSENT_SALT') ? AOW_CONSENT_SALT : 'aow-salt';
    $ip_hash = hash('sha256', $ip . '|' . $salt);
    $ua      = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $ver     = substr((string)($_POST['privacy_version'] ?? AOW_PRIVACY_VERSION), 0, 20);
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO consent_log (consent_id, ip_hash, user_agent, categories, consent_version, action, form)
             VALUES (UUID(), :ip, :ua, :cats, :ver, :act, :form)'
        );
        $stmt->execute([
            ':ip'   => $ip_hash,
            ':ua'   => $ua,
            ':cats' => json_encode(['form_consent' => true], JSON_UNESCAPED_UNICODE),
            ':ver'  => $ver,
            ':act'  => 'grant',
            ':form' => substr($form, 0, 40),
        ]);
    } catch (Throwable $e) {
        error_log('[Allonwheel] form_consent: ' . $e->getMessage());
    }
}
