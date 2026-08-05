<?php
// ============================================================
// libs/vehicle_taxonomy.class.php
// Tassonomia veicoli unificata Road / Special (flowchart, dir. 18).
//
// Punto UNICO in cui e' definita la macro-classificazione dei vtype:
//  - Road    = elenco CHIUSO di 24 slug (vehicle_types.slug)
//  - Special = complemento (tutti gli altri vtype realmente presenti)
//            + il ramo "Shelter / Container" (item_kind dedicato)
//
// Le etichette EN sono allineate a CompanyManager::$products, con le
// rinomine del piano gia' applicate:
//  - autonegozi_alimentari  -> "Street food"   (ex Street Food)
//  - autonegozi_mercerie    -> "Haberdashery"  (ex Haberdashery)
//
// NB: lo SLUG resta stabile (dir. 9). Cambia solo la label visibile.
// Quando la migrazione DB e' applicata (vehicle_types.macro_category),
// typesByMacro() legge direttamente dal DB (dir. 14: solo dati reali).
// Senza migrazione, usa la mappa statica come fallback non distruttivo.
// ============================================================

class VehicleTaxonomy
{
    // Macro-categorie
    const MACRO_ROAD    = 'road';
    const MACRO_SPECIAL = 'special';

    // Tipi di oggetto (primo step del wizard di inserimento)
    const KIND_VEHICLE = 'vehicle';
    const KIND_SHELTER = 'shelter_container';

    // Pseudo-slug usato quando l'annuncio e' uno Shelter / Container
    const SHELTER_SLUG = 'shelter_container';

    // ------------------------------------------------------------
    // Elenco CHIUSO Road (24 slug). Fonte: piano Parte 0-bis.
    // ------------------------------------------------------------
    public static $road = [
        'ambulanze',
        'autonegozi_alimentari',
        'autonegozi_mercerie',
        'blindati',
        'carrattrezzi',
        'cassoni',
        'centinati',
        'coibentati',
        'disabili',
        'forze_dell_ordine',
        'frigoriferi',
        'furgonature_box',
        'isotermici',
        'minibus',
        'officine_mobili',
        'piattaforme_aeree_gru',
        'pubblica_amministrazione',
        'scuolabus',
        'servizi_ecologici',
        'sistemi_di_sollevamento',
        'tempo_libero',
        'trasporto_abiti',
        'trasporto_animali',
        'vvf_protezione_civile',
    ];

    // ------------------------------------------------------------
    // Mappa statica slug -> label EN (fallback se manca la migrazione).
    // Allineata a CompanyManager::$products + rinomine del piano.
    // ------------------------------------------------------------
    public static $labels = [
        'ambulanze'                => 'Ambulances',
        'autonegozi_alimentari'    => 'Street food',
        'autonegozi_mercerie'      => 'Haberdashery',
        'blindati'                 => 'Armored',
        'carrattrezzi'             => 'Tow trucks',
        'cassoni'                  => 'Tippers',
        'centinati'                => 'Curtain-side bodies',
        'coibentati'               => 'Insulated bodies',
        'disabili'                 => 'Disabled access vehicles',
        'forze_dell_ordine'        => 'Law enforcement',
        'frigoriferi'              => 'Refrigerated bodies',
        'furgonature_box'          => 'Box vans',
        'isotermici'               => 'Isothermal bodies',
        'minibus'                  => 'Minibuses',
        'officine_mobili'          => 'Mobile workshops',
        'piattaforme_aeree_gru'    => 'Aerial platforms / Cranes',
        'pubblica_amministrazione' => 'Public administration',
        'scuolabus'                => 'School buses',
        'servizi_ecologici'        => 'Waste collection vehicles',
        'sistemi_di_sollevamento'  => 'Lifting systems',
        'tempo_libero'             => 'Leisure',
        'trasporto_abiti'          => 'Garment transport',
        'trasporto_animali'        => 'Animal transport',
        'vvf_protezione_civile'    => 'Fire dept. / Civil protection',
        // --- Special (complemento) ---
        'camper'                   => 'Motorhomes',
        'laboratori_medici_mobili' => 'Mobile medical labs',
        'uffici_mobili'            => 'Mobile offices',
    ];

    /** True se lo slug appartiene all'elenco chiuso Road. */
    public static function isRoad(string $slug): bool
    {
        return in_array($slug, self::$road, true);
    }

    /** Macro-categoria di uno slug: 'road' se nell'elenco chiuso, altrimenti 'special'. */
    public static function macroForSlug(string $slug): string
    {
        return self::isRoad($slug) ? self::MACRO_ROAD : self::MACRO_SPECIAL;
    }

    /**
     * Label EN di uno slug. DB-aware (24 lug 2026): se disponibile il PDO,
     * il nome REALE viene letto dalle tabelle (prima vehicle_types per i road,
     * poi special_types per special/shelter), cosi' anche i tipi speciali
     * curati dall'admin mostrano il loro nome e non un "indovinato" dallo slug.
     * Senza PDO (o slug non trovato) si ricade sulla mappa statica.
     */
    public static function label(string $slug, ?PDO $pdo = null): string
    {
        if ($slug === self::SHELTER_SLUG) {
            return 'Shelter / Container';
        }
        if ($pdo instanceof PDO) {
            try {
                foreach (['vehicle_types', 'special_types'] as $t) {
                    $st = $pdo->prepare("SELECT name FROM `{$t}` WHERE slug = :s LIMIT 1");
                    $st->execute([':s' => $slug]);
                    $name = $st->fetchColumn();
                    if ($name !== false && $name !== null && $name !== '') {
                        return (string)$name;
                    }
                }
            } catch (Throwable $e) {
                error_log('[Allonwheel] label(' . $slug . '): ' . $e->getMessage());
            }
        }
        return self::$labels[$slug] ?? ucwords(str_replace('_', ' ', $slug));
    }

    /**
     * Elenco [slug => label] dei tipi di una macro-categoria.
     * Preferisce il DB (vehicle_types.macro_category) se la colonna esiste
     * (dir. 14: solo dati realmente presenti). Altrimenti usa la mappa statica.
     *
     * @param PDO|null $pdo Connessione PDO opzionale.
     */
    // =================================================================
    // NUOVA TASSONOMIA (24 lug 2026) - sostituisce ogni regola precedente
    //
    //   ROAD     -> tabella `vehicle_types`
    //               la lista estratta dal CODICE DELLA STRADA italiano:
    //               elenco chiuso, di riferimento, non curato a mano.
    //   SPECIAL  -> tabella `special_types`
    //               la lista curata dall'AMMINISTRATORE. Puo' contenere voci
    //               scritte a mano e voci duplicate da vehicle_types (una
    //               tipologia stradale che esiste anche come allestimento
    //               speciale).
    //   SHELTER  -> la STESSA `special_types`: uno shelter e' un allestimento
    //               speciale costruito su container invece che su un veicolo.
    //
    // Da qui in avanti nessun file deve decidere da solo dove pescare le
    // tipologie: si chiede a questi due metodi.
    // =================================================================

    const CAT_ROAD    = 'road';
    const CAT_SPECIAL = 'special';
    const CAT_SHELTER = 'shelter';

    /** La tabella che contiene le tipologie della categoria indicata. */
    public static function tableForCategory(string $category): string
    {
        return ($category === self::CAT_ROAD) ? 'vehicle_types' : 'special_types';
    }

    /**
     * Tipologie disponibili per una categoria (road | special | shelter).
     * Ritorna [ ['slug'=>..., 'name'=>...], ... ] ordinate per la UI.
     * In caso di errore ritorna array vuoto: la pagina non deve rompersi.
     */
public static function typesForCategory(string $category, PDO $pdo): array
{
    $table = self::tableForCategory($category);

    try {

        $st = $pdo->query("
            SELECT slug, name
            FROM `{$table}`
            ORDER BY sort_order, name
        ");

        if (!$st) {
            return [];
        }

        $rows = [];

        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {

            $rows[] = [
                'slug' => (string)$r['slug'],
                'name' => (string)$r['name']
            ];

        }

        return $rows;

    } catch (Throwable $e) {

        error_log(
            '[Allonwheel] typesForCategory(' . $category . '): ' .
            $e->getMessage()
        );

        return [];
    }
}

    /**
     * Lo slug appartiene davvero alla categoria indicata?
     * Serve a non fidarsi di cio' che arriva dal POST: si controlla contro
     * la tabella giusta, non contro un elenco in memoria.
     */
    public static function isValidForCategory(string $slug, string $category, PDO $pdo): bool
    {
        if ($slug === '') { return false; }
        // "On demand" e' una scelta legittima ovunque: e' la via d'uscita per
        // chi non si riconosce negli elenchi.
        if ($slug === self::ON_DEMAND_SLUG) { return true; }
        $table = self::tableForCategory($category);
        try {
            $st = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE slug = :s");
            $st->execute([':s' => $slug]);
            return (int)$st->fetchColumn() > 0;
        } catch (Throwable $e) {
            error_log('[Allonwheel] isValidForCategory: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Tutte le tipologie, gia' divise per categoria, in UNA chiamata.
     * Serve alle pagine che devono mostrarle insieme (browse, ricerca in
     * sidebar) senza doversi ricordare da quale tabella arriva cosa.
     *
     * Ritorna ['road' => [...], 'special' => [...]] con righe
     * ['slug'=>..., 'name'=>...]. Lo shelter condivide la lista special.
     */
    public static function allTypesGrouped(PDO $pdo): array
    {
        return [
            self::CAT_ROAD    => self::typesForCategory(self::CAT_ROAD, $pdo),
            self::CAT_SPECIAL => self::typesForCategory(self::CAT_SPECIAL, $pdo),
        ];
    }

    /**
     * La categoria di uno slug: dove sta, road o special?
     * Utile a chi ha in mano solo il vehicle_type di un annuncio.
     * Ritorna '' se lo slug non esiste in nessuna delle due liste.
     */
    public static function categoryOfSlug(string $slug, PDO $pdo): string
    {
        if ($slug === '') { return ''; }
        try {
            $st = $pdo->prepare('SELECT 1 FROM `vehicle_types` WHERE slug = :s LIMIT 1');
            $st->execute([':s' => $slug]);
            if ($st->fetchColumn()) { return self::CAT_ROAD; }
            $st = $pdo->prepare('SELECT 1 FROM `special_types` WHERE slug = :s LIMIT 1');
            $st->execute([':s' => $slug]);
            if ($st->fetchColumn()) { return self::CAT_SPECIAL; }
        } catch (Throwable $e) {
            error_log('[Allonwheel] categoryOfSlug: ' . $e->getMessage());
        }
        return '';
    }

    /** Slug della voce "On demand": richiesta fuori catalogo. */
    const ON_DEMAND_SLUG = 'on_demand';

    public static function typesByMacro(string $macro, ?PDO $pdo = null): array
    {
        $macro = ($macro === self::MACRO_ROAD) ? self::MACRO_ROAD : self::MACRO_SPECIAL;

        // 1) Tentativo data-driven dal DB.
        //    FIX 2026-07-24: dalla migrazione ...c le tipologie SPECIAL non
        //    stanno piu' in vehicle_types (macro_category='special') ma nella
        //    tabella dedicata special_types. Interrogare qui vehicle_types
        //    restituiva 0 righe per gli special, facendo poi fallire
        //    isValidType() per ogni tipo speciale non hard-coded nella mappa
        //    statica: gli annunci Special/Premium venivano rifiutati.
        //    Si riusa typesForCategory(), che sceglie da sola la tabella
        //    giusta (vehicle_types per road, special_types per special).
        if ($pdo instanceof PDO) {
            $category = ($macro === self::MACRO_ROAD) ? self::CAT_ROAD : self::CAT_SPECIAL;
            $rows = self::typesForCategory($category, $pdo); // [ ['slug'=>..,'name'=>..], .. ]
            if (!empty($rows)) {
                $out = [];
                foreach ($rows as $r) {
                    $out[(string)$r['slug']] = (string)$r['name'];
                }
                return $out; // [slug => name]
            }
        }

        // 2) Fallback statico (pre-migrazione)
        $out = [];
        foreach (self::$labels as $slug => $label) {
            if (self::macroForSlug($slug) === $macro) {
                $out[$slug] = $label;
            }
        }
        asort($out);
        return $out;
    }

    /** Verifica che $slug sia un tipo valido coerente con la macro indicata. */
    public static function isValidType(string $slug, string $macro, ?PDO $pdo = null): bool
    {
        // Con una connessione disponibile si verifica l'ESISTENZA nella tabella
        // corretta (vehicle_types per road, special_types per special), coerente
        // con isValidForCategory() usato altrove (es. wanted). Cosi' un tipo
        // speciale curato dall'admin viene riconosciuto e "on_demand" resta
        // sempre lecito. Senza PDO si ricade sull'elenco statico.
        if ($pdo instanceof PDO) {
            $category = ($macro === self::MACRO_ROAD) ? self::CAT_ROAD : self::CAT_SPECIAL;
            return self::isValidForCategory($slug, $category, $pdo);
        }
        $list = self::typesByMacro($macro, $pdo);
        return isset($list[$slug]);
    }
}
