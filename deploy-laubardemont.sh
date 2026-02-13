#!/bin/bash
# =============================================================================
# 🏰 Château de Laubardemont — Script de déploiement (OVH mutualisé)
# =============================================================================
# Usage :
#   ./deploy.sh              → Build Hugo + déploie tout en prod
#   ./deploy.sh --env preprod → Déploie sur l'environnement preprod
#   ./deploy.sh --php-only   → Déploie uniquement les fichiers PHP (formulaire)
#   ./deploy.sh --dry-run    → Simule sans rien toucher
#   ./deploy.sh --rollback   → Restaure le dernier backup
# =============================================================================

set -euo pipefail

# ── Configuration ────────────────────────────────────────────────────────────
HUGO_PUBLIC_DIR="public"             # Dossier output Hugo local

# Valeurs par défaut (prod), surchargées par --env
DEPLOY_ENV="prod"
REMOTE_DIR="~/www"
SITE_URL="https://chateau-laubardemont.com"
GIT_BRANCH="main"

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

while [[ $# -gt 0 ]]; do
    case $1 in
        --php-only)   PHP_ONLY=true ;;
        --dry-run)    DRY_RUN=true ;;
        --skip-build) SKIP_BUILD=true ;;
        --rollback)   ROLLBACK=true ;;
        --env)
            shift
            if [[ $# -eq 0 ]]; then
                echo -e "${RED}❌ --env nécessite une valeur (prod ou preprod)${NC}"; exit 1
            fi
            DEPLOY_ENV="$1"
            ;;
        -h|--help)
            echo "Usage: ./deploy.sh [--env prod|preprod] [--php-only] [--skip-build] [--dry-run] [--rollback]"
            echo ""
            echo "Options:"
            echo "  --env ENV     Environnement cible : prod (défaut) ou preprod"
            echo "  --php-only    Déploie uniquement les fichiers PHP (formulaire + helpers)"
            echo "  --skip-build  Skip le build Hugo (déploie le dossier public/ existant)"
            echo "  --dry-run     Simule le déploiement sans rien exécuter"
            echo "  --rollback    Restaure le dernier backup distant"
            exit 0
            ;;
        *) echo -e "${RED}❌ Argument inconnu : $1${NC}"; exit 1 ;;
    esac
    shift
done

# ── Charger .env.deploy ────────────────────────────────────────────────────
if [ -f ".env.deploy" ]; then
    # Vérifier les permissions (doit être 600 ou 640)
    PERMS=$(stat -f "%OLp" .env.deploy 2>/dev/null || stat -c "%a" .env.deploy 2>/dev/null || echo "unknown")
    if [ "$PERMS" != "600" ] && [ "$PERMS" != "640" ] && [ "$PERMS" != "unknown" ]; then
        echo -e "${YELLOW}  ⚠️  .env.deploy a les permissions ${PERMS} — recommandé : chmod 600 .env.deploy${NC}"
    fi
    # shellcheck source=/dev/null
    source .env.deploy
fi

SSH_HOST="${DEPLOY_SSH_HOST:-ssh.cluster131.hosting.ovh.net}"
SSH_USER="${DEPLOY_SSH_USER:-}"

# ── Configuration sshpass (mutualisé OVH) ─────────────────────────────────
SSH_PASS="${DEPLOY_SSH_PASS:-}"
SSH_PASS_CMD=""

if [ -n "$SSH_PASS" ]; then
    if ! command -v sshpass &> /dev/null; then
        echo -e "${RED}  ❌ sshpass requis mais non installé. Installe-le : brew install esolitos/ipa/sshpass (macOS) ou apt install sshpass (Linux)${NC}"
        exit 1
    fi
    SSH_PASS_CMD="sshpass -p ${SSH_PASS}"
fi

# ── Configuration environnement ────────────────────────────────────────────
case $DEPLOY_ENV in
    prod)
        REMOTE_DIR="${DEPLOY_PROD_REMOTE_DIR:-}"
        SITE_URL="${DEPLOY_PROD_SITE_URL:-}"
        GIT_BRANCH="${DEPLOY_PROD_GIT_BRANCH:-}"
        ;;
    preprod)
        REMOTE_DIR="${DEPLOY_PREPROD_REMOTE_DIR:-}"
        SITE_URL="${DEPLOY_PREPROD_SITE_URL:-}"
        GIT_BRANCH="${DEPLOY_PREPROD_GIT_BRANCH:-}"
        ;;
    *)
        echo -e "${RED}❌ Environnement inconnu : $DEPLOY_ENV (prod ou preprod)${NC}"; exit 1
        ;;
esac

if [ -z "$REMOTE_DIR" ] || [ -z "$SITE_URL" ] || [ -z "$GIT_BRANCH" ]; then
    echo -e "${RED}  ❌ Configuration ${DEPLOY_ENV} incomplète dans .env.deploy${NC}"
    exit 1
fi
REMOTE_PUBLIC="${REMOTE_DIR}"
REMOTE_PHP="${REMOTE_DIR}/php"

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
        $SSH_PASS_CMD ssh "$SSH_USER@$SSH_HOST" "$1"
    fi
}

rsync_upload() {
    local src="$1"
    local dst="$2"
    local flags="${3:---archive --compress --delete}"

    if [ "$DRY_RUN" = true ]; then
        echo -e "${YELLOW}  [DRY-RUN] rsync $flags $src $SSH_USER@$SSH_HOST:$dst${NC}"
    else
        if [ -n "$SSH_PASS_CMD" ]; then
            rsync $flags \
                -e "$SSH_PASS_CMD ssh" \
                --exclude='.DS_Store' \
                --exclude='Thumbs.db' \
                --exclude='.git' \
                --exclude='node_modules' \
                "$src" "$SSH_USER@$SSH_HOST:$dst"
        else
            rsync $flags \
                --exclude='.DS_Store' \
                --exclude='Thumbs.db' \
                --exclude='.git' \
                --exclude='node_modules' \
                "$src" "$SSH_USER@$SSH_HOST:$dst"
        fi
    fi
}

# ── Vérifications ────────────────────────────────────────────────────────────
log_step "Vérifications"

if [ -n "$SSH_PASS_CMD" ]; then
    log_info "Mode sshpass activé (mutualisé OVH)"
else
    log_info "Mode interactif (mot de passe demandé à chaque connexion)"
fi

if [ -z "$SSH_USER" ]; then
    log_err "SSH_USER non configuré ! Définis DEPLOY_SSH_USER dans .env.deploy ou en variable d'environnement."
    exit 1
fi

# ── Rollback ────────────────────────────────────────────────────────────────
if [ "$ROLLBACK" = true ]; then
    log_step "Rollback vers le dernier backup"
    BACKUP_PREFIX="backup_${DEPLOY_ENV}"
    LATEST_BACKUP=$($SSH_PASS_CMD ssh "$SSH_USER@$SSH_HOST" "ls -t ~/backups/${BACKUP_PREFIX}_*.tar.gz 2>/dev/null | head -1")
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

log_ok "Vérifications passées (env: ${DEPLOY_ENV}, branche: ${CURRENT_BRANCH})"

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

    # Générer le build ID (hash du commit + date)
    mkdir -p static
    BUILD_HASH="$(git rev-parse --short HEAD 2>/dev/null || echo 'unknown')-$(date +%Y%m%d%H%M%S)"
    echo "$BUILD_HASH" > static/build.txt
    log_info "Build ID généré : ${BUILD_HASH}"

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

BACKUP_NAME="backup_${DEPLOY_ENV}_$(date +%Y%m%d_%H%M%S)"
ssh_exec "cd ${REMOTE_DIR} && mkdir -p ~/backups && tar czf ~/backups/${BACKUP_NAME}.tar.gz --exclude='*.tar.gz' . 2>/dev/null || true"
log_ok "Backup créé : ~/backups/${BACKUP_NAME}.tar.gz"
ssh_exec "ls -t ~/backups/backup_${DEPLOY_ENV}_*.tar.gz 2>/dev/null | tail -n +6 | xargs rm -f 2>/dev/null || true"
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

DEPLOY_TAG="deploy/${DEPLOY_ENV}/$(date +%Y%m%d-%H%M%S)"
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
echo -e "  Env   : ${DEPLOY_ENV}"
echo -e "  Site  : ${SITE_URL}"
echo -e "  Backup: ~/backups/${BACKUP_NAME}.tar.gz"
echo -e "  Tag   : ${DEPLOY_TAG}"
echo ""

if [ "$DRY_RUN" = true ]; then
    echo -e "${YELLOW}  ⚠️  Mode dry-run — rien n'a été modifié${NC}"
    echo ""
fi
