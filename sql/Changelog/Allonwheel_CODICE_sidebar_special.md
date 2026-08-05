# Allonwheel — Fix `sidebar_special.php` + routing
*Rev. 2 giu 2026 — sidebar della sezione Special corretta e collegata alle pagine indicate nel dispatcher.*

## Cosa è stato corretto

- **`sidebar_special.php`**: rimosso l'errore di sintassi fatale nell'array di fallback (`['name' => '', => '']`) e la query su `06_company_products_special` (tabella di associazione, priva delle colonne `name`/`slug` usate dal template, e senza backtick). Ora elenca le **6 categorie speciali** da `CompanyManager::$products_special` con link alla directory aziende filtrata (`?special=<key>`), più box Suppliers, Featured supplier e Testimonial.
- **`include_sidebar.php`**: i rami `special` e `suppliers` iniziavano entrambi con `$_sb_in('06_company')`, così il ramo `special` (primo) catturava tutte le pagine `06_company/*` rendendo irraggiungibile il ramo suppliers. Corretto: `shelter_container.php` e `special_vehicles.php` → `sidebar_special`; `06_company/*` e `road_vehicles.php` → `sidebar_suppliers`.
- **`06_company/06_30_company_directory.php`** + **`libs/06_company.class.php`**: aggiunto il filtro `?special=<key>` (nuovo metodo `getCompaniesByProductSpecial()`, mirror di `getCompaniesByVehicleType()` sulla tabella `_special`) così i link delle categorie speciali non sono morti.

## Pagine che ora mostrano `sidebar_special.php`

`special_vehicles.php` e `shelter_container.php` (i file indicati nei rami del dispatcher). Includono già `include_sidebar.php`, quindi non serve modificarle.

---

## `sidebar_special.php`

```php
<?php
// ============================================================
// sidebar_special.php — Sidebar della sezione "Special".
//
// Pagine che la usano (risolte da include_sidebar.php):
//   - special_vehicles.php
//   - shelter_container.php   (ramo Shelter/Container -> Special del flowchart)
//
// Mostra: navigazione Suppliers + l'elenco delle CATEGORIE SPECIALI
// (catalogo unico in CompanyManager::$products_special) con link alla
// directory aziende filtrata per categoria speciale (?special=<key>),
// poi il logo azienda in evidenza e il testimonial.
//
// Produce SOLO box .sb_box (il wrapper #templatemo_sidebar lo apre la pagina).
// Nessuno stile nuovo (dir. 8): classi .sb_box / .sb_list esistenti.
// ============================================================
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/libs/06_company.class.php';

if (!isset($base_url)) {
    $base_url = '';
    $_s = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    foreach (['00_first', '01_login', '02_free_ads', '03_ads', '04_request_offer', '06_company', 'shared', '_admin'] as $f) {
        if (strpos($_s, '/' . $f . '/') !== false) { $base_url = '../'; break; }
    }
    unset($_s);
}

// Catalogo delle categorie speciali (slug => label). Unica fonte di verita'.
$special_categories = CompanyManager::$products_special;
?>

<!-- ===== Suppliers ===== -->
<div class="sb_box">
  <h3>Suppliers</h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php">Supplier directory</a></li>
    <li><a href="<?php echo $base_url; ?>shelter_container.php">Shelter &amp; Container</a></li>
    <li><a href="<?php echo $base_url; ?>road_vehicles.php">Road Vehicles</a></li>
    <li><a href="<?php echo $base_url; ?>special_vehicles.php">Special Vehicles</a></li>
  </ul>
</div>

<!-- ===== Special categories ===== -->
<div class="sb_box">
  <h3>Special categories</h3>
  <ul class="sb_list">
    <?php foreach ($special_categories as $slug => $label): ?>
      <li>
        <a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php?special=<?php echo urlencode($slug); ?>">
          <?php echo htmlspecialchars($label); ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<!-- ===== Featured supplier ===== -->
<?php include __DIR__ . '/sidebar_company_logo.php'; ?>
<div class="cleaner h20"></div>
<!-- ===== Testimonial ===== -->
<div class="sb_box">
  <h3>Testimonial</h3>
  Searching for the right race transporter used to mean weeks of phone calls.
  With All on Wheel we narrowed our shortlist to three suppliers in an afternoon,
  all matching our spec for a two-car deck with workshop access.
  <div class="cleaner h10"></div>
  <cite>Andrew L. <span>&mdash; GT Team Principal</span></cite>
  <div class="cleaner h10"></div>
  <a href="<?php echo $base_url; ?>contact.php" class="more">Contact us</a>
</div>
```

## `include_sidebar.php`

```php
<?php
// ============================================================
// include_sidebar.php — Dispatcher della sidebar PER SEZIONE
//
// Direttiva 17 (nuova — annulla e sostituisce la precedente versione
// condizionale loggato/statico):
//   - Ogni sezione del sito (Marketplace, Suppliers, Account, ...)
//     ha la PROPRIA sidebar con le OPZIONI DI SEZIONE.
//   - La sidebar viene risolta dalla SEZIONE CORRENTE (cartella/pagina),
//     NON piu' dallo stato di login.
//   - Le PAGINE PERSONALI dell'utente loggato (my_posts, profilo,
//     post ad, gestione azienda, logout) NON compaiono in nessuna
//     sidebar: stanno solo nell'header dell'area login.
//
// Le pagine includono questo file dentro il proprio <div id="templatemo_sidebar">,
// quindi i file di sezione qui inclusi devono produrre SOLO box .sb_box
// (nessun wrapper #templatemo_sidebar duplicato).
//
// UTILIZZO (unica riga in ogni pagina del sito):
//   Da root:       <?php include __DIR__ . '/include_sidebar.php';
//   Da subfolder:  <?php include __DIR__ . '/../include_sidebar.php';
// ============================================================

require_once __DIR__ . '/config/session_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_sidebar_root = __DIR__;

// ----- Base path automatico (se l'header non l'ha gia' calcolato) -----
if (!isset($base_url)) {
    $base_url = '';
    $_sb_script = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    foreach (['00_first', '01_login', '02_free_ads', '03_ads', '04_request_offer', '06_company', 'shared', '_admin'] as $f) {
        if (strpos($_sb_script, '/' . $f . '/') !== false) {
            $base_url = '../';
            break;
        }
    }
    unset($_sb_script);
}

// ----- Risoluzione della sezione corrente dal path dello script -----
$_sb_script   = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
$_sb_basename = basename($_sb_script);

$_sb_in = static function (string $folder) use ($_sb_script): bool {
    return strpos($_sb_script, '/' . $folder . '/') !== false;
};

if ($_sb_in('02_free_ads') || $_sb_in('03_ads') || $_sb_in('04_request_offer') || $_sb_in('shared')
    || in_array($_sb_basename, ['browse.php', 'ads.php', 'ad_post.php'], true)) {
    // Sezione Marketplace (Free Ads / Premium Ads / Request quotation)
    $_sb_section = 'marketplace';
} elseif (in_array($_sb_basename, ['shelter_container.php', 'special_vehicles.php'], true)) {
    // Sezione Special (ramo Shelter/Container -> Special del flowchart):
    // usa sidebar_special.php
    $_sb_section = 'special';
} elseif ($_sb_in('06_company')
    || in_array($_sb_basename, ['road_vehicles.php'], true)) {
    // Sezione Suppliers (directory aziende 06_company/* + Road): usa sidebar_suppliers.php
    $_sb_section = 'suppliers';
} elseif ($_sb_in('01_login')) {
    // Area Account (le pagine personali restano nell'header, non qui)
    $_sb_section = 'account';
} else {
    // Index, pagine editoriali, 00_first, _admin, portfolio -> sidebar di default
    $_sb_section = 'default';
}

// ----- Inclusione della sidebar di sezione -----
$_sb_file = $_sidebar_root . '/sidebar_' . $_sb_section . '.php';
if (is_file($_sb_file)) {
    include $_sb_file;
} else {
    include $_sidebar_root . '/sidebar_default.php';
}

unset($_sidebar_root, $_sb_script, $_sb_basename, $_sb_in, $_sb_section, $_sb_file);
```

## `libs/06_company.class.php`

```php
<?php
/**
 * Classe 06_company - Gestione Aziende Fornitori
 * Allonwheel
 */
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
  public static $products_special = [
    'racing_trailer'        => 'Racing trailer',
    'box_trailer'           => 'Box trailer',
    'motorhomes_mobilhomes' => 'Motorhomes & Mobilhomes',
    'hospitality_units'     => 'Hospitality units',
    'paddock_trailers'      => 'Paddock trailers',
    'special_shelter'       => 'Special Shelter',
  ];
  
    public function __construct($conn) {
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
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $company = $result->fetch_assoc();
    $stmt->close();
    return $company ? $company['id'] : false;
  }
  
  /**
   * Ottieni dati azienda per ID
   */
  public function getCompanyById($id) {
    $stmt = $this->conn->prepare("SELECT * FROM `06_company` WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $company = $result->fetch_assoc();
    $stmt->close();
    return $company;
  }
  
  /**
   * Ottieni dati azienda per user_id
   */
  public function getCompanyByUserId($user_id) {
    $stmt = $this->conn->prepare("SELECT * FROM `06_company` WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $company = $result->fetch_assoc();
    $stmt->close();
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
      referente_email, referente_telefono
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
    "isssssssssssssssssssss",
    $data['user_id'],
    $data['ragione_sociale'],
    $data['partita_iva'],
    $data['codice_fiscale'],
    $data['indirizzo'],
    $data['cap'],
    $data['citta'],
    $data['provincia'],
    $data['nazione'],
    $data['telefono'],
    $data['cellulare'],
    $data['fax'],
    $data['email'],
    $data['pec'],
    $data['sito_web'],
    $data['descrizione'],
    $data['logo'],
    $data['referente_nome'],
    $data['referente_cognome'],
    $data['referente_ruolo'],
    $data['referente_email'],
    $data['referente_telefono']
    );
    $result = $stmt->execute();
    $insert_id = $result ? $stmt->insert_id : false;
    $stmt->close();
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
    $stmt->bind_param(
    "sssssssssssssssssssssii",
    $data['ragione_sociale'],
    $data['partita_iva'],
    $data['codice_fiscale'],
    $data['indirizzo'],
    $data['cap'],
    $data['citta'],
    $data['provincia'],
    $data['nazione'],
    $data['telefono'],
    $data['cellulare'],
    $data['fax'],
    $data['email'],
    $data['pec'],
    $data['sito_web'],
    $data['descrizione'],
    $data['logo'],
    $data['referente_nome'],
    $data['referente_cognome'],
    $data['referente_ruolo'],
    $data['referente_email'],
    $data['referente_telefono'],
    $id,
    $data['user_id']
    );
    $result = $stmt->execute();
    $stmt->close();
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
    $stmt->bind_param("ii", $id, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    
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
  
  /**
   * Salva prodotti selezionati per l'azienda
   */
  public function saveProducts($company_id, $products_data) {
    // Elimina prodotti esistenti
    $stmt = $this->conn->prepare("DELETE FROM `06_company_products` WHERE company_id = ?");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $stmt->close();
    
    if (empty($products_data)) return true;
    
    $stmt = $this->conn->prepare(
    "INSERT INTO `06_company_products` 
    (company_id, product_key, note, certificazioni_prodotto, campioni_gratuiti, assistenza_posa, progettazione_supporto, schede_tecniche) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    foreach ($products_data as $product) {
    $stmt->bind_param(
      "issiiiii",
      $company_id,
      $product['product_key'],
      $product['note'],
      $product['certificazioni_prodotto'],
      $product['campioni_gratuiti'],
      $product['assistenza_posa'],
      $product['progettazione_supporto'],
      $product['schede_tecniche']
    );
    $stmt->execute();
    }
    $stmt->close();
    return true;
  }
  
  /**
   * Ottieni prodotti dell'azienda
   */
  public function getProducts($company_id) {
    $stmt = $this->conn->prepare("SELECT * FROM `06_company_products` WHERE company_id = ? ORDER BY product_key");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
    $products[$row['product_key']] = $row;
    }
    $stmt->close();
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
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $stmt->close();
    
    if (empty($services_data)) return true;
    
    $stmt = $this->conn->prepare(
    "INSERT INTO `06_company_services` (company_id, service_key, note) VALUES (?, ?, ?)"
    );
    
    foreach ($services_data as $service) {
    $stmt->bind_param("iss", $company_id, $service['service_key'], $service['note']);
    $stmt->execute();
    }
    $stmt->close();
    return true;
  }
  
  /**
   * Ottieni servizi dell'azienda
   */
  public function getServices($company_id) {
    $stmt = $this->conn->prepare("SELECT * FROM `06_company_services` WHERE company_id = ? ORDER BY service_key");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $services = [];
    while ($row = $result->fetch_assoc()) {
    $services[$row['service_key']] = $row;
    }
    $stmt->close();
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
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $images = [];
    while ($row = $result->fetch_assoc()) {
    $images[] = $row;
    }
    $stmt->close();
    return $images;
  }
  
  /**
   * Inserisci immagine gallery
   */
  public function countGalleryImages($company_id) {
    $stmt = $this->conn->prepare("SELECT COUNT(*) FROM `06_company_gallery` WHERE company_id = ?");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return (int)$count;
  }

  public function insertGalleryImage($company_id, $user_id, $immagine, $didascalia = '', $ordine = 0) {
    $stmt = $this->conn->prepare(
    "INSERT INTO `06_company_gallery` (company_id, user_id, immagine, didascalia, ordine) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iissi", $company_id, $user_id, $immagine, $didascalia, $ordine);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
  }
  
  /**
   * Elimina immagine gallery
   */
  public function deleteGalleryImage($image_id, $user_id) {
    // Ottieni nome file prima di eliminare
    $stmt = $this->conn->prepare("SELECT immagine FROM `06_company_gallery` WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $image_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $image = $result->fetch_assoc();
    $stmt->close();
    
    if (!$image) return false;
    
    // Elimina dal database
    $stmt = $this->conn->prepare("DELETE FROM `06_company_gallery` WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $image_id, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    
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
     WHERE c.attiva = 1 
     ORDER BY c.ragione_sociale ASC"
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $companies = [];
    while ($row = $result->fetch_assoc()) {
    $companies[] = $row;
    }
    $stmt->close();
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
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
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
     WHERE c.attiva = 1
       AND (c.ragione_sociale LIKE ? OR c.citta LIKE ? OR c.descrizione LIKE ?)
     ORDER BY c.ragione_sociale"
    );
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $companies = [];
    while ($row = $result->fetch_assoc()) {
    $companies[] = $row;
    }
    $stmt->close();
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
    $sql .= " GROUP BY c.id ORDER BY c.ragione_sociale";
    $stmt = $this->conn->prepare($sql);
    if (!$stmt) { return []; }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $companies = [];
    while ($row = $result->fetch_assoc()) {
    $companies[] = $row;
    }
    $stmt->close();
    return $companies;
  }

  /**
   * Nome leggibile del tipo veicolo a partire dallo slug (per intestazione).
   */
  public function getVehicleTypeName($slug) {
    $stmt = $this->conn->prepare("SELECT name FROM vehicle_types WHERE slug = ? LIMIT 1");
    if (!$stmt) { return null; }
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
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
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $stmt->close();

    if (empty($special_data)) return true;

    $stmt = $this->conn->prepare(
      "INSERT INTO `06_company_products_special` (company_id, product_key, note) VALUES (?, ?, ?)"
    );
    foreach ($special_data as $product) {
      $stmt->bind_param("iss", $company_id, $product['product_key'], $product['note']);
      $stmt->execute();
    }
    $stmt->close();
    return true;
  }

  /**
   * Ottieni le categorie speciali dichiarate dall'azienda (key => row).
   */
  public function getProductsSpecial($company_id) {
    $stmt = $this->conn->prepare("SELECT * FROM `06_company_products_special` WHERE company_id = ? ORDER BY product_key");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $special = [];
    while ($row = $result->fetch_assoc()) {
      $special[$row['product_key']] = $row;
    }
    $stmt->close();
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
      $stmt->bind_param($types, ...$keys);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($row = $res->fetch_assoc()) {
        // Dedup per id azienda (un'azienda riceve UNA sola email)
        $companies[(int)$row['id']] = $row;
      }
      $stmt->close();
    };

    $fetch('06_company_products', $regular_keys);
    $fetch('06_company_products_special', $special_keys);

    return array_values($companies);
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
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $companies = [];
    while ($row = $result->fetch_assoc()) {
      $companies[] = $row;
    }
    $stmt->close();
    return $companies;
  }
}
```

## `06_company/06_30_company_directory.php`

```php
<?php
/**
 * 06_30_company_directory.php — Elenco pubblico aziende fornitrici
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/06_company.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

$cm = new CompanyManager($conn);

// Ricerca + filtro tipo veicolo (vtype) opzionali
$search = trim($_GET['q'] ?? '');
if ($search === 'Search suppliers') { $search = ''; }   // placeholder
$vtype  = trim($_GET['vtype'] ?? '');
$special = trim($_GET['special'] ?? '');

$vtype_name = '';
if ($special !== '') {
  // Filtro per categoria SPECIALE (06_company_products_special)
  $vtype_name = CompanyManager::$products_special[$special] ?? $special;
  $companies  = $cm->getCompaniesByProductSpecial($special, $search);
} elseif ($vtype !== '') {
  $vtype_name = $cm->getVehicleTypeName($vtype) ?? $vtype;
  $companies  = $cm->getCompaniesByVehicleType($vtype, $search);
} elseif ($search !== '') {
  $companies = $cm->searchCompanies($search);
} else {
  $companies = $cm->getAllActiveCompanies();
}

$total = $cm->countActiveCompanies();

$asset_base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$logo_base  = $asset_base . '/upload_image/06_company/thumbnail/';
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Supplier Directory</title>
<meta name="keywords" content="supplier directory, automotive suppliers, vehicle suppliers" />
<meta name="description" content="All on Wheel Supplier Directory - Find automotive suppliers and service providers" />
<meta name="robots" content="index, follow" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<!--<link href="css_pirobox/white/style.css" media="screen" title="white" rel="stylesheet" type="text/css" />
<link href="css_pirobox/black/style.css" media="screen" title="black" rel="stylesheet" type="text/css" />-->
<!--////// END  \\\\\\\-->

<!--////// INCLUDE THE JS AND PIROBOX OPTION IN YOUR HEADER  \\\\\\\-->
<!--////// END  \\\\\\\-->
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="../js/piroBox.1_2.js"></script>
<script type="text/javascript" src="../js/site_init.js"></script>
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <?php include __DIR__ . '/../header.php'; ?>
  </div>

  <div id="content_top">
    <div id="page_title">Supplier Directory</div>
    <div id="search_box">
    <form action="06_30_company_directory.php" method="get">
      <?php if ($vtype !== ''): ?>
      <input type="hidden" name="vtype" value="<?php echo htmlspecialchars($vtype); ?>" />
      <?php endif; ?>
      <?php if ($special !== ''): ?>
      <input type="hidden" name="special" value="<?php echo htmlspecialchars($special); ?>" />
      <?php endif; ?>
      <input type="text" value="<?php echo htmlspecialchars($search ?: 'Search suppliers'); ?>"
        name="q" size="10" id="searchfield" title="searchfield"
        onfocus="clearText(this)" onblur="clearText(this)" />
      <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="templatemo_content">

    <?php if (empty($companies)): ?>
    <div class="post_box">
      <h2>No suppliers found</h2>
      <p>There are no suppliers to display<?php echo ($search !== '' || $vtype !== '' || $special !== '') ? ' matching the current filter' : ''; ?>.</p>
    </div>
    <?php else: ?>

    <?php foreach ($companies as $c):
      $logo_file = trim((string)($c['logo'] ?? ''));
      if ($logo_file !== '') {
        $logo_url = $logo_base . $logo_file;
      } else {
        $logo_url = '../images/no_image.jpg';
      }
    ?>
      <div class="post_box">
        <h2><?php echo htmlspecialchars($c['ragione_sociale']); ?></h2>

        <ul class="gallery">
        <li>
          <a class="pirobox"
           href="<?php echo htmlspecialchars($logo_url); ?>"
           title="<?php echo htmlspecialchars($c['ragione_sociale']); ?>">
            <img src="<?php echo htmlspecialchars($logo_url); ?>"
             alt="<?php echo htmlspecialchars($c['ragione_sociale']); ?>"
             width="220" height="150" border="0"
             onerror="this.onerror=null;this.src='../images/no_image.jpg';" />
          </a>
        </li>
        </ul>

        <p><strong>Location:</strong> <?php echo htmlspecialchars($c['citta']); ?> (<?php echo htmlspecialchars($c['provincia']); ?>) &mdash; <?php echo htmlspecialchars($c['nazione']); ?></p>

        <p align="justify">
        <?php
        $desc = (string)($c['descrizione'] ?? '');
        $short = mb_strlen($desc) > 200 ? mb_substr($desc, 0, 200) . '…' : $desc;
        echo nl2br(htmlspecialchars($short));
        ?>
        </p>

        <div class="post_meta">
        <span class="cat">
          <?php if (!empty($c['num_products'])): ?>Products: <strong><?php echo (int)$c['num_products']; ?></strong> | <?php endif; ?>
          <?php if (!empty($c['num_services'])): ?>Services: <strong><?php echo (int)$c['num_services']; ?></strong> | <?php endif; ?>
          Since: <strong><?php echo date('d/m/Y', strtotime($c['data_inserimento'])); ?></strong>
        </span>
        <a href="06_02_view_company.php?id=<?php echo (int)$c['id']; ?>"
         class="more float_r">View profile</a>
        <div class="cleaner"></div>
        </div>
      </div>
    <?php endforeach; ?>

    <?php endif; ?>

  </div>

<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>

  <div class="cleaner"></div>

  <?php include __DIR__ . '/../footer.php'; ?>

</div>
</body>
</html>
```

