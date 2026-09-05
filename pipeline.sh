#!/usr/bin/env bash

set -euo pipefail

# Pipeline de deploy da plataforma B2B Velaro.
#
# Uso:
#   ./pipeline.sh                 -> deploy no diretório do próprio script
#   ./pipeline.sh homologacao     -> deploy em $DEPLOY_HML_DIR
#   ./pipeline.sh producao        -> deploy em $DEPLOY_PROD_DIR
#
# Detecta automaticamente a branch atual no diretório alvo e faz pull dela
# (nunca troca de branch sozinho).
#
# ATENÇÃO — o servidor de produção hospeda MAIS DE UM produto (agente-gordon,
# planeta, emergency). Os valores abaixo apontam para o diretório e para a fila
# da Velaro; trocá-los por engano faz deploy no produto errado e reinicia a fila
# de terceiros. O restart do PHP-FPM é compartilhado e afeta todos por segundos.
#
# ASSETS: o servidor de produção NÃO tem node/npm. O build é feito na máquina de
# quem faz o deploy e enviado antes, porque `public/build` é gitignored:
#
#     npm run build
#     rsync -az --delete public/build/ <host>:$DEPLOY_PROD_DIR/public/build/
#
# Se npm existir no alvo, o pipeline compila sozinho; se não existir, ele avisa
# e segue, contando que os assets já estejam lá.

ENVIRONMENT="${1:-}"

# Homologação da Velaro ainda não existe no servidor — o default fica declarado
# para quando existir, mas `./pipeline.sh homologacao` falha em `cd` até lá.
DEPLOY_HML_DIR="${DEPLOY_HML_DIR:-/data/homologacao/velaro}"
DEPLOY_PROD_DIR="${DEPLOY_PROD_DIR:-/data/velaro}"
SUPERVISOR_HML_PROGRAM="${SUPERVISOR_HML_PROGRAM:-velaro-hml-queue:*}"
SUPERVISOR_PROD_PROGRAM="${SUPERVISOR_PROD_PROGRAM:-velaro-queue:*}"

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

# `set -e` mataria o deploy aqui num servidor sem node, DEPOIS de ja ter rodado
# composer e migrate — o pior ponto para abortar. Por isso e condicional.
if command -v npm >/dev/null 2>&1; then
    log "Instalando dependências JS (npm ci)"
    npm ci

    log "Gerando assets de produção"
    npm run build
else
    log "npm não encontrado — assets NÃO foram compilados aqui"
    log "  Esperando build enviado por rsync (veja o cabeçalho deste arquivo)"
fi

# Manifest ausente ou desatualizado derruba toda tela que usa @vite: o Blade
# lanca ViteManifestNotFoundException e a pagina vira 500. Falha aqui, antes de
# trocar os caches, e melhor do que descobrir pelo usuario.
log "Conferindo o manifest do Vite"
if [[ ! -f public/build/manifest.json ]]; then
    echo "ERRO: public/build/manifest.json não existe. Envie o build antes do deploy." >&2
    exit 1
fi
for entrada in resources/css/velaro.css resources/css/app.css; do
    if ! grep -q "\"${entrada}\"" public/build/manifest.json; then
        echo "ERRO: manifest sem a entrada ${entrada} — build desatualizado." >&2
        exit 1
    fi
done
log "  manifest OK"

log "Limpando caches antigos (inclui classes Volt compiladas)"
php artisan optimize:clear

log "Reconstruindo caches Laravel (config/route/view/event)"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# reload do PHP-FPM NÃO limpa a opcache (SHM); só restart zera de fato.
# Reload do PHP-FPM NAO limpa a opcache (SHM); so restart zera de fato. Se este
# passo for pulado, o servidor continua servindo o BYTECODE ANTIGO com o codigo
# novo no disco — o pior tipo de deploy pela metade, porque nada aparenta erro.
#
# A versao anterior pre-testava a existencia do servico com `list-units | grep`
# e, quando o grep nao casava, seguia em silencio. Aconteceu em producao: o
# php8.3-fpm existia, o grep falhou, e o deploy terminou "com sucesso" sem ter
# limpado a opcache. Agora a tentativa e o proprio teste, e o passo FALHA ALTO
# quando nao consegue reiniciar nada.
log "Reiniciando PHP-FPM para zerar a opcache"
if command -v systemctl >/dev/null 2>&1; then
    FPM_REINICIADO=""
    for fpm in php8.4-fpm php8.3-fpm php8.2-fpm php-fpm; do
        if systemctl restart "$fpm" 2>/dev/null; then
            FPM_REINICIADO="$fpm"
            log "PHP-FPM (${fpm}) reiniciado"
            break
        fi
    done

    if [[ -z "$FPM_REINICIADO" ]]; then
        echo "ERRO: nenhum servico PHP-FPM pode ser reiniciado. A opcache NAO foi" >&2
        echo "      limpa e o servidor pode servir bytecode antigo. Reinicie a mao" >&2
        echo "      e confira: systemctl list-units --type=service | grep fpm" >&2
        exit 1
    fi
else
    echo "ERRO: systemctl nao encontrado — nao ha como zerar a opcache." >&2
    echo "      Reinicie o PHP-FPM manualmente antes de considerar o deploy feito." >&2
    exit 1
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
