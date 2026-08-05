<?php
// ============================================================
// libs/product_macro.class.php
// Overlay MOTORSPORT di brand (Fase 1): 5 macro-categorie SOPRA la
// tassonomia commerciale (VehicleTaxonomy / vehicle_types).
//
// Punto UNICO in cui vivono:
//  - le 5 macro di brand (slug => label) e relative costanti;
//  - la mappa macro -> product_key per il matching fornitori, usata
//    dal configuratore/RFQ (CompanyManager::getCompaniesByProducts);
//  - la regola di classificazione di un annuncio in una macro
//    (forAd), allineata 1:1 al backfill di product_macros.sql.
//
// DB: quando product_macros e' migrata, all()/label() leggono dal DB
// (solo dati reali, dir. 14); altrimenti usano la mappa statica come
// fallback non distruttivo (dir. 9). Lo SLUG resta stabile.
//
// NB matching fornitori: getCompaniesByProducts interroga SOLO
// 06_company_products (regular) e 06_company_products_special (special).
// I servizi (CompanyManager::$services, es. realizzazione_su_progetto)
// NON sono coperti: per includerli serve estendere il matcher.
// ============================================================

class ProductMacro
{
    // ------------------------------------------------------------
    // Le 5 macro di brand (slug stabili)
    // ------------------------------------------------------------
    const RACE_TRAILER  = 'race-trailer';
    const HOSPITALITY   = 'hospitality';
    const MOBILE_CLINIC = 'mobile-clinic';
    const SHELTER       = 'shelter-container';
    const CUSTOM        = 'custom-projects';

    // Catalogo statico slug => label (fallback se manca product_macros)
    public static $macros = [
        self::RACE_TRAILER  => 'Race Trailer',
        self::HOSPITALITY   => 'Hospitality',
        self::MOBILE_CLINIC => 'Mobile Clinic',
        self::SHELTER       => 'Shelter & Container',
        self::CUSTOM        => 'Custom Projects',
    ];

    // ------------------------------------------------------------
    // Mappa macro -> product_key per il matching fornitori (RFQ).
    //   'special' => chiavi di CompanyManager::$products_special
    //                (tabella 06_company_products_special)
    //   'regular' => chiavi di CompanyManager::$products
    //                (tabella 06_company_products)
    // Passabili direttamente a getCompaniesByProducts($regular, $special).
    // ------------------------------------------------------------
    public static $supplierKeys = [
        self::RACE_TRAILER  => [
            'regular' => [],
            'special' => ['racing_trailer', 'paddock_trailers', 'box_trailer'],
        ],
        self::HOSPITALITY   => [
            'regular' => [],
            'special' => ['hospitality_units'],
        ],
        self::MOBILE_CLINIC => [
            'regular' => ['laboratori_medici_mobili', 'ambulanze', 'disabili'],
            'special' => [],
        ],
        self::SHELTER       => [
            'regular' => [],
            'special' => ['shelter_container'], // rinominato 24 lug 2026 (era special_shelter)
        ],
        self::CUSTOM        => [
            // 'camper' e' fra i prodotti REGOLARI: motorhomes_mobilhomes vi e'
            // confluito il 24 lug 2026 (motorhome e mobilhome sono camper).
            'regular' => ['camper'],
            'special' => [],
            // Nota: 'realizzazione_su_progetto' e' un SERVIZIO
            // (CompanyManager::$services), non coperto dal matcher attuale.
        ],
    ];

    // vehicle_type che, da soli, implicano Mobile Clinic (= backfill SQL)
    public static $medicalSlugs = ['laboratori_medici_mobili', 'ambulanze', 'disabili'];

    // ------------------------------------------------------------
    // Elenco [slug => name] delle macro.
    // DB-first da product_macros (ORDER BY sort_order), con fallback
    // statico. Mantiene l'ordine voluto anche pre-migrazione.
    // ------------------------------------------------------------
    public static function all(?PDO $pdo = null): array
    {
        if ($pdo instanceof PDO) {
            try {
                $stmt = $pdo->query(
                    "SELECT slug, name FROM product_macros
                     ORDER BY sort_order, name"
                );
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];
                if (!empty($rows)) {
                    return $rows; // [slug => name]
                }
            } catch (Throwable $e) {
                // Tabella non ancora migrata -> fallback statico
            }
        }
        return self::$macros;
    }

    /** Label di una macro (DB-first, fallback statico). */
    public static function label(string $slug, ?PDO $pdo = null): string
    {
        $all = self::all($pdo);
        if (isset($all[$slug])) {
            return $all[$slug];
        }
        return self::$macros[$slug] ?? ucwords(str_replace('-', ' ', $slug));
    }

    /** True se lo slug e' una delle 5 macro note. */
    public static function exists(string $slug): bool
    {
        return isset(self::$macros[$slug]);
    }

    /**
     * Chiavi fornitore per una macro: ['regular' => [...], 'special' => [...]].
     * Pronte per CompanyManager::getCompaniesByProducts($regular, $special).
     */
    public static function supplierKeysFor(string $slug): array
    {
        return self::$supplierKeys[$slug] ?? ['regular' => [], 'special' => []];
    }

    /**
     * Reverse map: dato un elenco di product_key aziendali (regular+special),
     * ritorna gli slug delle macro coperte. Ponte fornitore -> marketplace.
     */
    public static function macrosForSupplierKeys(array $productKeys): array
    {
        $out = [];
        $keys = array_values(array_filter(array_map('strval', $productKeys), 'strlen'));
        foreach (self::$supplierKeys as $macro => $sets) {
            $all = array_merge($sets['regular'] ?? [], $sets['special'] ?? []);
            foreach ($keys as $k) {
                if (in_array($k, $all, true)) { $out[$macro] = true; break; }
            }
        }
        return array_keys($out);
    }

    /**
     * Classifica un annuncio (riga 02/03_ads) in una macro di brand.
     * Stessa priorita' del backfill di product_macros.sql:
     *   shelter > racing > hospitality > medical > project.
     * Ritorna lo slug macro, oppure null se nessun segnale di brand
     * (es. annuncio puramente commerciale: motorhome, street food, ...).
     */
    /**
     * Famiglia (product_macro) a partire dalla SOLA scelta gerarchica
     * categoria + tipologia, senza avere un annuncio completo.
     *
     * Serve dove l'utente sceglie solo "che cosa cerca" - richiesta di
     * preventivo (RFQ) e wanted request - e non compila un annuncio: la
     * famiglia si deriva con le stesse regole dell'inserimento, cosi' le
     * tre pagine restano coerenti fra loro.
     *
     * $itemKind: 'vehicle' oppure 'shelter_container'
     * $vtype   : vehicle_types.slug
     */
    public static function forSelection(string $itemKind, string $vtype): ?string
    {
        // Stessa mappa slug -> flag usata dal wizard di inserimento
        // (02_free_ads/02_01_upload_advertising.php): alcune tipologie
        // implicano da sole una caratteristica dell'annuncio.
        $slugToFlag = [
            'autonegozi_alimentari'    => 'street_food',
            'camper'                   => 'motorhome',
            'ambulanze'                => 'medical',
            'laboratori_medici_mobili' => 'medical',
            'forze_dell_ordine'        => 'military',
            'blindati'                 => 'military',
            // tipologie da paddock: implicano la famiglia corrispondente
            'racing_trailer'           => 'racing',
            'box_trailer'              => 'racing',
            'paddock_trailers'         => 'racing',
            'hospitality_units'        => 'hospitality',
        ];
        $ad = ['item_kind' => $itemKind, 'vehicle_type' => $vtype];
        if (isset($slugToFlag[$vtype])) {
            $ad[$slugToFlag[$vtype]] = 1;
        }
        return self::forAd($ad);
    }

    public static function forAd(array $ad): ?string
    {
        $kind  = (string)($ad['item_kind'] ?? '');
        $vtype = (string)($ad['vehicle_type'] ?? '');
        $type  = (string)($ad['type'] ?? '');
        $cond  = (string)($ad['conditions'] ?? '');

        if ($kind === 'shelter_container') {
            return self::SHELTER;
        }
        if (!empty($ad['racing'])) {
            return self::RACE_TRAILER;
        }
        if (!empty($ad['hospitality'])) {
            return self::HOSPITALITY;
        }
        if (!empty($ad['medical']) || in_array($vtype, self::$medicalSlugs, true)) {
            return self::MOBILE_CLINIC;
        }
        if ($type === 'Project' || $cond === 'Project') {
            return self::CUSTOM;
        }
        return null;
    }
}
