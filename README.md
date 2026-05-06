# chateaulaubardemont

Site Hugo + formulaire PHP du Château de Laubardemont (lieu de réception, Bordeaux).

## Options du thème `laubardemont`

Le formulaire de contact (`themes/laubardemont/static/php/`) supporte deux intégrations externes optionnelles, configurées via `config.php` (gitignoré — voir [`config.example.php`](themes/laubardemont/static/php/config.example.php) pour le template).

### Connexion API BelEvent

Lead capture vers l'API BelEvent. Activée dès que `BELEVENT_API_URL` et `BELEVENT_API_KEY` sont remplies dans `config.php`. Tant que l'une des deux est vide, l'appel est silencieusement ignoré (pas d'erreur côté formulaire).

- Helper : [`helpers/belevent.php`](themes/laubardemont/static/php/helpers/belevent.php) (fire-and-forget)
- Constantes : `BELEVENT_API_URL`, `BELEVENT_API_KEY`, `BELEVENT_VENUE_SLUG`

### Connexion Zapier (fallback)

Webhook Zapier déclenché **uniquement si BelEvent échoue** (incluant le cas "non configuré"). Sert à pousser le lead vers Google Sheets / Calendar / mails de notif via un Zap pendant que BelEvent n'est pas branché.

- Helper : [`helpers/zapier.php`](themes/laubardemont/static/php/helpers/zapier.php) (fire-and-forget)
- Kill switch global : `ZAPIER_ENABLED` (`true`/`false`)
- Constantes : `ZAPIER_WEBHOOK_URL`, `ZAPIER_WEBHOOK_SECRET`
- Détails d'architecture, payload, plan de test : [`docs/2026-05-06-zapier-fallback.md`](docs/2026-05-06-zapier-fallback.md)

---

Documentation projet (commandes Hugo, structure, déploiement, tests PHP) : [`CLAUDE.md`](CLAUDE.md).
