<?php
// ============================================================
// tools/fix_lang_typos.php — correzione una tantum dei refusi nei
// dizionari lang/*.php: accenti mancanti in francese, traslitterazioni
// ae/oe/ue in tedesco, un accento in italiano.
//
// Interviene SOLO sui valori delle chiavi indicate: nessuna chiave viene
// aggiunta, rimossa o rinominata e nessuna logica cambia.
//
//   php tools/fix_lang_typos.php          (applica)
//   php tools/fix_lang_typos.php --dry    (mostra soltanto)
// ============================================================

$dry = in_array('--dry', $argv, true);
$root = dirname(__DIR__);

/** Valori corretti, per lingua e per chiave. */
$fixes = [
    'fr' => [
        'rfq.special_cats'      => 'Carrosseries spéciales',
        'rfq.choose_cat_help'   => 'Choisissez une catégorie : la liste ci-dessous montre seulement ce qui lui appartient.',
        'rfq.choose_cat_all'    => 'Afficher toutes les catégories',
        'rfq.cta_text'          => 'Vous cherchez %s ? Dites-nous ce dont vous avez besoin et les fournisseurs adaptés vous répondront.',
        'rfq.tech_intro'        => "Précisez les caractéristiques techniques de votre demande premium (mêmes champs qu'une annonce premium) :",
        'brand.tagline'         => 'Véhicules paddock motorsport et spéciaux',
        'home.hero_kicker'      => 'Véhicules paddock motorsport et spéciaux',
        'home.vp1_h'            => 'Fournisseurs vérifiés',
        'home.vp1_p'            => 'Un annuaire B2B sélectionné de carrossiers et prestataires.',
        'home.b2b_sub'          => 'Carrosseries routières et spéciales, plus experts et abris.',
        'home.cta_p'            => "Publiez vos véhicules ou inscrivez votre entreprise dans l'annuaire.",
        'nav.account_settings'  => 'Paramètres du compte',
        'nav.logout'            => 'Déconnexion',
        'nav.create_account'    => 'Créer un compte',
        'facet.vtype'           => 'Type de véhicule',
        'facet.special'         => 'Spéciaux',
        'facet.condition'       => 'État',
        'facet.reset'           => 'Réinitialiser',
        'facet.all_cond'        => 'Tous les états',
        'bridge.suppliers_for'  => 'Fournisseurs vérifiés',
        'bridge.suppliers_sub'  => 'Constructeurs et aménageurs pour cette catégorie.',
        'bridge.view_directory' => "Voir l'annuaire complet des fournisseurs",
        'bridge.listings_title' => 'Annonces liées du marketplace',
        'bridge.listings_sub'   => 'Voir les annonces dans les catégories couvertes par ce fournisseur.',
        'dir.certified'         => 'Certifiée',
        'family.empty_tail'     => 'et nous vous mettons en relation avec des constructeurs spécialisés.',
        'ad.details'            => 'Détails',
        'ad.cert_supplier'      => 'Fournisseur certifié',
        'guide.intro'           => 'Votre compte est prêt. Voici ce que vous pouvez faire :',
        'guide.sell'            => 'Vendre un véhicule',
        'guide.sell_d'          => 'publiez une annonce gratuite ou premium en quelques étapes.',
        'guide.buy_d'           => 'vous achetez ? laissez les fournisseurs venir à vous.',
        'guide.profile'         => 'Complétez votre profil',
        'guide.profile_d'       => 'ajoutez vos informations et gérez les paramètres du compte.',
        'guide.hint'            => 'Ce guide disparaît dès que vous publiez votre premier élément. Vous retrouvez ces options dans la barre latérale, sous My account.',
    ],
    'de' => [
        'rfq.choose_cat_help'   => 'Wählen Sie eine Kategorie: unten erscheint nur, was dazu gehört.',
        'home.vp1_h'            => 'Geprüfte Lieferanten',
        'nav.delete_account'    => 'Konto löschen',
        'facet.reset'           => 'Zurücksetzen',
        'facet.all_cond'        => 'Alle Zustände',
        'facet.price_max'       => 'Höchstpreis (EUR)',
        'facet.len_min'         => 'Min. Länge (m)',
        'facet.len_max'         => 'Max. Länge (m)',
        'facet.clear'           => 'Filter zurücksetzen',
        'bridge.suppliers_for'  => 'Geprüfte Anbieter',
        'bridge.suppliers_sub'  => 'Hersteller und Ausstatter für diese Kategorie.',
        'bridge.view_directory' => 'Zum vollständigen Anbieterverzeichnis',
        'bridge.listings_title' => 'Zugehörige Marktplatz-Anzeigen',
        'search.clear'          => 'Löschen',
        'guide.intro'           => 'Ihr Konto ist bereit. Das können Sie hier tun:',
        'guide.buy_d'           => 'Sie möchten kaufen? Lassen Sie die Lieferanten zu Ihnen kommen.',
        'guide.profile'         => 'Profil vervollständigen',
        'guide.profile_d'       => 'fügen Sie Ihre Daten hinzu und verwalten Sie die Kontoeinstellungen.',
        'guide.hint'            => 'Dieser Leitfaden verschwindet, sobald Sie Ihr erstes Element veröffentlichen. Diese Optionen finden Sie in der Seitenleiste unter My account.',
    ],
    'it' => [
        'home.b2b_sub'          => 'Allestimenti road e speciali, più esperti e shelter.',
    ],
];

$total = 0;
foreach ($fixes as $lang => $map) {
    $file = $root . '/lang/' . $lang . '.php';
    $src  = file_get_contents($file);
    if ($src === false) { fwrite(STDERR, "impossibile leggere $file\n"); exit(1); }
    $done = 0;

    foreach ($map as $key => $value) {
        // Il valore nuovo va scritto con le stesse convenzioni del file:
        // apici singoli, apostrofi e backslash con escape.
        $php = "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
        $re  = "/('" . preg_quote($key, '/') . "'\s*=>\s*)'(?:[^'\\\\]|\\\\.)*'/";
        $new = preg_replace($re, '$1' . str_replace('$', '\\$', $php), $src, 1, $n);
        if ($new === null) { fwrite(STDERR, "regex fallita su $lang / $key\n"); exit(1); }
        if ($n !== 1) { fwrite(STDERR, "chiave non trovata: $lang / $key\n"); exit(1); }
        $src = $new;
        $done++;
    }

    // La sintassi deve restare valida e il dizionario deve contenere le
    // stesse chiavi di prima: se non e' cosi', non si scrive nulla.
    $before = array_keys((array)include $file);
    $tmp = tempnam(sys_get_temp_dir(), 'lang');
    file_put_contents($tmp, $src);
    $after = @include $tmp;
    unlink($tmp);
    if (!is_array($after) || array_keys($after) !== $before) {
        fwrite(STDERR, "verifica chiavi fallita su $lang: nessuna scrittura\n");
        exit(1);
    }

    if (!$dry) { file_put_contents($file, $src); }
    echo "lang/$lang.php: $done voci corrette", $dry ? ' (dry run)' : '', "\n";
    $total += $done;
}
echo "totale: $total refusi\n";
