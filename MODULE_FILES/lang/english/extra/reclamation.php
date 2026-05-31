<?php
/* -----------------------------------------------------------------------------------------
   MRH 2026: Reclamation Module – English Language File (Frontend)
   ---------------------------------------------------------------------------------------*/

  // Authentication
  define('ENTRY_RECLAMATION_ORDERS_ID_ERROR', 'Please provide your order number.');
  define('ENTRY_ORDERS_ID_TEXT', '*');

  // Headings
  define('TEXT_RECLAMATION_HEADING', 'Submit a complaint');
  define('TEXT_RECLAMATION_AUTH_TITLE', 'Verify your order');
  define('TEXT_RECLAMATION_INFO', 'Your order %s from %s');
  define('TEXT_RECLAMATION_INTRO', 'Please select the products you wish to complain about and describe the issue as precisely as possible. Photos help us process your case faster.');
  define('TEXT_RECLAMATION_SELECT_PRODUCTS', 'Select the affected products:');
  define('TEXT_RECLAMATION_NO_PRODUCTS', 'There are no products that can be complained about.');
  define('TEXT_RECLAMATION_ORDER_NOT_FOUND', 'Order not found. Please check your order number and email address.');
  define('TEXT_RECLAMATION_NOT_SHIPPED', 'This order has not yet been shipped. A complaint is only possible after receiving the goods.');
  define('TEXT_RECLAMATION_RATE_LIMIT', 'The maximum number of complaints has already been submitted for this order. Please contact our customer service.');

  // Success
  define('TEXT_RECLAMATION_SUCCESS', 'Thank you for your complaint.<br/>We have successfully received your request and will process it as soon as possible.<br/><br/>Our team is reviewing your details and will take care of the next steps. You will shortly receive a confirmation by email.');
  define('TEXT_RECLAMATION_SUCCESS_MSG', 'Complaint submitted successfully.');
  define('TEXT_RECLAMATION_SUCCESS_SUMMARY', 'Summary of the complained products:');

  // Plants
  define('TEXT_RECLAMATION_PLANT_EXCLUDED', 'Complaint excluded');
  define('TEXT_RECLAMATION_PLANT_EXCLUDED_INFO', 'Live plants (cuttings, seedlings) are excluded from complaints pursuant to &sect;&nbsp;18 para.&nbsp;1 no.&nbsp;4 FAGG, as they can perish quickly and return transport cannot guarantee the quality and health of the plant.');

  // Seeds
  define('TEXT_RECLAMATION_SEED_BADGE', 'Seeds');
  define('TEXT_RECLAMATION_SEED_KULANZ_TITLE', 'Goodwill complaint');
  define('TEXT_RECLAMATION_SEED_KULANZ_INFO', 'Seeds are natural products. A germination guarantee is not legally possible. We will review your complaint on a goodwill basis. Please answer the following questions honestly and upload photos.');
  define('TEXT_RECLAMATION_SEED_NOT_GERMINATED', 'Seeds did not germinate');
  define('TEXT_RECLAMATION_SEED_DAMAGED', 'Damaged during delivery');
  define('TEXT_RECLAMATION_SEED_WRONG_STRAIN', 'Wrong strain received');
  define('TEXT_RECLAMATION_SEED_IMAGE_REQUIRED', 'At least one photo is required for seed complaints.');

  // Seed questions
  define('TEXT_RECLAMATION_SEED_QUESTIONS_TITLE', 'Germination questions');
  define('TEXT_RECLAMATION_SEED_GERM_METHOD', 'Germination method');
  define('TEXT_RECLAMATION_SEED_GERM_PAPER', 'Moist paper towel');
  define('TEXT_RECLAMATION_SEED_GERM_SOIL', 'Directly in soil');
  define('TEXT_RECLAMATION_SEED_GERM_JIFFY', 'Jiffy pellet');
  define('TEXT_RECLAMATION_SEED_GERM_ROCKWOOL', 'Rockwool');
  define('TEXT_RECLAMATION_SEED_GERM_WATER', 'Glass of water');
  define('TEXT_RECLAMATION_SEED_TEMP', 'Temperature during germination');
  define('TEXT_RECLAMATION_SEED_TEMP_UNDER18', 'Below 18&deg;C');
  define('TEXT_RECLAMATION_SEED_TEMP_18_22', '18&ndash;22&deg;C');
  define('TEXT_RECLAMATION_SEED_TEMP_22_26', '22&ndash;26&deg;C (optimal)');
  define('TEXT_RECLAMATION_SEED_TEMP_OVER26', 'Above 26&deg;C');
  define('TEXT_RECLAMATION_SEED_TEMP_UNKNOWN', 'Unknown');
  define('TEXT_RECLAMATION_SEED_DAYS', 'How many days did you wait?');
  define('TEXT_RECLAMATION_SEED_DAYS_1_3', '1&ndash;3 days');
  define('TEXT_RECLAMATION_SEED_DAYS_4_7', '4&ndash;7 days');
  define('TEXT_RECLAMATION_SEED_DAYS_8_14', '8&ndash;14 days');
  define('TEXT_RECLAMATION_SEED_DAYS_OVER14', 'Over 14 days');
  define('TEXT_RECLAMATION_SEED_COUNT', 'Number of seeds that did not germinate');
  define('TEXT_RECLAMATION_SEED_STORED', 'I confirm that the seeds were stored in a cool, dry and dark place.');
  define('TEXT_RECLAMATION_SEED_EXPECTED', 'Expected strain');
  define('TEXT_RECLAMATION_SEED_EXPECTED_PH', 'e.g. Northern Lights Auto');
  define('TEXT_RECLAMATION_SEED_RECEIVED', 'Received strain');
  define('TEXT_RECLAMATION_SEED_RECEIVED_PH', 'e.g. White Widow Auto');

  // Accessories / Warranty
  define('TEXT_RECLAMATION_ACCESSORY_BADGE', 'Accessories');
  define('TEXT_RECLAMATION_WARRANTY_EXPIRED', 'Warranty expired');
  define('TEXT_RECLAMATION_WARRANTY_EXPIRED_INFO', 'The statutory warranty period of 2 years (&sect;&nbsp;922 ABGB) has expired for this product.');

  // Complaint reasons
  define('TEXT_RECLAMATION_REASON_LABEL', 'Reason for complaint');
  define('TEXT_RECLAMATION_REASON_SELECT', '-- Please select --');
  define('TEXT_RECLAMATION_REASON_TRANSPORT', 'Transport damage');
  define('TEXT_RECLAMATION_REASON_WRONG', 'Wrong product received');
  define('TEXT_RECLAMATION_REASON_INCOMPLETE', 'Delivery incomplete');
  define('TEXT_RECLAMATION_REASON_QUALITY', 'Quality defect');
  define('TEXT_RECLAMATION_REASON_PACKAGING', 'Packaging damaged');
  define('TEXT_RECLAMATION_REASON_OTHER', 'Other reason');

  // Description
  define('TEXT_RECLAMATION_DESCRIPTION_LABEL', 'Description of the problem');
  define('TEXT_RECLAMATION_DESCRIPTION_PH', 'Please describe the problem as precisely as possible...');
  define('TEXT_RECLAMATION_DESCRIPTION_HINT', 'Max. 2,000 characters');

  // Quantity
  define('TEXT_RECLAMATION_QTY_LABEL', 'Quantity complained');
  define('TEXT_RECLAMATION_QTY_MAX', 'Max.:');
  define('TEXT_RECLAMATION_QTY_ORDERED', 'Ordered');
  define('TEXT_RECLAMATION_PRICE', 'Price');
  define('TEXT_RECLAMATION_NO_PRODUCTS_SELECTED', 'Please select at least one product.');

  // Image upload
  define('TEXT_RECLAMATION_UPLOAD_HEADING', 'Upload photos');
  define('TEXT_RECLAMATION_UPLOAD_INFO', 'Upload photos of the damaged product, packaging or the problem. Photos significantly speed up the processing.');
  define('TEXT_RECLAMATION_UPLOAD_HINT', 'Max. 5 images, each max. 10 MB. Allowed formats: JPG, PNG, HEIC, WebP.');
  define('TEXT_RECLAMATION_UPLOAD_MAX_WARNING', 'Maximum 5 images allowed. Only the first 5 will be uploaded.');
  define('TEXT_RECLAMATION_IMAGE_RIGHTS', 'I confirm that I own the rights to the uploaded images and that they may be used to process my complaint.');
  define('TEXT_RECLAMATION_IMAGE_RIGHTS_ERROR', 'Please confirm the image rights before uploading photos.');
  define('TEXT_RECLAMATION_UPLOADED_IMAGES', 'Uploaded images');

  // Email
  define('EMAIL_RECLAMATION_SUBJECT', 'Complaint order {$nr}');
  define('EMAIL_RECLAMATION_CONFIRM_SUBJECT', 'Confirmation of your complaint &ndash; order {$nr}');

  // Admin
  define('TEXT_RECLAMATION_ADMIN_STATUS_UPDATED', 'Complaint #%s: Status changed to "%s".');
  define('TABLE_HEADING_RECLAMATION', 'Complaints');

  // Admin status
  define('TEXT_RECLAMATION_STATUS_OPEN', 'Open');
  define('TEXT_RECLAMATION_STATUS_IN_PROGRESS', 'In progress');
  define('TEXT_RECLAMATION_STATUS_RESOLVED', 'Resolved');
  define('TEXT_RECLAMATION_STATUS_REJECTED', 'Rejected');
  define('TEXT_RECLAMATION_STATUS_CLOSED', 'Closed');

  // Table headers
  define('HEADER_ARTICLE', 'Article');
  define('HEADER_QTY', 'Qty');
  define('HEADER_MODEL', 'Model');

  // Buttons
  define('IMAGE_BUTTON_RECLAMATION', 'Submit complaint');
