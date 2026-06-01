# Reklamationsmodul – Offene Aufgaben

## Bug-Fixes (Prio 1)

- [ ] **Mail-Template Dateiname falsch:** Controller referenziert `reclamation_confirm.html` statt `reclamation_confirm_mail.html` → Zeile im Controller finden und korrigieren (oder Datei umbenennen)
- [ ] **Backend-Dashboard Darstellung:** Layout-Probleme – farbige Status-Balken zu breit/hoch, kein Bootstrap-Admin-Styling, wirkt unprofessionell. Muss an das modified-Admin-Design angepasst werden (kleinere Kacheln, Tabelle mit korrektem Admin-CSS)

## Verbesserungen (Prio 2)

- [ ] Content-Text (HTML im Content-Manager) auf zweispaltig max. umstellen, Icons mit `fa-solid text-light fs-4`
- [ ] Temperatur-Dropdown: nur "(optimal)" Text entfernt, Feld bleibt – prüfen ob auf Server korrekt deployed
- [ ] FR/ES TXT-Mail-Templates auf Server deployen (bisher nur DE + EN deployed)

## Hinweise

- OPcache leeren: `curl -s -u "Alex:19649541BNZUUHJHBBHZi" "https://mr-hanf.de/opcache_reset.php?token=MrHanf2024Reset"`
- Smarty-Cache leeren: `rm -rf templates_c/*`
- Immer BEIDE Caches leeren nach Deployment!
