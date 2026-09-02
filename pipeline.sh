#!/usr/bin/env bash

set -euo pipefail

# Pipeline de deploy do CodaFácil Scaffold.
#
# Uso:
#   ./pipeline.sh                 -> deploy no diretório do próprio script
#   ./pipeline.sh homologacao     -> deploy em $DEPLOY_HML_DIR
#   ./pipeline.sh producao        -> deploy em $DEPLOY_PROD_DIR
#
# Detecta automaticamente a branch atual no diretório alvo e faz pull dela
# (nunca troca de branch sozinho). Ao clonar o scaffold, ajuste as variáveis
# de ambiente abaixo — ou exporte-as no servidor.

ENVIRONMENT="${1:-}"

DEPLOY_HML_DIR="${DEPLOY_HML_DIR:-/data/homologacao/codafacil}"
DEPLOY_PROD_DIR="${DEPLOY_PROD_DIR:-/data/codafacil}"
SUPERVISOR_HML_PROGRAM="${SUPERVISOR_HML_PROGRAM:-codafacil-hml-queue:*}"
SUPERVISOR_PROD_PROGRAM="${SUPERVISOR_PROD_PROGRAM:-codafacil-queue:*}"

case "$ENVIRONMENT" in
    homologacao|hml|staging)
        TARGET_DIR="$DEPLOY_HML_DIR"
        SUPERVISOR_PROGRAM="$SUPERVISOR_HML_PROGRAM"
        ;;
    producao|production|prod)
        TARGET_DIR="$DEPLOY_PROD_DIR"
        SUPERVISOR_PROGRAM="$SUPERVISOR_PROD_PROGRAM"
        ;;
    "")
        TARGET_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
        SUPERVISOR_PROGRAM=""
        ;;
    *)
        echo "Uso: $0 [homologacao|producao]" >&2
        exit 2
        ;;
esac

log() {
    printf '\n[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$1"
}

cd "$TARGET_DIR"

BRANCH="$(git rev-parse --abbrev-ref HEAD)"

log "Diretório: ${TARGET_DIR}"
log "Branch atual: ${BRANCH}"

log "Pull origin/${BRANCH} (fast-forward apenas)"
git pull --ff-only origin "$BRANCH"

log "Instalando dependências PHP (composer)"
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

log "Executando migrations em modo forçado"
php artisan migrate --force

log "Sincronizando catálogo ACL do backend"
php artisan acl:sync-backend --no-interaction

log "Instalando dependências JS (npm ci)"
npm ci

log "Gerando assets de produção"
npm run build

log "Limpando caches antigos (inclui classes Volt compiladas)"
php artisan optimize:clear

log "Reconstruindo caches Laravel (config/route/view/event)"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# reload do PHP-FPM NÃO limpa a opcache (SHM); só restart zera de fato.
log "Reiniciando PHP-FPM para zerar a opcache"
if command -v systemctl >/dev/null 2>&1; then
    for fpm in php8.4-fpm php8.3-fpm php-fpm; do
        if systemctl list-units --type=service --all 2>/dev/null | grep -q "${fpm}\.service"; then
            systemctl restart "$fpm" && log "PHP-FPM (${fpm}) reiniciado" && break
        fi
    done
else
    log "systemctl não encontrado — pulei restart do PHP-FPM (reinicie manualmente)"
fi

log "Sinalizando queue:restart"
php artisan queue:restart

if [[ -n "$SUPERVISOR_PROGRAM" ]]; then
    if command -v supervisorctl >/dev/null 2>&1; then
        log "Reiniciando ${SUPERVISOR_PROGRAM} via supervisorctl"
        supervisorctl restart "$SUPERVISOR_PROGRAM"
    else
        log "supervisorctl não encontrado — pulei restart de ${SUPERVISOR_PROGRAM}"
    fi
fi

log "Pipeline concluído com sucesso"
