<?php
// ============================================================
// libs/ad_section_fields.class.php
// SORGENTE UNICA: quali variabili appartengono a quale sezione.
//
// 23 lug 2026. Le tre sezioni del sito (Road / Special / Shelter) non
// descrivono le stesse cose: un cassone stradale non ha la veranda, uno
// shelter non ha gli assi ne' la sponda idraulica. Finora la modifica
// mostrava a tutti gli stessi campi. Qui si dichiara, una volta sola, cosa
// e' pertinente a ciascuna sezione; i form di modifica leggono da qui.
//
// Le sezioni derivano dalla classificazione dell'annuncio:
//   item_kind = shelter_container            -> 'shelter'
//   item_kind = vehicle, macro = road        -> 'road'
//   item_kind = vehicle, macro = special     -> 'special'
//
// I nomi dei campi sono quelli reali delle tabelle 02_free_ads / 03_ads e
// 03_ads_tech_details (nessun campo inventato, dir. 14).
// ============================================================

require_once __DIR__ . '/vehicle_taxonomy.class.php';

class AdSectionFields
{
    const SEC_ROAD    = 'road';
    const SEC_SPECIAL = 'special';
    const SEC_SHELTER = 'shelter';

    /**
     * Sezione di un annuncio, dalla sua classificazione salvata.
     */
    public static function sectionOf(array $ad): string
    {
        $kind = (string)($ad['item_kind'] ?? VehicleTaxonomy::KIND_VEHICLE);
        if ($kind === VehicleTaxonomy::KIND_SHELTER) {
            return self::SEC_SHELTER;
        }
        $macro = (string)($ad['macro_category'] ?? VehicleTaxonomy::MACRO_SPECIAL);
        return ($macro === VehicleTaxonomy::MACRO_ROAD) ? self::SEC_ROAD : self::SEC_SPECIAL;
    }

    /** Etichetta leggibile della sezione (UI in inglese). */
    public static function label(string $section): string
    {
        switch ($section) {
            case self::SEC_ROAD:    return 'Road vehicle';
            case self::SEC_SHELTER: return 'Shelter / Container';
            default:                return 'Special vehicle';
        }
    }

    // ------------------------------------------------------------
    // CAMPI BASE (tabelle 02_free_ads / 03_ads)
    // ------------------------------------------------------------
    // Comuni a tutte le sezioni: sono l'annuncio in se' (cosa vendi, a che
    // prezzo, in che stato).
    private static $baseCommon = [
        'title', 'subtitle', 'list_price', 'type', 'conditions', 'description',
        'length_mt', 'width_mt', 'height_mt',
    ];

    /**
     * Campi base modificabili per la sezione.
     * axles_n: SOLO per i veicoli. Uno shelter/container e' una struttura
     * statica, non ha assi: chiederlo sarebbe un campo sempre vuoto.
     */
    public static function baseFields(string $section): array
    {
        $f = self::$baseCommon;
        if ($section !== self::SEC_SHELTER) {
            $f[] = 'axles_n';
        }
        return $f;
    }

    /** true se la sezione prevede il campo numero assi. */
    public static function hasAxles(string $section): bool
    {
        return $section !== self::SEC_SHELTER;
    }

    /**
     * Il tipo veicolo e' scegliibile? Per lo shelter no: il tipo e' fisso
     * (shelter_container), come nel wizard di inserimento.
     */
    public static function hasVehicleTypeChoice(string $section): bool
    {
        // Aggiornato 24 lug 2026: anche lo SHELTER sceglie la tipologia.
        // Prima era un tipo unico e fisso; ora gli shelter condividono la
        // lista degli special (special_types), perche' sono gli stessi
        // allestimenti costruiti su container. Quindi vale per tutte le
        // sezioni.
        return true;
    }

    /** Macro storica della sezione. NB: la tendina dei tipi NON si popola
     *  piu' da qui - la tabella la sceglie VehicleTaxonomy::typesForCategory(). */
    public static function macroFor(string $section): string
    {
        return ($section === self::SEC_ROAD)
            ? VehicleTaxonomy::MACRO_ROAD
            : VehicleTaxonomy::MACRO_SPECIAL;
    }

    // ------------------------------------------------------------
    // GRUPPI TECNICI (premium, tabella 03_ads_tech_details)
    // ------------------------------------------------------------
    // I nomi dei gruppi sono quelli di shared/tech_details_fields.php
    // ($AOW_TECH_GROUPS): qui si dice solo QUALI mostrare per sezione, e per
    // alcuni gruppi quali singoli campi escludere.
    //
    // Criterio:
    //  - Road    = allestimenti su veicolo stradale. Ha telaio e sponda; non
    //              ha veranda/cucina/letti/bagno/SAT (sono da paddock o
    //              abitativi) ne' telemetria (da corsa).
    //  - Special = paddock, hospitality, motorhome, uffici e laboratori
    //              mobili: e' la sezione piu' ricca, ha tutto.
    //  - Shelter = struttura statica: niente telaio (assi/MGW/ralla) e niente
    //              sponda idraulica; niente veranda ne' gavone (da rimorchio).
    //              Tiene impianti, arredo, finiture e dimensioni.
    private static $techMap = [
        self::SEC_ROAD => [
            'General options'    => ['exclude' => ['Awning', 'Belly', 'Kitchen', 'Beds', 'Bathroom', 'SAT']],
            'Lift facilities'    => ['exclude' => []],
            'Cargo facilities'   => ['exclude' => []],
            'Office furniture'   => ['exclude' => ['Telemetry', 'TV']],
            'Electrical system'  => ['exclude' => []],
            'Outside finishing'  => ['exclude' => []],
            'Chassis'            => ['exclude' => []],
            'External dimension' => ['exclude' => []],
        ],
        self::SEC_SPECIAL => [
            'General options'    => ['exclude' => []],
            'Lift facilities'    => ['exclude' => []],
            'Cargo facilities'   => ['exclude' => []],
            'Office furniture'   => ['exclude' => []],
            'Electrical system'  => ['exclude' => []],
            'Outside finishing'  => ['exclude' => []],
            'Chassis'            => ['exclude' => []],
            'External dimension' => ['exclude' => []],
        ],
        self::SEC_SHELTER => [
            'General options'    => ['exclude' => ['Awning', 'Belly']],
            'Cargo facilities'   => ['exclude' => []],
            'Office furniture'   => ['exclude' => ['Telemetry']],
            'Electrical system'  => ['exclude' => []],
            'Outside finishing'  => ['exclude' => []],
            'External dimension' => ['exclude' => []],
            // niente 'Lift facilities' (sponda idraulica: da veicolo)
            // niente 'Chassis' (assi, MGW, ralla, step deck: da veicolo)
        ],
    ];

    /** I gruppi tecnici previsti per la sezione, nell'ordine di dichiarazione. */
    public static function techGroups(string $section): array
    {
        return self::$techMap[$section] ?? self::$techMap[self::SEC_SPECIAL];
    }

    /** true se il gruppo tecnico e' previsto per la sezione. */
    public static function hasTechGroup(string $section, string $group): bool
    {
        return isset(self::$techMap[$section][$group]);
    }

    /** true se il singolo campo tecnico e' previsto per la sezione. */
    public static function hasTechField(string $section, string $group, string $field): bool
    {
        if (!self::hasTechGroup($section, $group)) { return false; }
        $ex = self::$techMap[$section][$group]['exclude'] ?? [];
        return !in_array($field, $ex, true);
    }

    /**
     * Tutti i campi tecnici ammessi per la sezione, appiattiti.
     * Serve all'handler per NON salvare cio' che la sezione non prevede.
     * $allGroups e' la struttura $AOW_TECH_GROUPS di shared/tech_details_fields.php.
     */
    public static function allowedTechFields(string $section, array $allGroups): array
    {
        $out = [];
        foreach ($allGroups as $gname => $gdef) {
            if (!self::hasTechGroup($section, $gname)) { continue; }
            $fields = $gdef[1] ?? [];
            foreach ($fields as $key => $label) {
                if (self::hasTechField($section, $gname, $key)) { $out[] = $key; }
            }
        }
        return $out;
    }
}
