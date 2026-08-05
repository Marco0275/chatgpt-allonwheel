<?php
// ============================================================
// sidebar_rfq_cta.php
// Box "Request a quotation" verso la RFQ della SEZIONE corrente.
//
// 23 lug 2026. La richiesta di preventivo e' divisa per sezione
// (04_request_offer.php?section=road|special|shelter): ogni sezione mostra
// solo le proprie categorie. Le pagine di sezione impostano
// $aow_rfq_section PRIMA di includere la sidebar e qui compare il box che
// porta alla RFQ giusta.
//
// Se $aow_rfq_section non e' impostata il box NON compare: sulle pagine
// generiche la RFQ resta quella completa, raggiungibile da header e footer.
//
// Solo classi CSS esistenti (.sb_box, .more), nessuno stile inline (dir. 8).
// ============================================================

// Difensiva: se la pagina non avesse caricato l'i18n, si carica qui invece di
// interrompere il rendering della sidebar.
if (!function_exists('t')) { require_once __DIR__ . '/config/i18n.php'; }

if (!empty($aow_rfq_section)) {

    $aow_cta_labels = [
        'road'    => 'Road vehicles',
        'special' => 'Special vehicles',
        'shelter' => 'Shelter & Container',
    ];
    $aow_cta_label = $aow_cta_labels[$aow_rfq_section] ?? '';

    if ($aow_cta_label !== '') {
        // La sidebar e' inclusa sia da pagine in radice sia da sottocartelle:
        // con BASE_URL il link e' corretto in entrambi i casi.
        $aow_cta_base = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '';
        ?>
        <div class="sb_box">
          <h3><?php te('rfq.cta_title', 'Request a quotation'); ?></h3>
          <p><?php
            printf(
                htmlspecialchars(
                    t('rfq.cta_text', 'Looking for %s? Tell us what you need and matching suppliers reply to you.'),
                    ENT_QUOTES, 'UTF-8'
                ),
                htmlspecialchars($aow_cta_label, ENT_QUOTES, 'UTF-8')
            );
          ?></p>
          <p>
            <a class="more" href="<?php echo $aow_cta_base; ?>04_request_offer/04_request_offer.php?section=<?php echo urlencode($aow_rfq_section); ?>">
              <?php te('rfq.cta_button', 'Request a quotation'); ?>
            </a>
          </p>
        </div>
        <?php
    }
}
