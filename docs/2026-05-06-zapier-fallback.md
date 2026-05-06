# 2026-05-06 — Branchement Zapier sur le formulaire de contact

## Contexte

L'API BelEvent (Symfony) qui doit recevoir les leads du formulaire n'est pas encore branchée. En attendant, on a mis en place un **fallback Zapier** : Zap qui reçoit le payload, ajoute une ligne dans Google Sheets, crée un event Calendar, envoie un mail de confirmation au prospect et un mail de notif interne.

Le mail PHP via `mail()` continue de partir comme avant — Zapier est bonus, jamais bloquant.

## Architecture du fallback

```
Form submit
   │
   ▼
sendContactMail()    ─→  mail PHP (toujours envoyé)
   │
   ▼
sendToBelEvent()     ─→  succès ? STOP
   │ échec (incl. "API not configured")
   ▼
sendToZapier()       ─→  fire-and-forget vers webhook Zapier
   │
   ▼
redirect303(REDIRECT_OK)
```

Le déclenchement Zapier n'est conditionné qu'à `!$beleventResult['success']` — donc tant que BelEvent reste non configuré, Zapier prend le relais. Quand BelEvent sera branché et répondra 2xx, Zapier sera automatiquement court-circuité.

Kill switch global via `ZAPIER_ENABLED` dans `config.php` :

```php
define('ZAPIER_ENABLED', false);  // → fonction sort en early return, no-op
```

## Fichiers créés

| Fichier | Rôle |
|---|---|
| [`themes/laubardemont/static/php/helpers/zapier.php`](../themes/laubardemont/static/php/helpers/zapier.php) | Helper fire-and-forget mimant `belevent.php`. cURL avec SSL verify peer + host (=2), timeouts 5s/3s, **pas de log de la response HTTP** (peut leaker des données dans logs OVH partagés). |
| [`themes/laubardemont/static/php/config.example.php`](../themes/laubardemont/static/php/config.example.php) | Template versionné de `config.php`. À copier en `config.php` puis remplir avec les vrais secrets. |
| [`docs/2026-05-06-zapier-fallback.md`](2026-05-06-zapier-fallback.md) | Ce document. |

## Fichiers modifiés

| Fichier | Changement |
|---|---|
| `themes/laubardemont/static/php/contact-submit.php` | Ajout du `require_once 'helpers/zapier.php';` et du bloc fallback `if (!$beleventResult['success']) { sendToZapier(...); }` avant le `redirect303(REDIRECT_OK)` final. |
| `.gitignore` | Ajout de `themes/laubardemont/static/php/config.php` pour ne plus tracker le fichier qui contient les secrets. |

## Fichiers retirés du tracking

| Fichier | Action |
|---|---|
| `themes/laubardemont/static/php/config.php` | `git rm --cached` — toujours présent sur disque (local + serveurs), mais plus suivi par git. Désormais ignoré par `.gitignore`. |

## Payload envoyé à Zapier

```json
{
  "nom":            "{last_name}",
  "prenom":         "{first_name}",
  "email":          "{email}",
  "telephone":      "{phone | null}",
  "date_souhaitee": "{date | null}",
  "type_evenement": "{reason | null}",
  "nb_invites":     "{int | null}",
  "message":        "{message stripped}",
  "_secret":        "{ZAPIER_WEBHOOK_SECRET}"
}
```

Le `_secret` permet au Zap de filtrer les requêtes (le webhook URL Zapier seul est public, le secret authentifie).

## Sécurité

1. **Secrets jamais en dur dans le code** — webhook URL et secret vivent uniquement dans `config.php`, qui est gitignoré.
2. **`config.php` retiré du tracking git** — vérifié via `git log --all -S "<token>"` : aucun secret n'est dans l'historique git.
3. **`config.example.php`** sert de template versionné avec placeholders (`XXXXXX/YYYYYY`, `TOKEN_64_CHARS_HEX`).
4. **SSL verify peer + host** activés sur le cURL Zapier.
5. **Pas de log de la response HTTP Zapier** (logs OVH mutualisé sont partagés).
6. **Permissions config.php** — chmod 600 sur preprod.

## Git

| Action | Détail |
|---|---|
| Branche | `claude/stupefied-meninsky-711cf7` (créée depuis develop) |
| Commit | `f29446e` — `feat(php): add Zapier webhook fallback when BelEvent unconfigured` |
| Merge | `--no-ff` dans `develop` (commit de merge `6294962`) |
| Tag | `deploy/preprod/20260506-112305` |

## Déploiement preprod

Lancé via `./deploy-laubardemont.sh --env preprod`.

**Étape automatique du script :**
- Build Hugo `--minify --gc` : 395 fichiers, 79 Mo
- Backup serveur (vide pour ce déploiement — le dossier preprod n'existait pas avant)
- Upload site statique
- Upload des 14 fichiers PHP (rsync `--exclude='config.php'`)
- Vérification HTTP

**Fix manuel post-déploiement :**
Le script avait laissé un 500 sur `contact-submit.php` parce que `config.php` n'avait jamais été uploadé (rsync l'exclut par défaut, et aucun `config.php` ne préexistait sur preprod). Upload manuel via `scp` puis `chmod 600`. Le formulaire répond maintenant en `405` sur GET (= comportement attendu).

**État actuel preprod :**
- `ZAPIER_ENABLED = false` (étape 1 du plan de test progressif)
- Vrais credentials Zapier (URL + secret) déjà remplis dans `config.php`
- `chateau-laubardemont.com`, `www.chateau-laubardemont.com`, `preprod.chateau-laubardemont.com` dans `ALLOWED_HOSTS`

## Plan de test progressif

### Étape 1 — Form standard (Zapier OFF)

URL : https://preprod.chateau-laubardemont.com/contact/

- Remplir avec un email perso
- Vérifier la réception du mail PHP
- **Aucune action Zapier attendue** (pas de ligne Sheet, pas d'event Calendar, pas de mail de confirmation Zapier)

### Étape 2 — Activation Zapier

Sur preprod, éditer `config.php` :
```php
define('ZAPIER_ENABLED', true);
```

Re-soumettre le formulaire. Attendu :
- Mail PHP reçu (comme avant)
- **+ mail de confirmation Zapier** au prospect
- **+ ligne ajoutée** au Google Sheet
- **+ event créé** dans Google Calendar
- **+ mail de notif interne**

Si l'étape 2 échoue, repasser `ZAPIER_ENABLED = false` — le mail PHP continue de partir normalement, BelEvent reprendra la main dès que son API sera branchée.

## Points d'attention pour la suite

1. **Backup vide pour le premier déploiement preprod** — le `tar` a été créé sur un dossier qui n'existait pas. Le rollback (`./deploy-laubardemont.sh --env preprod --rollback`) ne fonctionnera pas pour ce déploiement précis. Le prochain déploiement aura un vrai backup.

2. **Logs PHP introuvables** — `~/logs/` n'existe pas tel quel sur l'hébergement OVH. À localiser via le panel OVH pour debug futur.

3. **`config.php` accessible HTTP en page blanche** sur preprod (HTTP 200, body vide, pas de leak du source). Pour durcir, ajouter dans `.htaccess` :
   ```apache
   <Files "config.php">
     Require all denied
   </Files>
   ```

4. **Bug d'incohérence existant non corrigé** — dans `contact-submit.php`, le `message` envoyé à BelEvent et Zapier utilise `strip_tags($_POST['message'])` au lieu de `cleanMessage()` du `$data` sanitizé. Volontairement laissé ISO comportement BelEvent ; à traiter dans une autre tâche.

## Déploiement prod (pas encore fait)

Quand l'étape 2 sera validée sur preprod :
1. Vérifier que `chateau-laubardemont.com` (prod) a bien un `config.php` à jour avec les constantes Zapier
2. Lancer `./deploy-laubardemont.sh` (sans `--env`, prod par défaut depuis `main`)
3. Tester en remplissant le formulaire prod avec un email perso
