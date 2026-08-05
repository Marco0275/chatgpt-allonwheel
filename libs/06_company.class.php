<?php
/**
 * Classe 06_company - Gestione Aziende Fornitori
 * Allonwheel
 */

require_once __DIR__ . '/vehicle_taxonomy.class.php';

class CompanyManager {
  
  private $conn;
  
  // ============================================================
  // Tipologie di allestimento (checkbox, da immagine "Allestitori speciali")
  // Salvate in 06_company_products con product_key = chiave qui sotto
  // ============================================================
  public static $products = [
    'ambulanze'      => 'Ambulances',
    'autonegozi_alimentari'  => 'Street Food',
    'autonegozi_mercerie'    => 'Haberdashery',
    'blindati'        => 'Armored',
    'camper'       => 'Motorhomes',
    'carrattrezzi'     => 'Tow trucks',
    'cassoni'        => 'Tippers',
    'centinati'      => 'Curtain-side bodies',
    'coibentati'       => 'Insulated bodies',
    'disabili'       => 'Disabled access vehicles',
    'forze_dell_ordine'    => "Law enforcement",
    'frigoriferi'      => 'Refrigerated bodies',
    'furgonature_box'    => 'Box vans',
    'isotermici'       => 'Isothermal bodies',
    'laboratori_medici_mobili' => 'Mobile medical labs',
    'minibus'        => 'Minibuses',
    'officine_mobili'    => 'Mobile workshops',
    'piattaforme_aeree_gru'  => 'Aerial platforms / Cranes',
    'pubblica_amministrazione' => 'Public administration',
    'scuolabus'      => 'School buses',
    'servizi_ecologici'    => 'Waste collection vehicles',
    'sistemi_di_sollevamento'  => 'Lifting systems',
    'tempo_libero'     => 'Leisure',
    'trasporto_abiti'    => 'Garment transport',
    'trasporto_animali'    => 'Animal transport',
    'uffici_mobili'      => 'Mobile offices',
    'vvf_protezione_civile'  => 'Fire dept. / Civil protection',
  ];

  // Attributi extra per ogni tipologia (tinyint(1) in DB)
  // Per il dominio "allestitori" non sono richiesti attributi: il modello
  // resta in DB per compatibilità schema ma in UI non vengono mostrati.
  // Lasciati VUOTI di proposito → il render del form salta le colonne attributi.
  public static $product_attributes = [
    // (intentionally empty)
  ];

  // ============================================================
  // Servizi accessori (checkbox + nota opzionale)
  // ============================================================
  public static $services = [
    'assistenza_tecnica'   => 'Technical support',
    'riparazioni'      => 'Repairs',
    'realizzazione_su_progetto'  => 'Custom builds',
    'pratiche_per_omologazione'  => 'Type approval procedures',
    'offerta_veicoli_usati'  => 'Used vehicles available',
    'certificazione'     => 'Certification',
  ];

  // ============================================================
  // Categorie SPECIALI (flowchart ramo "Special").
  // Salvate in 06_company_products_special con product_key = chiave qui sotto.
  // ============================================================
  // Unificazione slug 24 lug 2026 (patch 2026-07-24b_taxonomy_merge.sql):
  //  - motorhomes_mobilhomes e' confluito in 'camper' (motorhome e mobilhome
  //    sono camper) che sta fra i prodotti REGOLARI qui sopra;
  //  - special_shelter rinominato 'shelter_container' per combaciare con
  //    VehicleTaxonomy::SHELTER_SLUG, che e' cio' che scrivono gli annunci;
  //  - roadshow_vehicles e street_food non erano qui: erano solo tipologie
  //    annuncio, ora confluite in hospitality_units e autonegozi_alimentari.
  public static $products_special = [
    'racing_trailer'        => 'Racing trailer',
    'box_trailer'           => 'Box trailer',
    'hospitality_units'     => 'Hospitality units',
    'paddock_trailers'      => 'Paddock trailers',
    'shelter_container'       => 'Special Shelter',
  ];

  // -----------------------------------------------------------------
  // SEZIONI RFQ (23 lug 2026): la richiesta di preventivo e' divisa per
  // sezione - Road, Special, Shelter - cosi' ogni sezione mostra SOLO le
  // categorie pertinenti, invece di un unico elenco con tutto mescolato.
  // Stessa logica delle tre sezioni del sito (vedi AdSectionFields).
  //
  // 'regular' = chiavi di self::$products        (checkbox product[])
  // 'special' = chiavi di self::$products_special (checkbox product_special[])
  //
  // Le regular NON elencate sotto 'road' (camper, laboratori_medici_mobili,
  // uffici_mobili) sono Special per tassonomia e stanno nella sezione special.
  public static $rfqSections = [
    'road' => [
      'label'   => 'Road vehicles',
      'regular' => [
        'ambulanze', 'autonegozi_alimentari', 'autonegozi_mercerie', 'blindati',
        'carrattrezzi', 'cassoni', 'centinati', 'coibentati', 'disabili',
        'forze_dell_ordine', 'frigoriferi', 'furgonature_box', 'isotermici',
        'minibus', 'officine_mobili', 'piattaforme_aeree_gru',
        'pubblica_amministrazione', 'scuolabus', 'servizi_ecologici',
        'sistemi_di_sollevamento', 'tempo_libero', 'trasporto_abiti',
        'trasporto_animali', 'vvf_protezione_civile',
      ],
      'special' => [],
    ],
    'special' => [
      'label'   => 'Special vehicles',
      'regular' => ['camper', 'laboratori_medici_mobili', 'uffici_mobili'],
      'special' => [
        'racing_trailer', 'box_trailer',
        'hospitality_units', 'paddock_trailers',
      ],
    ],
    'shelter' => [
      'label'   => 'Shelter & Container',
      'regular' => [],
      'special' => ['shelter_container'],
    ],
  ];

  /**
   * Categorie da mostrare nella RFQ per la sezione indicata.
   * Ritorna ['regular'=>[k=>label], 'special'=>[k=>label], 'label'=>...].
   * Sezione assente o sconosciuta: TUTTE le categorie, cioe' il
   * comportamento storico (i link generici alla RFQ restano validi).
   */
  public static function rfqCategoriesFor(?string $section): array {
    if ($section !== null && isset(self::$rfqSections[$section])) {
      $def = self::$rfqSections[$section];
      $reg = [];
      foreach ($def['regular'] as $k) {
        if (isset(self::$products[$k])) { $reg[$k] = self::$products[$k]; }
      }
      $spc = [];
      foreach ($def['special'] as $k) {
        if (isset(self::$products_special[$k])) { $spc[$k] = self::$products_special[$k]; }
      }
      return ['regular' => $reg, 'special' => $spc, 'label' => $def['label']];
    }
    return ['regular' => self::$products, 'special' => self::$products_special, 'label' => ''];
  }
  
  public function __construct(PDO $conn) {
    $this->conn = $conn;
  }
  
  // =========================================================
  // CRUD AZIENDA
  // =========================================================
  
  /**
   * Verifica se l'utente ha già un'azienda registrata
   */
  public function userHasCompany($user_id) {
    $stmt = $this->conn->prepare("SELECT id FROM `06_company` WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt;
    $company = $result->fetch(PDO::FETCH_ASSOC);
    return $company ? $company['id'] : false;
  }
  
  /**
   * Ottieni dati azienda per ID
   */
  public function getCompanyById($id) {
    $stmt = $this->conn->prepare("SELECT * FROM `06_company` WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt;
    $company = $result->fetch(PDO::FETCH_ASSOC);
    return $company;
  }
  
  /**
   * Ottieni dati azienda per user_id
   */
  public function getCompanyByUserId($user_id) {
    $stmt = $this->conn->prepare("SELECT * FROM `06_company` WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt;
    $company = $result->fetch(PDO::FETCH_ASSOC);
    return $company;
  }
  
  /**
   * Inserisci nuova azienda
   */
  public function insertCompany($data) {
    $stmt = $this->conn->prepare(
    "INSERT INTO `06_company` (
      user_id, ragione_sociale, partita_iva, codice_fiscale,
      indirizzo, cap, citta, provincia, nazione,
      telefono, cellulare, fax, email, pec, sito_web,
      descrizione, logo,
      referente_nome, referente_cognome, referente_ruolo,
      referente_email, referente_telefono,
      offers_rental, general_note
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $result = $stmt->execute([$data['user_id'], $data['ragione_sociale'], $data['partita_iva'], $data['codice_fiscale'], $data['indirizzo'], $data['cap'], $data['citta'], $data['provincia'], $data['nazione'], $data['telefono'], $data['cellulare'], $data['fax'], $data['email'], $data['pec'], $data['sito_web'], $data['descrizione'], $data['logo'], $data['referente_nome'], $data['referente_cognome'], $data['referente_ruolo'], $data['referente_email'], $data['referente_telefono'], $data['offers_rental'] ?? 0, $data['general_note'] ?? '']);
    $insert_id = $result ? $this->conn->lastInsertId() : false;
    return $insert_id;
  }
  
  /**
   * Aggiorna azienda
   */
  public function updateCompany($id, $data) {
    $stmt = $this->conn->prepare(
    "UPDATE `06_company` SET
      ragione_sociale = ?, partita_iva = ?, codice_fiscale = ?,
      indirizzo = ?, cap = ?, citta = ?, provincia = ?, nazione = ?,
      telefono = ?, cellulare = ?, fax = ?, email = ?, pec = ?, sito_web = ?,
      descrizione = ?, logo = ?,
      referente_nome = ?, referente_cognome = ?, referente_ruolo = ?,
      referente_email = ?, referente_telefono = ?,
      data_modifica = NOW()
    WHERE id = ? AND user_id = ?"
    );
    $result = $stmt->execute([$data['ragione_sociale'], $data['partita_iva'], $data['codice_fiscale'], $data['indirizzo'], $data['cap'], $data['citta'], $data['provincia'], $data['nazione'], $data['telefono'], $data['cellulare'], $data['fax'], $data['email'], $data['pec'], $data['sito_web'], $data['descrizione'], $data['logo'], $data['referente_nome'], $data['referente_cognome'], $data['referente_ruolo'], $data['referente_email'], $data['referente_telefono'], $id, $data['user_id']]);
    return $result;
  }
  
  /**
   * Elimina azienda e tutti i dati correlati (CASCADE)
   */
  public function deleteCompany($id, $user_id) {
    // Recupera immagini gallery per eliminazione file fisici
    $images = $this->getGalleryImages($id);
    $logo = $this->getCompanyById($id);
    
    $stmt = $this->conn->prepare("DELETE FROM `06_company` WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$id, $user_id]);
    
    if ($result) {
    // Elimina file fisici gallery (original + thumbnail)
    foreach ($images as $img) {
      $base = $_SERVER['DOCUMENT_ROOT'] . '/upload_image/06_company/';
      foreach (['original', 'thumbnail'] as $sub) {
        $filepath = $base . $sub . '/' . $img['immagine'];
        if (file_exists($filepath)) { unlink($filepath); }
      }
      // Compatibilità con vecchi upload flat (pre-refactoring)
      $flat = $base . $img['immagine'];
      if (file_exists($flat)) { unlink($flat); }
    }
    // Elimina logo (original + thumbnail)
    if ($logo && !empty($logo['logo'])) {
      $base = $_SERVER['DOCUMENT_ROOT'] . '/upload_image/06_company/';
      foreach (['original', 'thumbnail'] as $sub) {
        $logopath = $base . $sub . '/' . $logo['logo'];
        if (file_exists($logopath)) { unlink($logopath); }
      }
      // Compatibilità con vecchi upload flat
      $flat = $base . $logo['logo'];
      if (file_exists($flat)) { unlink($flat); }
    }
    }
    return $result;
  }
  
  // =========================================================
  // PRODOTTI
  // =========================================================
  
  // ============================================================
  // Tassonomia DB-driven per la dichiarazione prodotti del fornitore.
  // Le tipologie NON sono piu' hardcoded: si leggono dalle stesse tabelle
  // usate dagli annunci e dall'RFQ (vehicle_types per i ROAD, special_types
  // per gli SPECIAL), cosi' i tipi aggiunti dall'admin diventano subito
  // dichiarabili e il match RFQ->fornitore resta coerente.
  //
  // Ritorna [slug => name], stesso shape degli storici array statici, cosi'
  // i foreach dei form restano identici. $keepKeys reintegra eventuali slug
  // gia' salvati per l'azienda ma non piu' in tabella (legacy): non vengono
  // eliminati in silenzio, restano selezionabili finche' l'utente non li
  // toglie di proposito.
  // ============================================================
  public static function productsRoad(PDO $pdo, array $keepKeys = []): array
  {
    if (!class_exists('VehicleTaxonomy')) {
      require_once __DIR__ . '/vehicle_taxonomy.class.php';
    }
    return self::taxonomyChecklist(VehicleTaxonomy::CAT_ROAD, $pdo, $keepKeys);
  }

  public static function productsSpecial(PDO $pdo, array $keepKeys = []): array
  {
    if (!class_exists('VehicleTaxonomy')) {
      require_once __DIR__ . '/vehicle_taxonomy.class.php';
    }
    return self::taxonomyChecklist(VehicleTaxonomy::CAT_SPECIAL, $pdo, $keepKeys);
  }

  /**
   * Etichetta di una tipologia (road o special) letta dal DB via
   * VehicleTaxonomy. Fallback allo slug se non trovata. Usata dai punti di
   * sola visualizzazione al posto dei vecchi array statici, cosi' anche i
   * tipi aggiunti dall'admin mostrano il nome corretto.
   */
  public static function productLabel(string $slug, PDO $pdo): string
  {
    if (!class_exists('VehicleTaxonomy')) {
      require_once __DIR__ . '/vehicle_taxonomy.class.php';
    }
    return VehicleTaxonomy::label($slug, $pdo);
  }

  private static function taxonomyChecklist(string $category, PDO $pdo, array $keepKeys): array
  {
    if (!class_exists('VehicleTaxonomy')) {
      require_once __DIR__ . '/vehicle_taxonomy.class.php';
    }
    $out = [];
    foreach (VehicleTaxonomy::typesForCategory($category, $pdo) as $row) {
      $slug = (string)($row['slug'] ?? '');
      if ($slug === '') { continue; }
      $out[$slug] = (string)($row['name'] ?? $slug);
    }
    // Guardia anti-perdita dati: reintegra gli slug gia' salvati non piu' in tabella.
    foreach ($keepKeys as $slug) {
      $slug = (string)$slug;
      if ($slug !== '' && !isset($out[$slug])) {
        $out[$slug] = VehicleTaxonomy::label($slug, $pdo);
      }
    }
    return $out;
  }

  /**
   * Salva prodotti selezionati per l'azienda
   */
  public function saveProducts($company_id, $products_data) {
    // Elimina prodotti esistenti
    $stmt = $this->conn->prepare("DELETE FROM `06_company_products` WHERE company_id = ?");
    $stmt->execute([$company_id]);
    
    if (empty($products_data)) return true;
    
    $stmt = $this->conn->prepare(
    "INSERT INTO `06_company_products` 
    (company_id, product_key, note, certificazioni_prodotto, campioni_gratuiti, assistenza_posa, progettazione_supporto, schede_tecniche) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    foreach ($products_data as $product) {
    $stmt->execute([$company_id, $product['product_key'], $product['note'], $product['certificazioni_prodotto'], $product['campioni_gratuiti'], $product['assistenza_posa'], $product['progettazione_supporto'], $product['schede_tecniche']]);
    }
    return true;
  }
  
  /**
   * Ottieni prodotti dell'azienda
   */
  public function getProducts($company_id) {
    $stmt = $this->conn->prepare("SELECT * FROM `06_company_products` WHERE company_id = ? ORDER BY product_key");
    $stmt->execute([$company_id]);
    $result = $stmt;
    $products = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $products[$row['product_key']] = $row;
    }
    return $products;
  }
  
  // =========================================================
  // SERVIZI
  // =========================================================
  
  /**
   * Salva servizi selezionati per l'azienda
   */
  public function saveServices($company_id, $services_data) {
    $stmt = $this->conn->prepare("DELETE FROM `06_company_services` WHERE company_id = ?");
    $stmt->execute([$company_id]);
    
    if (empty($services_data)) return true;
    
    $stmt = $this->conn->prepare(
    "INSERT INTO `06_company_services` (company_id, service_key, note) VALUES (?, ?, ?)"
    );
    
    foreach ($services_data as $service) {
    $stmt->execute([$company_id, $service['service_key'], $service['note']]);
    }
    return true;
  }
  
  /**
   * Ottieni servizi dell'azienda
   */
  public function getServices($company_id) {
    $stmt = $this->conn->prepare("SELECT * FROM `06_company_services` WHERE company_id = ? ORDER BY service_key");
    $stmt->execute([$company_id]);
    $result = $stmt;
    $services = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $services[$row['service_key']] = $row;
    }
    return $services;
  }
  
  // =========================================================
  // GALLERY
  // =========================================================
  
  /**
   * Ottieni immagini gallery dell'azienda
   */
  public function getGalleryImages($company_id) {
    $stmt = $this->conn->prepare("SELECT * FROM `06_company_gallery` WHERE company_id = ? ORDER BY ordine, id");
    $stmt->execute([$company_id]);
    $result = $stmt;
    $images = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $images[] = $row;
    }
    return $images;
  }
  
  /**
   * Inserisci immagine gallery
   */
  public function countGalleryImages($company_id) {
    $stmt = $this->conn->prepare("SELECT COUNT(*) FROM `06_company_gallery` WHERE company_id = ?");
    $stmt->execute([$company_id]);
    $count = $stmt->fetchColumn();
    return (int)$count;
  }

  public function insertGalleryImage($company_id, $user_id, $immagine, $didascalia = '', $ordine = 0) {
    $stmt = $this->conn->prepare(
    "INSERT INTO `06_company_gallery` (company_id, user_id, immagine, didascalia, ordine) VALUES (?, ?, ?, ?, ?)"
    );
    $result = $stmt->execute([$company_id, $user_id, $immagine, $didascalia, $ordine]);
    return $result;
  }
  
  /**
   * Elimina immagine gallery
   */
  public function deleteGalleryImage($image_id, $user_id) {
    // Ottieni nome file prima di eliminare
    $stmt = $this->conn->prepare("SELECT immagine FROM `06_company_gallery` WHERE id = ? AND user_id = ?");
    $stmt->execute([$image_id, $user_id]);
    $result = $stmt;
    $image = $result->fetch(PDO::FETCH_ASSOC);
    
    if (!$image) return false;
    
    // Elimina dal database
    $stmt = $this->conn->prepare("DELETE FROM `06_company_gallery` WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$image_id, $user_id]);
    
    // Elimina file fisici (original + thumbnail)
    if ($result && !empty($image['immagine'])) {
    $base = $_SERVER['DOCUMENT_ROOT'] . '/upload_image/06_company/';
    foreach (['original', 'thumbnail'] as $sub) {
      $filepath = $base . $sub . '/' . $image['immagine'];
      if (file_exists($filepath)) { unlink($filepath); }
    }
    // Compatibilità con vecchi upload flat (pre-refactoring)
    $flat = $base . $image['immagine'];
    if (file_exists($flat)) { unlink($flat); }
    }
    return $result;
  }
  
  // =========================================================
  // ELENCO / DIRECTORY
  // =========================================================
  
  /**
   * Ottieni tutte le aziende attive per la directory
   */
  public function getAllActiveCompanies() {
    $stmt = $this->conn->prepare(
    "SELECT c.*, 
        (SELECT COUNT(*) FROM `06_company_products` WHERE company_id = c.id) as num_products,
        (SELECT COUNT(*) FROM `06_company_services` WHERE company_id = c.id) as num_services
     FROM `06_company` c 
      LEFT JOIN users u ON u.id_user = c.user_id
     WHERE c.attiva = 1 
     ORDER BY (u.user_tier='gold') DESC, c.ragione_sociale ASC"
    );
    $stmt->execute();
    $result = $stmt;
    $companies = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $companies[] = $row;
    }
    return $companies;
  }
  
  /**
   * Ottieni la matrice completa aziende-prodotti per la pagina riepilogativa
   */
  public function getDirectoryMatrix() {
    $companies = $this->getAllActiveCompanies();
    $matrix = [];
    
    foreach ($companies as $company) {
    $company['products'] = $this->getProducts($company['id']);
    $company['services_list'] = $this->getServices($company['id']);
    $matrix[] = $company;
    }
    
    return $matrix;
  }
  
  /**
   * Conta aziende totali attive
   */
  public function countActiveCompanies() {
    $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM `06_company` WHERE attiva = 1");
    $stmt->execute();
    $result = $stmt;
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row['total'];
  }
  
  /**
   * Cerca aziende per nome o città
   */
  public function searchCompanies($search) {
    $search_param = '%' . $search . '%';
    $stmt = $this->conn->prepare(
    "SELECT c.*,
        (SELECT COUNT(*) FROM `06_company_products` WHERE company_id = c.id) as num_products,
        (SELECT COUNT(*) FROM `06_company_services` WHERE company_id = c.id) as num_services
     FROM `06_company` c
      LEFT JOIN users u ON u.id_user = c.user_id
     WHERE c.attiva = 1
       AND (c.ragione_sociale LIKE ? OR c.citta LIKE ? OR c.descrizione LIKE ?)
     ORDER BY (u.user_tier='gold') DESC, c.ragione_sociale"
    );
    $stmt->execute([$search_param, $search_param, $search_param]);
    $result = $stmt;
    $companies = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $companies[] = $row;
    }
    return $companies;
  }

  /**
   * Filtra le aziende attive per tipo veicolo.
   * $vtype = vehicle_types.slug = 06_company_products.product_key.
   * La ricerca testuale opzionale e' combinabile col filtro (JOIN + LIKE).
   */
  public function getCompaniesByVehicleType($vtype, $search = '') {
    $sql = "SELECT c.*,
        (SELECT COUNT(*) FROM `06_company_products` WHERE company_id = c.id) as num_products,
        (SELECT COUNT(*) FROM `06_company_services` WHERE company_id = c.id) as num_services
      FROM `06_company` c
      LEFT JOIN users u ON u.id_user = c.user_id
      INNER JOIN `06_company_products` p ON p.company_id = c.id
      WHERE c.attiva = 1 AND p.product_key = ?";
    $types  = "s";
    $params = [$vtype];
    if ($search !== '') {
      $sql .= " AND (c.ragione_sociale LIKE ? OR c.citta LIKE ? OR c.descrizione LIKE ?)";
      $like = '%' . $search . '%';
      $types .= "sss";
      $params[] = $like; $params[] = $like; $params[] = $like;
    }
    $sql .= " GROUP BY c.id ORDER BY MAX(u.user_tier='gold') DESC, c.ragione_sociale";
    $stmt = $this->conn->prepare($sql);
    if (!$stmt) { return []; }
    $stmt->execute($params);
    $result = $stmt;
    $companies = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $companies[] = $row;
    }
    return $companies;
  }

  /**
   * Nome leggibile del tipo veicolo a partire dallo slug (per intestazione).
   */
  public function getVehicleTypeName($slug) {
    $stmt = $this->conn->prepare("SELECT name FROM vehicle_types WHERE slug = ? LIMIT 1");
    if (!$stmt) { return null; }
    $stmt->execute([$slug]);
    $result = $stmt;
    $row = $result->fetch(PDO::FETCH_ASSOC);
    return $row['name'] ?? null;
  }

  // =========================================================
  // PRODOTTI SPECIALI (tabella companion 06_company_products_special)
  // =========================================================

  /**
   * Salva le categorie speciali selezionate per l'azienda.
   * Stessa logica di saveProducts(): replace completo (DELETE + INSERT).
   */
  public function saveProductsSpecial($company_id, $special_data) {
    $stmt = $this->conn->prepare("DELETE FROM `06_company_products_special` WHERE company_id = ?");
    $stmt->execute([$company_id]);

    if (empty($special_data)) return true;

    $stmt = $this->conn->prepare(
      "INSERT INTO `06_company_products_special` (company_id, product_key, note) VALUES (?, ?, ?)"
    );
    foreach ($special_data as $product) {
      $stmt->execute([$company_id, $product['product_key'], $product['note']]);
    }
    return true;
  }

  /**
   * Ottieni le categorie speciali dichiarate dall'azienda (key => row).
   */
  public function getProductsSpecial($company_id) {
    $stmt = $this->conn->prepare("SELECT * FROM `06_company_products_special` WHERE company_id = ? ORDER BY product_key");
    $stmt->execute([$company_id]);
    $result = $stmt;
    $special = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
      $special[$row['product_key']] = $row;
    }
    return $special;
  }

  /**
   * Aziende attive che producono ALMENO UNA delle categorie selezionate,
   * cercando sia in 06_company_products (regular) sia in
   * 06_company_products_special (special). Ritorna righe azienda dedotte
   * per id, con i campi utili all'invio email.
   *
   * @param string[] $regular_keys product_key regolari selezionati
   * @param string[] $special_keys product_key speciali selezionati
   * @return array[] elenco aziende (id, ragione_sociale, email, referente_*)
   */
  // Restituisce TUTTE le aziende attive con email valida (broadcast RFQ, dir. C6)
  public function getAllCompanies() {
    $sql = "SELECT id, ragione_sociale, email, referente_email
            FROM `06_company`
            WHERE attiva = 1 AND email <> ''
            ORDER BY ragione_sociale";
    $res = $this->conn->query($sql);
    if (!$res) { return []; }
    $rows = [];
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) { $rows[] = $r; }
    return $rows;
  }

  // Salva campi i18n/preferenze senza toccare i bind posizionali di insert/update (dir. C7)
  public function saveCompanyPrefs($company_id, $descrizione_it, $wants_pm_list) {
    $st = $this->conn->prepare("UPDATE `06_company` SET descrizione_it = ?, wants_pm_list = ? WHERE id = ?");
    $w = $wants_pm_list ? 1 : 0; $id = (int)$company_id; $dit = (string)$descrizione_it;
    return $st->execute([$dit, $w, $id]);
  }

  public function getCompaniesByProducts(array $regular_keys, array $special_keys) {
    $companies = [];

    $fetch = function (string $table, array $keys) use (&$companies) {
      $keys = array_values(array_unique(array_filter($keys, 'strlen')));
      if (empty($keys)) { return; }
      $place = implode(',', array_fill(0, count($keys), '?'));
      $types = str_repeat('s', count($keys));
      $sql = "SELECT DISTINCT c.id, c.ragione_sociale, c.email,
                     c.referente_nome, c.referente_cognome, c.referente_email
              FROM `06_company` c
              INNER JOIN `{$table}` p ON p.company_id = c.id
              WHERE c.attiva = 1 AND p.product_key IN ({$place})";
      $stmt = $this->conn->prepare($sql);
      if (!$stmt) { return; }
      $stmt->execute($keys);
      $res = $stmt;
      while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        // Dedup per id azienda (un'azienda riceve UNA sola email)
        $companies[(int)$row['id']] = $row;
      }
    };

    $fetch('06_company_products', $regular_keys);
    $fetch('06_company_products_special', $special_keys);

    return array_values($companies);
  }

  /**
   * Come getCompaniesByProducts ma ritorna le righe AZIENDA COMPLETE (c.*),
   * con ricerca testuale opzionale. Usato dalla directory per il filtro per
   * famiglia (macro): le chiavi arrivano da ProductMacro::supplierKeysFor().
   */
  /**
   * Come getCompaniesByProducts, ma calcola anche QUANTO ogni azienda
   * corrisponde alla richiesta (match_count = numero di chiavi prodotto in
   * comune) e ordina per pertinenza decrescente.
   *
   * Serve al tetto sui destinatari della RFQ (17 lug 2026): senza un
   * ordinamento, tagliare a 3 sceglierebbe tre fornitori a caso. Con il
   * punteggio, i 3 che ricevono la richiesta sono i piu' pertinenti.
   *
   * Conteggio via sottoquery correlate (una sola query totale, niente N+1).
   * MySQL 5.7: HAVING su alias e' consentito.
   *
   * @return array righe con: id, ragione_sociale, email, referente_*, match_count
   */
  public function getCompaniesByProductsScored(array $regular_keys, array $special_keys) {
    $reg = array_values(array_unique(array_filter($regular_keys, 'strlen')));
    $spe = array_values(array_unique(array_filter($special_keys, 'strlen')));
    if (empty($reg) && empty($spe)) { return []; }

    // Ogni ramo contribuisce al punteggio solo se ha chiavi: senza,
    // IN () sarebbe SQL non valido -> si usa la costante 0.
    $sub_reg = '0';
    $sub_spe = '0';
    $types = '';
    $vals  = [];
    if (!empty($reg)) {
      $ph = implode(',', array_fill(0, count($reg), '?'));
      $sub_reg = "(SELECT COUNT(*) FROM `06_company_products` p
                    WHERE p.company_id = c.id AND p.product_key IN ({$ph}))";
      $types .= str_repeat('s', count($reg));
      $vals   = array_merge($vals, $reg);
    }
    if (!empty($spe)) {
      $ph = implode(',', array_fill(0, count($spe), '?'));
      $sub_spe = "(SELECT COUNT(*) FROM `06_company_products_special` s
                    WHERE s.company_id = c.id AND s.product_key IN ({$ph}))";
      $types .= str_repeat('s', count($spe));
      $vals   = array_merge($vals, $spe);
    }

    $sql = "SELECT c.id, c.ragione_sociale, c.email,
                   c.referente_nome, c.referente_cognome, c.referente_email,
                   ({$sub_reg} + {$sub_spe}) AS match_count
              FROM `06_company` c
             WHERE c.attiva = 1
            HAVING match_count > 0
             ORDER BY match_count DESC, c.ragione_sociale ASC";

    $stmt = $this->conn->prepare($sql);
    if (!$stmt) { return []; }
    $stmt->execute($vals);
    $res = $stmt;
    $rows = [];
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) { $rows[] = $r; }
    return $rows;
  }

  public function getCompaniesByMacroKeys(array $regular_keys, array $special_keys, $search = '') {
    $companies = [];
    $term = trim((string)$search);

    $fetch = function (string $table, array $keys) use (&$companies, $term) {
      $keys = array_values(array_unique(array_filter($keys, 'strlen')));
      if (empty($keys)) { return; }
      $place  = implode(',', array_fill(0, count($keys), '?'));
      $types  = str_repeat('s', count($keys));
      $params = $keys;
      $sql = "SELECT DISTINCT c.*, u.user_tier AS aow_tier
              FROM `06_company` c
      LEFT JOIN users u ON u.id_user = c.user_id
              INNER JOIN `{$table}` p ON p.company_id = c.id
              WHERE c.attiva = 1 AND p.product_key IN ({$place})";
      if ($term !== '') {
        $sql   .= " AND (c.ragione_sociale LIKE ? OR c.citta LIKE ? OR c.descrizione LIKE ?)";
        $types .= "sss";
        $like   = '%' . $term . '%';
        $params[] = $like; $params[] = $like; $params[] = $like;
      }
      $stmt = $this->conn->prepare($sql);
      if (!$stmt) { return; }
      $stmt->execute($params);
      $res = $stmt;
      while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $companies[(int)$row['id']] = $row; // dedup per id azienda
      }
    };

    $fetch('06_company_products', $regular_keys);
    $fetch('06_company_products_special', $special_keys);

    // Piano Gold (landing): fisso in cima anche nella directory per famiglia.
    // Ordinamento in PHP perche' il metodo unisce e deduplica due query.
    $aow_out = array_values($companies);
    usort($aow_out, function ($a, $b) {
        $ga = (($a['aow_tier'] ?? '') === 'gold') ? 1 : 0;
        $gb = (($b['aow_tier'] ?? '') === 'gold') ? 1 : 0;
        if ($ga !== $gb) { return $gb - $ga; }
        return strcasecmp((string)($a['ragione_sociale'] ?? ''), (string)($b['ragione_sociale'] ?? ''));
    });
    return $aow_out;
  }

  /**
   * Aziende attive che offrono una specifica CATEGORIA SPECIALE.
   * Mirror di getCompaniesByVehicleType() ma su 06_company_products_special.
   * $special_key = chiave di CompanyManager::$products_special.
   */
  public function getCompaniesByProductSpecial($special_key, $search = '') {
    $sql = "SELECT c.*,
        (SELECT COUNT(*) FROM `06_company_products` WHERE company_id = c.id) as num_products,
        (SELECT COUNT(*) FROM `06_company_services` WHERE company_id = c.id) as num_services
      FROM `06_company` c
      INNER JOIN `06_company_products_special` p ON p.company_id = c.id
      WHERE c.attiva = 1 AND p.product_key = ?";
    $types  = "s";
    $params = [$special_key];
    if ($search !== '') {
      $sql .= " AND (c.ragione_sociale LIKE ? OR c.citta LIKE ? OR c.descrizione LIKE ?)";
      $like = '%' . $search . '%';
      $types .= "sss";
      $params[] = $like; $params[] = $like; $params[] = $like;
    }
    $sql .= " GROUP BY c.id ORDER BY c.ragione_sociale";
    $stmt = $this->conn->prepare($sql);
    if (!$stmt) { return []; }
    $stmt->execute($params);
    $result = $stmt;
    $companies = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
      $companies[] = $row;
    }
    return $companies;
  }
}