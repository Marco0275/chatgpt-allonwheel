-- ============================================================
-- macro_intro_text.sql — P0.2: copy introduttivo delle 5 macro
-- (product_macros.intro_text). Senza, le intro su browse.php?macro=
-- non compaiono. Target MySQL 5.7. IDEMPOTENTE: solo UPDATE per slug,
-- rieseguibile senza effetti collaterali. Nessuna ALTER, nessun dato
-- distrutto (dir. 9). hero_image lasciato a Marco (asset reali).
-- ============================================================

UPDATE `product_macros` SET `intro_text` =
  'The complete paddock solution for professional race teams. Garage, workshop and office in one transporter, with electrical and HVAC systems, telemetry connections, tail lift and belly storage. From two-car decks to three-car configurations with demountable upper deck, browse current offers from sellers and rental operators worldwide.'
  WHERE `slug` = 'race-trailer';

UPDATE `product_macros` SET `intro_text` =
  'Your brand on the road. Multi-storey hospitality structures with office space, driver areas, dining rooms, kitchens, roof terraces and sponsors lounges, the pinnacle of coachbuilding found at the front of every elite paddock. Explore new builds and second-hand units, and connect with certified bodybuilders.'
  WHERE `slug` = 'hospitality';

UPDATE `product_macros` SET `intro_text` =
  'Medical and care units built for the demands of the paddock. Self-contained mobile clinics and medical centres for race events and large-scale activations, with treatment rooms, equipment bays and integrated power systems on a truck or trailer base. Browse available units and specialist builders.'
  WHERE `slug` = 'mobile-clinic';

UPDATE `product_macros` SET `intro_text` =
  'Deployable shelters and converted containers for any environment. Modular shelters and ISO-container conversions for storage, command posts, workshops and technical rooms, rugged, transportable and quick to deploy at the circuit or on site. Browse listings or connect with a builder.'
  WHERE `slug` = 'shelter-container';

UPDATE `product_macros` SET `intro_text` =
  'Bespoke builds when nothing off-the-shelf will do. One-off roadshow units, brand-experience vehicles and fully tailored paddock structures designed around your team or campaign. Tell us what you need and we will connect you with the right specialist.'
  WHERE `slug` = 'custom-projects';
