<?php
/* -----------------------------------------------------------------------------------------
   MRH 2026: Reklamationsmodul – Deutsche Sprachdatei (Frontend)
   ---------------------------------------------------------------------------------------*/

  // Authentifizierung
  define('ENTRY_RECLAMATION_ORDERS_ID_ERROR', 'Bitte geben Sie Ihre Bestellnummer ein.');
  define('ENTRY_ORDERS_ID_TEXT', '*');

  // Ueberschriften
  define('TEXT_RECLAMATION_HEADING', 'Reklamation einreichen');
  define('TEXT_RECLAMATION_AUTH_TITLE', 'Bestellung verifizieren');
  define('TEXT_RECLAMATION_INFO', 'Ihre Bestellung %s vom %s');
  define('TEXT_RECLAMATION_INTRO', 'Bitte w&auml;hlen Sie die Produkte aus, die Sie reklamieren m&ouml;chten, und beschreiben Sie das Problem m&ouml;glichst genau. Fotos helfen uns bei der schnelleren Bearbeitung.');
  define('TEXT_RECLAMATION_SELECT_PRODUCTS', 'W&auml;hlen Sie die betroffenen Produkte aus:');
  define('TEXT_RECLAMATION_NO_PRODUCTS', 'Es gibt keine Produkte, die reklamiert werden k&ouml;nnen.');
  define('TEXT_RECLAMATION_ORDER_NOT_FOUND', 'Bestellung nicht gefunden. Bitte &uuml;berpr&uuml;fen Sie Ihre Bestellnummer und E-Mail-Adresse.');
  define('TEXT_RECLAMATION_NOT_SHIPPED', 'Diese Bestellung wurde noch nicht versendet. Eine Reklamation ist erst nach Erhalt der Ware m&ouml;glich.');
  define('TEXT_RECLAMATION_RATE_LIMIT', 'F&uuml;r diese Bestellung wurden bereits die maximale Anzahl an Reklamationen eingereicht. Bitte kontaktieren Sie unseren Kundenservice.');
  define('TEXT_RECLAMATION_EXPIRED', 'Die Reklamationsfrist f&uuml;r diese Bestellung ist abgelaufen (max. 60 Tage nach Bestelldatum). Bitte kontaktieren Sie unseren Kundenservice.');

  // Erfolg
  define('TEXT_RECLAMATION_SUCCESS', 'Vielen Dank f&uuml;r Ihre Reklamation.<br/>Wir haben Ihre Anfrage erfolgreich erhalten und werden sie schnellstm&ouml;glich bearbeiten.<br/><br/>Unser Team pr&uuml;ft Ihre Angaben und k&uuml;mmert sich um die n&auml;chsten Schritte. Sie erhalten in K&uuml;rze eine Best&auml;tigung per E-Mail.');
  define('TEXT_RECLAMATION_SUCCESS_MSG', 'Reklamation erfolgreich eingereicht.');
  define('TEXT_RECLAMATION_SUCCESS_SUMMARY', 'Zusammenfassung der reklamierten Produkte:');

  // Pflanzen
  define('TEXT_RECLAMATION_PLANT_EXCLUDED', 'Reklamation ausgeschlossen');
  define('TEXT_RECLAMATION_PLANT_EXCLUDED_INFO', 'Lebende Pflanzen (Stecklinge, S&auml;mlinge) sind gem&auml;&szlig; &sect;&nbsp;18 Abs.&nbsp;1 Nr.&nbsp;4 FAGG von der Reklamation ausgeschlossen, da sie schnell verderben k&ouml;nnen und ein R&uuml;cktransport die Qualit&auml;t und Gesundheit der Pflanze nicht gew&auml;hrleisten kann.');

  // Samen
  define('TEXT_RECLAMATION_SEED_BADGE', 'Samen');
  define('TEXT_RECLAMATION_SEED_KULANZ_TITLE', 'Kulanz-Reklamation');
  define('TEXT_RECLAMATION_SEED_KULANZ_INFO', 'Samen sind Naturprodukte. Eine Keimgarantie ist rechtlich nicht m&ouml;glich. Wir pr&uuml;fen Ihre Reklamation auf Kulanzbasis. Bitte beantworten Sie die folgenden Fragen ehrlich und laden Sie Fotos hoch.');
  define('TEXT_RECLAMATION_SEED_NOT_GERMINATED', 'Samen nicht gekeimt');
  define('TEXT_RECLAMATION_SEED_DAMAGED', 'Besch&auml;digt bei Lieferung');
  define('TEXT_RECLAMATION_SEED_WRONG_STRAIN', 'Falsche Sorte erhalten');
  define('TEXT_RECLAMATION_SEED_IMAGE_REQUIRED', 'F&uuml;r Samen-Reklamationen ist mindestens ein Foto erforderlich.');

  // Samen-Fragen
  define('TEXT_RECLAMATION_SEED_QUESTIONS_TITLE', 'Fragen zur Keimung');
  define('TEXT_RECLAMATION_SEED_GERM_METHOD', 'Keimmethode');
  define('TEXT_RECLAMATION_SEED_GERM_PAPER', 'Feuchtes K&uuml;chenpapier');
  define('TEXT_RECLAMATION_SEED_GERM_SOIL', 'Direkt in Erde');
  define('TEXT_RECLAMATION_SEED_GERM_JIFFY', 'Jiffy-Pellet');
  define('TEXT_RECLAMATION_SEED_GERM_ROCKWOOL', 'Steinwolle');
  define('TEXT_RECLAMATION_SEED_GERM_WATER', 'Wasserglas');
  define('TEXT_RECLAMATION_SEED_TEMP', 'Temperatur bei Keimung');
  define('TEXT_RECLAMATION_SEED_TEMP_UNDER18', 'Unter 18&deg;C');
  define('TEXT_RECLAMATION_SEED_TEMP_18_22', '18&ndash;22&deg;C');
  define('TEXT_RECLAMATION_SEED_TEMP_22_26', '22&ndash;26&deg;C');
  define('TEXT_RECLAMATION_SEED_TEMP_OVER26', '&Uuml;ber 26&deg;C');
  define('TEXT_RECLAMATION_SEED_TEMP_UNKNOWN', 'Unbekannt');
  define('TEXT_RECLAMATION_SEED_DAYS', 'Wie viele Tage gewartet?');
  define('TEXT_RECLAMATION_SEED_DAYS_1_3', '1&ndash;3 Tage');
  define('TEXT_RECLAMATION_SEED_DAYS_4_7', '4&ndash;7 Tage');
  define('TEXT_RECLAMATION_SEED_DAYS_8_14', '8&ndash;14 Tage');
  define('TEXT_RECLAMATION_SEED_DAYS_OVER14', '&Uuml;ber 14 Tage');
  define('TEXT_RECLAMATION_SEED_COUNT', 'Anzahl nicht gekeimter Samen');
  define('TEXT_RECLAMATION_SEED_STORED', 'Ich best&auml;tige, dass die Samen k&uuml;hl, trocken und dunkel gelagert wurden.');
  define('TEXT_RECLAMATION_SEED_EXPECTED', 'Erwartete Sorte');
  define('TEXT_RECLAMATION_SEED_EXPECTED_PH', 'z.B. Northern Lights Auto');
  define('TEXT_RECLAMATION_SEED_RECEIVED', 'Erhaltene Sorte');
  define('TEXT_RECLAMATION_SEED_RECEIVED_PH', 'z.B. White Widow Auto');

  // Zubehoer / Gewaehrleistung
  define('TEXT_RECLAMATION_ACCESSORY_BADGE', 'Zubeh&ouml;r');
  define('TEXT_RECLAMATION_WARRANTY_EXPIRED', 'Gew&auml;hrleistung abgelaufen');
  define('TEXT_RECLAMATION_WARRANTY_EXPIRED_INFO', 'Die gesetzliche Gew&auml;hrleistungsfrist von 2 Jahren (&sect;&nbsp;922 ABGB) ist f&uuml;r dieses Produkt abgelaufen.');

  // Reklamationsgruende
  define('TEXT_RECLAMATION_REASON_LABEL', 'Reklamationsgrund');
  define('TEXT_RECLAMATION_REASON_SELECT', '-- Bitte w&auml;hlen --');
  define('TEXT_RECLAMATION_REASON_TRANSPORT', 'Transportschaden');
  define('TEXT_RECLAMATION_REASON_WRONG', 'Falsches Produkt erhalten');
  define('TEXT_RECLAMATION_REASON_INCOMPLETE', 'Lieferung unvollst&auml;ndig');
  define('TEXT_RECLAMATION_REASON_QUALITY', 'Qualit&auml;tsmangel');
  define('TEXT_RECLAMATION_REASON_PACKAGING', 'Verpackung besch&auml;digt');
  define('TEXT_RECLAMATION_REASON_OTHER', 'Sonstiger Grund');

  // Beschreibung
  define('TEXT_RECLAMATION_DESCRIPTION_LABEL', 'Beschreibung des Problems');
  define('TEXT_RECLAMATION_DESCRIPTION_PH', 'Bitte beschreiben Sie das Problem m&ouml;glichst genau...');
  define('TEXT_RECLAMATION_DESCRIPTION_HINT', 'Max. 2000 Zeichen');

  // Menge
  define('TEXT_RECLAMATION_QTY_LABEL', 'Reklamierte Menge');
  define('TEXT_RECLAMATION_QTY_MAX', 'Max.:');
  define('TEXT_RECLAMATION_QTY_ORDERED', 'Bestellt');
  define('TEXT_RECLAMATION_PRICE', 'Preis');
  define('TEXT_RECLAMATION_NO_PRODUCTS_SELECTED', 'Bitte w&auml;hlen Sie mindestens ein Produkt aus.');

  // Bildupload
  define('TEXT_RECLAMATION_UPLOAD_HEADING', 'Fotos hochladen');
  define('TEXT_RECLAMATION_UPLOAD_INFO', 'Laden Sie Fotos des besch&auml;digten Produkts, der Verpackung oder des Problems hoch. Fotos beschleunigen die Bearbeitung erheblich.');
  define('TEXT_RECLAMATION_UPLOAD_HINT', 'Max. 5 Bilder, je max. 10 MB. Erlaubte Formate: JPG, PNG, HEIC, WebP.');
  define('TEXT_RECLAMATION_UPLOAD_RECOMMENDED', 'Ein Foto ist nicht Pflicht, erh&ouml;ht aber die Chance auf eine schnelle und positive Bearbeitung deiner Reklamation erheblich!');
  define('TEXT_RECLAMATION_UPLOAD_MAX_WARNING', 'Maximal 5 Bilder erlaubt. Nur die ersten 5 werden hochgeladen.');
  define('TEXT_RECLAMATION_IMAGE_RIGHTS', 'Ich best&auml;tige, dass ich die Rechte an den hochgeladenen Bildern besitze und diese zur Bearbeitung meiner Reklamation verwendet werden d&uuml;rfen.');
  define('TEXT_RECLAMATION_IMAGE_RIGHTS_ERROR', 'Bitte best&auml;tigen Sie die Bildrechte, bevor Sie Fotos hochladen.');
  define('TEXT_RECLAMATION_UPLOADED_IMAGES', 'Hochgeladene Bilder');

  // E-Mail
  define('EMAIL_RECLAMATION_SUBJECT', 'Reklamation Bestellung {$nr}');
  define('EMAIL_RECLAMATION_CONFIRM_SUBJECT', 'Best&auml;tigung Ihrer Reklamation – Bestellung {$nr}');

  // Admin
  define('TEXT_RECLAMATION_ADMIN_STATUS_UPDATED', 'Reklamation #%s: Status auf "%s" ge&auml;ndert.');
  define('TABLE_HEADING_RECLAMATION', 'Reklamationen');

  // Admin-Status
  define('TEXT_RECLAMATION_STATUS_OPEN', 'Offen');
  define('TEXT_RECLAMATION_STATUS_IN_PROGRESS', 'In Bearbeitung');
  define('TEXT_RECLAMATION_STATUS_RESOLVED', 'Gel&ouml;st');
  define('TEXT_RECLAMATION_STATUS_REJECTED', 'Abgelehnt');
  define('TEXT_RECLAMATION_STATUS_CLOSED', 'Geschlossen');

  // Tabellen-Header
  define('HEADER_ARTICLE', 'Artikel');
  define('HEADER_QTY', 'Menge');
  define('HEADER_MODEL', 'Art.-Nr.');

  // Buttons
  define('IMAGE_BUTTON_RECLAMATION', 'Reklamation absenden');

  // Bestellhistorie Button
  define('TEXT_RECLAMATION_BTN_SUBMIT', 'Reklamation einreichen');
  define('TEXT_RECLAMATION_BTN_EXPIRED', 'Reklamationsfrist abgelaufen (60 Tage)');
  define('TEXT_RECLAMATION_BTN_TOO_EARLY', 'Reklamation noch nicht m&ouml;glich (10 Tage Wartezeit)');
