# XSP Licensing — Arquitetura Anti-Pirataria

Sistema completo de licenciamento SaaS para o **Painel Office Xtream Server Pro**.

## Tudo em Docker

A única dependência no host é o **Docker**. Tudo o mais — API Go, Postgres,
Redis, registry, admin, Caddy (TLS), até o pipeline de build da release —
roda em containers.

```
licensing/
├── docker-compose.yml      Stack completo (caddy + api + admin + db + redis + registry + builder)
├── Caddyfile               Proxy reverso + TLS automático
├── Makefile                Atalhos (make up, make release, make logs)
├── bootstrap-secrets.sh    Gera todos os segredos via container Go
├── .env.example
│
├── install-server.sh       1-comando para subir tudo numa VPS limpa
├── install-painel.sh       1-comando para o cliente instalar o painel
├── INSTALL.sh              Router (pergunta server ou painel)
│
├── api-license/            API central (Go) — Dockerfile próprio
├── xsp-loader/             Extensão C que decifra PHP em memória
├── painel-image/           Pipeline: adapta → ofusca → cifra → push registry
├── admin-dashboard/        Painel PHP para gerenciar KEYs
├── builder/                Imagem com Python+Docker CLI para rodar releases
├── landing/                Página pública /install.sh com domínio auto-detect
└── docs/                   DEPLOY.md, OPERATIONS.md, SECURITY.md
```

## Modelo de proteção (em uma frase)

> Os `.php` do painel viajam **cifrados em disco (AES-256-GCM)** dentro de um
> container Docker. A chave para decifrar **não fica na imagem** — é
> entregue pela API central a cada boot, **cifrada com o HWID da máquina do
> cliente**. Sem licença ativa, sem chave. Sem chave, sem decifrar. Sem
> decifrar, painel não roda.

## Ordem de leitura recomendada

1. [`docs/DEPLOY.md`](docs/DEPLOY.md) — Como subir a infra do zero numa VPS.
2. [`docs/OPERATIONS.md`](docs/OPERATIONS.md) — Runbook diário (criar KEY, revogar, etc.).
3. [`docs/SECURITY.md`](docs/SECURITY.md) — Camadas de proteção e limitações honestas.
4. `api-license/README.md` — API e endpoints.
5. `installer-go/README.md` — Instalador do cliente.
6. `xsp-loader/README.md` — Extensão C.
7. `painel-image/README.md` — Pipeline de release.
8. `admin-dashboard/README.md` — Painel admin.

## Quick start — 1 comando

### Você (uma vez, na sua VPS central)

```bash
# Após apontar os 4 subdomínios para a VPS:
cd licensing/
sudo bash install-server.sh
```

Pronto. O script:
- Instala Docker (única dependência no host)
- Coleta seus 4 domínios + e-mail
- Gera todos os segredos via container Go
- Sobe o stack completo via `docker compose up`:
  - Caddy (TLS automático Let's Encrypt)
  - api-license (Go)
  - admin-dashboard (PHP)
  - Postgres + Redis + Registry
- Hospeda landing pública + `install.sh` personalizado

### Cliente (cada VPS nova que vender)

```bash
curl -sSL https://seudominio.com/install.sh | sudo bash
```

Não precisa editar nada — o `install-server.sh` já substituiu os segredos
no `install.sh` antes de hospedar.

### Empacotar release nova do painel

```bash
# No diretório licensing/
make release
```

Esse `make release` chama o container `builder` que executa todo o pipeline:
adapta credenciais → ofusca → cifra → builda imagem Docker → push para o
registry → registra a master key na API. Sem precisar de Python ou
ferramentas no host.

### Atalhos do Makefile

```bash
make up              # sobe tudo (1ª vez chama bootstrap-secrets sozinho)
make down            # para tudo
make logs            # logs em tempo real (todos)
make logs S=api      # logs de um serviço só
make status          # estado dos containers
make release         # empacota release nova do painel
make release-loader  # recompila só a extensão xsp_loader.so
```

## Fluxo de validação (1 request HTTP)

```
[client request] → Apache (container painel)
        ↓
  bootstrap.php (claro)
        ↓
  license_check.php (claro)
        ↓
  [cache local válido?] ─sim→ xsp_unlock(cache.master_key) → execução normal via xsp://
        ↓ não
  POST /v1/heartbeat (HMAC)
        ↓
  api-license valida → retorna master_key_sealed (AES-GCM com HWID)
        ↓
  PHP unseal → xsp_unlock($master) → execução via xsp://
        ↓
  cache salvo (24h offline)
```
