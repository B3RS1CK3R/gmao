Résumé des corrections appliquées automatiquement
=============================================

Contexte
--------
Lors de tests locaux, plusieurs endpoints et scripts chargeaient directement `config/database.php` ou `includes/lang.php`, ce qui causait des erreurs de fonction manquante (`t()`) et des warnings "headers already sent" dans certains cas. Pour standardiser et éviter ces problèmes, j'ai unifié les inclusions vers le point d'entrée central `includes/functions.php` qui initialise la DB, la traduction, et la session.

Changements effectués
---------------------
- Remplacement de `require_once __DIR__ . '/../config/database.php'` par `require_once __DIR__ . '/../includes/functions.php'` dans plusieurs scripts.
- Ajout de `ob_start();` dans les scripts modifiés pour réduire les risques de "headers already sent".

Fichiers modifiés (liste non exhaustive)
----------------------------------------
- api/get_alerts.php (déjà modifié précédemment)
- api/get_equipment.php
- api/get_ip.php
- api/network_info_modal.php
- api/check_attachments_columns.php
- api/update_intervention_date.php
- api/delete_attachment.php (vérifié)
- api/upload_attachment.php (vérifié)
- cron/check_alerts.php
- export/*.php (export_interventions_excel.php, export_equipment_excel.php, export_intervention_pdf.php, ical_export.php, export_stock_excel.php)
- import/import_equipment.php
- pages/admin_migrations.php
- pages/login.php
- pages/mobile_interventions.php
- tools/* (add_fk_technician.php, run_migration.php)

Tests réalisés
--------------
- Exécution CLI de plusieurs endpoints / fragments :
  - `php -f api/get_alerts.php` → renvoie JSON (unauthenticated)
  - `php -f api/get_ip.php` → JSON rendu (ip: null en CLI)
  - `php -f api/check_attachments_columns.php` → indique `external_path varchar(1024)`
  - `php -f api/network_info_modal.php` → rendu HTML fragment sans erreur de parsing
  - `php -l` sur fichiers modifiés → pas d'erreurs de syntaxe

Remarques
--------
- Ces changements améliorent la cohérence et réduisent les erreurs runtime. Ils peuvent modifier légèrement le comportement de scripts CLI qui s'attendaient à ne charger que la DB — `includes/functions.php` démarre la session et charge les traductions, ce qui est généralement souhaitable.
- Si tu veux que je publie un commentaire directement sur la PR GitHub, fournis un token ou autorise l'outil d'intégration; sinon copie-colle le message suivant en commentaire PR.

Message de commentaire suggéré pour la PR
---------------------------------------
Bonjour,

J'ai appliqué automatiquement une standardisation des inclusions : les scripts utilisent désormais `includes/functions.php` (initialise DB, traduction, session) et ajoutent `ob_start()` pour prévenir les warnings "headers already sent".

Fichiers modifiés et tests CLI exécutés sont listés ci-dessus. Si tu veux que je poste ce commentaire sur la PR, je peux le faire si tu fournis l'accès GitHub approprié.

— Corrections appliquées par l'agent
