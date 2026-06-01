<?php
/* -----------------------------------------------------------------------------------------
   MRH 2026: Module de r&eacute;clamation – Fichier de langue fran&ccedil;ais (Frontend)
   ---------------------------------------------------------------------------------------*/

  // Authentification
  define('ENTRY_RECLAMATION_ORDERS_ID_ERROR', 'Veuillez saisir votre num&eacute;ro de commande.');
  define('ENTRY_ORDERS_ID_TEXT', '*');

  // En-t&ecirc;tes
  define('TEXT_RECLAMATION_HEADING', 'Soumettre une r&eacute;clamation');
  define('TEXT_RECLAMATION_AUTH_TITLE', 'V&eacute;rifier votre commande');
  define('TEXT_RECLAMATION_INFO', 'Votre commande %s du %s');
  define('TEXT_RECLAMATION_INTRO', 'Veuillez s&eacute;lectionner les produits concern&eacute;s et d&eacute;crire le probl&egrave;me aussi pr&eacute;cis&eacute;ment que possible. Les photos nous aident &agrave; traiter votre dossier plus rapidement.');
  define('TEXT_RECLAMATION_SELECT_PRODUCTS', 'S&eacute;lectionnez les produits concern&eacute;s :');
  define('TEXT_RECLAMATION_NO_PRODUCTS', 'Il n\'y a aucun produit pouvant faire l\'objet d\'une r&eacute;clamation.');
  define('TEXT_RECLAMATION_ORDER_NOT_FOUND', 'Commande introuvable. Veuillez v&eacute;rifier votre num&eacute;ro de commande et votre adresse e-mail.');
  define('TEXT_RECLAMATION_NOT_SHIPPED', 'Cette commande n\'a pas encore &eacute;t&eacute; exp&eacute;di&eacute;e. Une r&eacute;clamation n\'est possible qu\'apr&egrave;s r&eacute;ception de la marchandise.');
  define('TEXT_RECLAMATION_RATE_LIMIT', 'Le nombre maximum de r&eacute;clamations a d&eacute;j&agrave; &eacute;t&eacute; soumis pour cette commande. Veuillez contacter notre service client.');

  // Succ&egrave;s
  define('TEXT_RECLAMATION_SUCCESS', 'Merci pour votre r&eacute;clamation.<br/>Nous avons bien re&ccedil;u votre demande et la traiterons dans les meilleurs d&eacute;lais.<br/><br/>Notre &eacute;quipe examine vos informations et s\'occupe des prochaines &eacute;tapes. Vous recevrez prochainement une confirmation par e-mail.');
  define('TEXT_RECLAMATION_SUCCESS_MSG', 'R&eacute;clamation soumise avec succ&egrave;s.');
  define('TEXT_RECLAMATION_SUCCESS_SUMMARY', 'R&eacute;sum&eacute; des produits r&eacute;clam&eacute;s :');

  // Plantes
  define('TEXT_RECLAMATION_PLANT_EXCLUDED', 'R&eacute;clamation exclue');
  define('TEXT_RECLAMATION_PLANT_EXCLUDED_INFO', 'Les plantes vivantes (boutures, semis) sont exclues des r&eacute;clamations conform&eacute;ment au &sect;&nbsp;18 al.&nbsp;1 n&deg;&nbsp;4 FAGG, car elles peuvent p&eacute;rir rapidement et le transport retour ne peut garantir la qualit&eacute; et la sant&eacute; de la plante.');

  // Graines
  define('TEXT_RECLAMATION_SEED_BADGE', 'Graines');
  define('TEXT_RECLAMATION_SEED_KULANZ_TITLE', 'R&eacute;clamation &agrave; titre gracieux');
  define('TEXT_RECLAMATION_SEED_KULANZ_INFO', 'Les graines sont des produits naturels. Une garantie de germination n\'est pas juridiquement possible. Nous examinerons votre r&eacute;clamation &agrave; titre gracieux. Veuillez r&eacute;pondre honn&ecirc;tement aux questions suivantes et t&eacute;l&eacute;charger des photos.');
  define('TEXT_RECLAMATION_SEED_NOT_GERMINATED', 'Graines non germ&eacute;es');
  define('TEXT_RECLAMATION_SEED_DAMAGED', 'Endommag&eacute;es &agrave; la livraison');
  define('TEXT_RECLAMATION_SEED_WRONG_STRAIN', 'Mauvaise vari&eacute;t&eacute; re&ccedil;ue');
  define('TEXT_RECLAMATION_SEED_IMAGE_REQUIRED', 'Au moins une photo est requise pour les r&eacute;clamations de graines.');

  // Questions sur les graines
  define('TEXT_RECLAMATION_SEED_QUESTIONS_TITLE', 'Questions sur la germination');
  define('TEXT_RECLAMATION_SEED_GERM_METHOD', 'M&eacute;thode de germination');
  define('TEXT_RECLAMATION_SEED_GERM_PAPER', 'Essuie-tout humide');
  define('TEXT_RECLAMATION_SEED_GERM_SOIL', 'Directement en terre');
  define('TEXT_RECLAMATION_SEED_GERM_JIFFY', 'Pastille Jiffy');
  define('TEXT_RECLAMATION_SEED_GERM_ROCKWOOL', 'Laine de roche');
  define('TEXT_RECLAMATION_SEED_GERM_WATER', 'Verre d\'eau');
  define('TEXT_RECLAMATION_SEED_TEMP', 'Temp&eacute;rature lors de la germination');
  define('TEXT_RECLAMATION_SEED_TEMP_UNDER18', 'Moins de 18&deg;C');
  define('TEXT_RECLAMATION_SEED_TEMP_18_22', '18&ndash;22&deg;C');
  define('TEXT_RECLAMATION_SEED_TEMP_22_26', '22&ndash;26&deg;C (optimal)');
  define('TEXT_RECLAMATION_SEED_TEMP_OVER26', 'Plus de 26&deg;C');
  define('TEXT_RECLAMATION_SEED_TEMP_UNKNOWN', 'Inconnu');
  define('TEXT_RECLAMATION_SEED_DAYS', 'Combien de jours avez-vous attendu ?');
  define('TEXT_RECLAMATION_SEED_DAYS_1_3', '1&ndash;3 jours');
  define('TEXT_RECLAMATION_SEED_DAYS_4_7', '4&ndash;7 jours');
  define('TEXT_RECLAMATION_SEED_DAYS_8_14', '8&ndash;14 jours');
  define('TEXT_RECLAMATION_SEED_DAYS_OVER14', 'Plus de 14 jours');
  define('TEXT_RECLAMATION_SEED_COUNT', 'Nombre de graines non germ&eacute;es');
  define('TEXT_RECLAMATION_SEED_STORED', 'Je confirme que les graines ont &eacute;t&eacute; conserv&eacute;es dans un endroit frais, sec et sombre.');
  define('TEXT_RECLAMATION_SEED_EXPECTED', 'Vari&eacute;t&eacute; attendue');
  define('TEXT_RECLAMATION_SEED_EXPECTED_PH', 'p. ex. Northern Lights Auto');
  define('TEXT_RECLAMATION_SEED_RECEIVED', 'Vari&eacute;t&eacute; re&ccedil;ue');
  define('TEXT_RECLAMATION_SEED_RECEIVED_PH', 'p. ex. White Widow Auto');

  // Accessoires / Garantie
  define('TEXT_RECLAMATION_ACCESSORY_BADGE', 'Accessoires');
  define('TEXT_RECLAMATION_WARRANTY_EXPIRED', 'Garantie expir&eacute;e');
  define('TEXT_RECLAMATION_WARRANTY_EXPIRED_INFO', 'Le d&eacute;lai de garantie l&eacute;gal de 2 ans (&sect;&nbsp;922 ABGB) a expir&eacute; pour ce produit.');

  // Motifs de r&eacute;clamation
  define('TEXT_RECLAMATION_REASON_LABEL', 'Motif de la r&eacute;clamation');
  define('TEXT_RECLAMATION_REASON_SELECT', '-- Veuillez s&eacute;lectionner --');
  define('TEXT_RECLAMATION_REASON_TRANSPORT', 'Dommage de transport');
  define('TEXT_RECLAMATION_REASON_WRONG', 'Mauvais produit re&ccedil;u');
  define('TEXT_RECLAMATION_REASON_INCOMPLETE', 'Livraison incompl&egrave;te');
  define('TEXT_RECLAMATION_REASON_QUALITY', 'D&eacute;faut de qualit&eacute;');
  define('TEXT_RECLAMATION_REASON_PACKAGING', 'Emballage endommag&eacute;');
  define('TEXT_RECLAMATION_REASON_OTHER', 'Autre motif');

  // Description
  define('TEXT_RECLAMATION_DESCRIPTION_LABEL', 'Description du probl&egrave;me');
  define('TEXT_RECLAMATION_DESCRIPTION_PH', 'Veuillez d&eacute;crire le probl&egrave;me aussi pr&eacute;cis&eacute;ment que possible...');
  define('TEXT_RECLAMATION_DESCRIPTION_HINT', 'Max. 2 000 caract&egrave;res');

  // Quantit&eacute;
  define('TEXT_RECLAMATION_QTY_LABEL', 'Quantit&eacute; r&eacute;clam&eacute;e');
  define('TEXT_RECLAMATION_QTY_MAX', 'Max. :');
  define('TEXT_RECLAMATION_QTY_ORDERED', 'Command&eacute;');
  define('TEXT_RECLAMATION_PRICE', 'Prix');
  define('TEXT_RECLAMATION_NO_PRODUCTS_SELECTED', 'Veuillez s&eacute;lectionner au moins un produit.');

  // T&eacute;l&eacute;chargement d\'images
  define('TEXT_RECLAMATION_UPLOAD_HEADING', 'T&eacute;l&eacute;charger des photos');
  define('TEXT_RECLAMATION_UPLOAD_INFO', 'T&eacute;l&eacute;chargez des photos du produit endommag&eacute;, de l\'emballage ou du probl&egrave;me. Les photos acc&eacute;l&egrave;rent consid&eacute;rablement le traitement.');
  define('TEXT_RECLAMATION_UPLOAD_HINT', 'Max. 5 images, chacune max. 10 Mo. Formats autoris&eacute;s : JPG, PNG, HEIC, WebP.');
  define('TEXT_RECLAMATION_UPLOAD_RECOMMENDED', 'Une photo n&rsquo;est pas obligatoire, mais augmente consid&eacute;rablement les chances d&rsquo;un traitement rapide et positif de votre r&eacute;clamation !');
  define('TEXT_RECLAMATION_UPLOAD_MAX_WARNING', 'Maximum 5 images autoris&eacute;es. Seules les 5 premi&egrave;res seront t&eacute;l&eacute;charg&eacute;es.');
  define('TEXT_RECLAMATION_IMAGE_RIGHTS', 'Je confirme que je d&eacute;tiens les droits sur les images t&eacute;l&eacute;charg&eacute;es et qu\'elles peuvent &ecirc;tre utilis&eacute;es pour traiter ma r&eacute;clamation.');
  define('TEXT_RECLAMATION_IMAGE_RIGHTS_ERROR', 'Veuillez confirmer les droits d\'image avant de t&eacute;l&eacute;charger des photos.');
  define('TEXT_RECLAMATION_UPLOADED_IMAGES', 'Images t&eacute;l&eacute;charg&eacute;es');

  // E-mail
  define('EMAIL_RECLAMATION_SUBJECT', 'R&eacute;clamation commande {$nr}');
  define('EMAIL_RECLAMATION_CONFIRM_SUBJECT', 'Confirmation de votre r&eacute;clamation &ndash; commande {$nr}');

  // Admin
  define('TEXT_RECLAMATION_ADMIN_STATUS_UPDATED', 'R&eacute;clamation #%s : Statut modifi&eacute; en &laquo; %s &raquo;.');
  define('TABLE_HEADING_RECLAMATION', 'R&eacute;clamations');

  // Statuts admin
  define('TEXT_RECLAMATION_STATUS_OPEN', 'Ouvert');
  define('TEXT_RECLAMATION_STATUS_IN_PROGRESS', 'En cours');
  define('TEXT_RECLAMATION_STATUS_RESOLVED', 'R&eacute;solu');
  define('TEXT_RECLAMATION_STATUS_REJECTED', 'Rejet&eacute;');
  define('TEXT_RECLAMATION_STATUS_CLOSED', 'Ferm&eacute;');

  // En-t&ecirc;tes de tableau
  define('HEADER_ARTICLE', 'Article');
  define('HEADER_QTY', 'Qt&eacute;');
  define('HEADER_MODEL', 'R&eacute;f.');

  // Boutons
  define('IMAGE_BUTTON_RECLAMATION', 'Soumettre la r&eacute;clamation');
