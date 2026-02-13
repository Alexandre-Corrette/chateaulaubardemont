#!/bin/bash
# =============================================================================
# 🏰 Château de Laubardemont — Script de déploiement (OVH mutualisé)
# =============================================================================
# Usage :
#   ./deploy.sh              → Build Hugo + déploie tout (site + PHP)
#   ./deploy.sh --php-only   → Déploie uniquement les fichiers PHP (formulaire)
#   ./deploy.sh --dry-run    → Simule sans rien toucher
#   ./deploy.sh --rollback   → Restaure le dernier backup
# =============================================================================

set -euo pipefail

# ── Configuration ────────────────────────────────────────────────────────────
SSH_HOST="ftp.cluster128.hosting.ovh.net"
SSH_USER="${DEPLOY_SSH_USER:-}"              # Via env ou .env.deploy
REMOTE_DIR="~/www"                   # ⚠️ Chemin racine du site sur OVH
REMOTE_PUBLIC="${REMOTE_DIR}"        # Hugo output va directement dans www/
REMOTE_PHP="${REMOTE_DIR}/php"       # Fichiers PHP du formulaire
HUGO_PUBLIC_DIR="public"             # Dossier output Hugo local
GIT_BRANCH="main"                   # Branche de production

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# ── Flags ────────────────────────────────────────────────────────────────────
PHP_ONLY=false
DRY_RUN=false
SKIP_BUILD=false
ROLLBACK=false

for arg in "$@"; do
    case $arg in
        --php-only)   PHP_ONLY=true ;;
        --dry-run)    DRY_RUN=true ;;
        --skip-build) SKIP_BUILD=true ;;
        --rollback)   ROLLBACK=true ;;
        -h|--help)
            echo "Usage: ./deploy.sh [--php-only] [--skip-build] [--dry-run] [--rollback]"
            echo ""
            echo "Options:"
            echo "  --php-only    Déploie uniquement les fichiers PHP (formulaire + helpers)"
            echo "  --skip-build  Skip le build Hugo (déploie le dossier public/ existant)"
            echo "  --dry-run     Simule le déploiement sans rien exécuter"
            echo "  --rollback    Restaure le dernier backup distant"
            exit 0
            ;;
        *) echo -e "${RED}❌ Argument inconnu : $arg${NC}"; exit 1 ;;
    esac
done

# Charger .env.deploy si présent
if [ -f ".env.deploy" ]; then
    # shellcheck source=/dev/null
    source .env.deploy
    SSH_USER="${DEPLOY_SSH_USER:-$SSH_USER}"
fi

# ── Fonctions ────────────────────────────────────────────────────────────────
log_step()  { echo -e "\n${BLUE}━━━ $1 ━━━${NC}"; }
log_ok()    { echo -e "${GREEN}  ✅ $1${NC}"; }
log_warn()  { echo -e "${YELLOW}  ⚠️  $1${NC}"; }
log_err()   { echo -e "${RED}  ❌ $1${NC}"; }
log_info()  { echo -e "${CYAN}  ℹ️  $1${NC}"; }

run() {
    if [ "$DRY_RUN" = true ]; then
        echo -e "${YELLOW}  [DRY-RUN] $*${NC}"
    else
        "$@"
    fi
}

ssh_exec() {
    if [ "$DRY_RUN" = true ]; then
        echo -e "${YELLOW}  [DRY-RUN] ssh $SSH_USER@$SSH_HOST \"$1\"${NC}"
    else
        ssh "$SSH_USER@$SSH_HOST" "$1"
    fi
}

rsync_upload() {
    local src="$1"
    local dst="$2"
    local flags="${3:---archive --compress --delete}"

    if [ "$DRY_RUN" = true ]; then
        echo -e "${YELLOW}  [DRY-RUN] rsync $flags $src $SSH_USER@$SSH_HOST:$dst${NC}"
    else
        rsync $flags \
            --exclude='.DS_Store' \
            --exclude='Thumbs.db' \
            --exclude='.git' \
            --exclude='node_modules' \
            "$src" "$SSH_USER@$SSH_HOST:$dst"
    fi
}

# ── Vérifications ────────────────────────────────────────────────────────────
log_step "Vérifications"

if [ -z "$SSH_USER" ]; then
    log_err "SSH_USER non configuré ! Définis DEPLOY_SSH_USER dans .env.deploy ou en variable d'environnement."
    exit 1
fi

# ── Rollback ────────────────────────────────────────────────────────────────
if [ "$ROLLBACK" = true ]; then
    log_step "Rollback vers le dernier backup"
    LATEST_BACKUP=$(ssh "$SSH_USER@$SSH_HOST" "ls -t ~/backups/backup_*.tar.gz 2>/dev/null | head -1")
    if [ -z "$LATEST_BACKUP" ]; then
        log_err "Aucun backup trouvé sur le serveur"
        exit 1
    fi
    log_info "Backup trouvé : ${LATEST_BACKUP}"
    read -p "  Restaurer ce backup ? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 0
    fi
    ssh_exec "cd ${REMOTE_DIR} && tar xzf ${LATEST_BACKUP}"
    log_ok "Rollback effectué"

    # Vérification post-rollback
    SITE_URL="https://chateau-laubardemont.com"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$SITE_URL" 2>/dev/null || echo "000")
    if [ "$HTTP_CODE" = "200" ]; then
        log_ok "Site accessible après rollback (HTTP ${HTTP_CODE})"
    else
        log_warn "Site retourne HTTP ${HTTP_CODE} après rollback"
    fi
    exit 0
fi

# Vérifier qu'on est à la racine du projet Hugo
if [ ! -f "hugo.toml" ] && [ ! -f "config.toml" ] && [ ! -f "config.yaml" ]; then
    log_err "Ce script doit être lancé depuis la racine du projet Hugo."
    exit 1
fi

# Vérifier la branche
CURRENT_BRANCH=$(git branch --show-current 2>/dev/null || echo "unknown")
if [ "$CURRENT_BRANCH" != "$GIT_BRANCH" ] && [ "$PHP_ONLY" = false ]; then
    log_warn "Tu es sur la branche '${CURRENT_BRANCH}', pas '${GIT_BRANCH}'"
    read -p "  Continuer quand même ? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 0
    fi
fi

# Vérifier les changements non commités
if ! git diff --quiet HEAD 2>/dev/null; then
    log_warn "Tu as des changements non commités"
    git status --short
    read -p "  Déployer quand même ? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 0
    fi
fi

log_ok "Vérifications passées (branche: ${CURRENT_BRANCH})"

# ── Mode PHP-only ────────────────────────────────────────────────────────────
if [ "$PHP_ONLY" = true ]; then
    log_step "Déploiement PHP uniquement"

    if [ ! -d "php" ]; then
        log_err "Dossier php/ introuvable à la racine du projet"
        exit 1
    fi

    # Compter les fichiers PHP
    PHP_COUNT=$(find php/ -name "*.php" | wc -l | tr -d ' ')
    log_info "Upload de ${PHP_COUNT} fichiers PHP..."

    # Upload les fichiers PHP (sans --delete pour ne pas supprimer config.php sur le serveur)
    rsync_upload "php/" "${REMOTE_PHP}/" "--archive --compress --exclude='config.php'"

    log_ok "Fichiers PHP déployés"
    log_warn "config.php n'est PAS écrasé (contient la clé API BelEvent)"
    log_info "Si c'est le premier déploiement, copie config.php manuellement :"
    log_info "  scp php/config.php ${SSH_USER}@${SSH_HOST}:${REMOTE_PHP}/config.php"

    echo ""
    echo -e "${GREEN}🏰 Déploiement PHP terminé !${NC}"
    exit 0
fi

# ── Build Hugo ───────────────────────────────────────────────────────────────
if [ "$SKIP_BUILD" = false ]; then
    log_step "Build Hugo"

    # Vérifier que Hugo est installé
    if ! command -v hugo &> /dev/null; then
        log_err "Hugo n'est pas installé. Installe-le : brew install hugo"
        exit 1
    fi

    HUGO_VERSION=$(hugo version 2>/dev/null | head -c 50)
    log_info "Hugo: ${HUGO_VERSION}"

    # Nettoyer l'ancien build
    if [ -d "$HUGO_PUBLIC_DIR" ]; then
        run rm -rf "$HUGO_PUBLIC_DIR"
        log_info "Ancien build nettoyé"
    fi

    # Générer le build ID
    mkdir -p static
    echo "$(date +%s)" > static/build.txt
    log_info "Build ID généré : $(cat static/build.txt)"

    # Build
    if [ "$DRY_RUN" = false ]; then
        hugo --minify --gc 2>&1 | tail -5
    else
        echo -e "${YELLOW}  [DRY-RUN] hugo --minify --gc${NC}"
    fi

    # Vérifier le résultat
    if [ -d "$HUGO_PUBLIC_DIR" ] || [ "$DRY_RUN" = true ]; then
        if [ "$DRY_RUN" = false ]; then
            FILE_COUNT=$(find "$HUGO_PUBLIC_DIR" -type f | wc -l | tr -d ' ')
            TOTAL_SIZE=$(du -sh "$HUGO_PUBLIC_DIR" | cut -f1)
            log_ok "Build terminé : ${FILE_COUNT} fichiers, ${TOTAL_SIZE}"
        else
            log_ok "Build simulé"
        fi
    else
        log_err "Le build Hugo a échoué (dossier public/ non créé)"
        exit 1
    fi
else
    log_step "Build Hugo (skip)"
    if [ ! -d "$HUGO_PUBLIC_DIR" ]; then
        log_err "Dossier public/ introuvable. Lance le build d'abord ou retire --skip-build"
        exit 1
    fi
    log_ok "Utilisation du build existant"
fi

# ── Backup distant ──────────────────────────────────────────────────────────
log_step "Backup du site actuel"

BACKUP_NAME="backup_$(date +%Y%m%d_%H%M%S)"
ssh_exec "cd ${REMOTE_DIR} && mkdir -p ~/backups && tar czf ~/backups/${BACKUP_NAME}.tar.gz --exclude='*.tar.gz' . 2>/dev/null || true"
log_ok "Backup créé : ~/backups/${BACKUP_NAME}.tar.gz"
ssh_exec "ls -t ~/backups/backup_*.tar.gz 2>/dev/null | tail -n +6 | xargs rm -f 2>/dev/null || true"
log_info "Rotation des backups (max 5 conservés)"

# ── Upload site statique ────────────────────────────────────────────────────
log_step "Upload du site"

# rsync le contenu de public/ vers le remote
# --delete supprime les fichiers qui n'existent plus localement
# --exclude protège les fichiers serveur (PHP, config, .htaccess custom)
rsync_upload "${HUGO_PUBLIC_DIR}/" "${REMOTE_PUBLIC}/" \
    "--archive --compress --delete --stats --exclude='php/' --exclude='.htaccess' --exclude='config.php' --exclude='.env'"
log_ok "Site statique uploadé"

# ── Upload fichiers PHP ─────────────────────────────────────────────────────
log_step "Upload des fichiers PHP"

if [ -d "php" ]; then
    PHP_COUNT=$(find php/ -name "*.php" | wc -l | tr -d ' ')
    log_info "${PHP_COUNT} fichiers PHP à synchroniser"

    # Upload SANS écraser config.php (contient les secrets)
    rsync_upload "php/" "${REMOTE_PHP}/" "--archive --compress --exclude='config.php'"

    log_ok "Fichiers PHP déployés"
    log_warn "config.php non écrasé (secrets serveur)"
else
    log_warn "Pas de dossier php/ — formulaire non déployé"
fi

# ── Vérification ─────────────────────────────────────────────────────────────
log_step "Vérification"

if [ "$DRY_RUN" = false ]; then
    # Vérifier que le site répond
    SITE_URL="https://chateau-laubardemont.com"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$SITE_URL" 2>/dev/null || echo "000")

    if [ "$HTTP_CODE" = "200" ]; then
        log_ok "Site accessible : ${SITE_URL} (HTTP ${HTTP_CODE})"
    elif [ "$HTTP_CODE" = "000" ]; then
        log_warn "Impossible de joindre ${SITE_URL} (timeout ou DNS)"
    else
        log_warn "Site retourne HTTP ${HTTP_CODE}"
    fi

    # Vérifier le build ID
    if [ -f "${HUGO_PUBLIC_DIR}/build.txt" ]; then
        LOCAL_BUILD=$(cat "${HUGO_PUBLIC_DIR}/build.txt")
        REMOTE_BUILD=$(curl -s --max-time 5 "${SITE_URL}/build.txt" 2>/dev/null || echo "")
        if [ "$LOCAL_BUILD" = "$REMOTE_BUILD" ]; then
            log_ok "Build vérifié (ID: ${LOCAL_BUILD})"
        else
            log_warn "Build ID mismatch — local: ${LOCAL_BUILD}, remote: ${REMOTE_BUILD}"
        fi
    fi

    # Vérifier le formulaire PHP
    FORM_URL="${SITE_URL}/php/contact-submit.php"
    FORM_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "$FORM_URL" 2>/dev/null || echo "000")

    if [ "$FORM_CODE" = "405" ]; then
        log_ok "Formulaire PHP accessible (405 = GET bloqué, normal)"
    elif [ "$FORM_CODE" = "000" ]; then
        log_warn "Formulaire PHP non joignable"
    else
        log_info "Formulaire PHP retourne HTTP ${FORM_CODE}"
    fi
else
    log_ok "Vérifications simulées"
fi

# ── Git tag ──────────────────────────────────────────────────────────────────
log_step "Tag de déploiement"

DEPLOY_TAG="deploy/$(date +%Y%m%d-%H%M%S)"
if [ "$DRY_RUN" = false ]; then
    git tag "$DEPLOY_TAG" 2>/dev/null && git push origin "$DEPLOY_TAG" 2>/dev/null || true
    log_ok "Tag créé : ${DEPLOY_TAG}"
else
    log_info "Tag simulé : ${DEPLOY_TAG}"
fi

# ── Résumé ───────────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}  🏰 Déploiement Laubardemont terminé !${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "  Site  : https://chateau-laubardemont.com"
echo -e "  Backup: ~/backups/${BACKUP_NAME}.tar.gz"
echo -e "  Tag   : ${DEPLOY_TAG}"
echo ""

if [ "$DRY_RUN" = true ]; then
    echo -e "${YELLOW}  ⚠️  Mode dry-run — rien n'a été modifié${NC}"
    echo ""
fi
