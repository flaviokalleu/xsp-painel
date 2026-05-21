# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## O que é este repositório

Dois projetos acoplados:

- **`script/`** — Painel Office Xtream Server Pro V10. Painel IPTV em PHP 8.x +
  Apache + MariaDB. ~513 arquivos PHP, ~47 MB. Tem áreas de admin, cliente,
  revendedor, importador M3U, chatbot integrado.

- **`licensing/`** — Sistema de licenciamento + anti-pirataria construído
  para distribuir o `script/` como produto SaaS. Veja `licensing/CLAUDE.md`
  para detalhes deste subsistema.

Os dois são interdependentes: o `script/` é cifrado/embalado pelo pipeline
de `licensing/painel-image/` e distribuído como imagem Docker para clientes
que ativam licenças mensais.

## Idioma

O dono (Flavio, `netsharkssh@gmail.com`) interage **em português**. Responda
em PT-BR. Comentários no código também em PT-BR (já é convenção do repo).

## Invariantes críticas (não quebrar)

1. **O HWID precisa ser idêntico em 4 lugares.** Se mudar o algoritmo em um,
   atualizar nos outros 3 — senão heartbeats falham e clientes perdem acesso:
   - `licensing/installer-go/internal/system/system.go::ComputeHWID`
   - `licensing/install-painel.sh` (cálculo bash com `sha256sum`)
   - `licensing/painel-image/php-stub/license_check.php::xsp_compute_hwid`
   - O que está armazenado em `installations.hwid` no Postgres da API
   
   Fórmula canônica: `sha256( machine_id || 0x1f || board_uuid || 0x1f || disk_uuid || 0x1f || mac )`.

2. **Não modificar `script/` diretamente para adicionar funcionalidade.** O
   pipeline (`licensing/painel-image/build/adapt-panel.py`) opera sobre uma
   cópia em `/tmp` e o original deveria permanecer "estado limpo". Exceção:
   correções no painel em si (bugs no PHP do IPTV).

3. **HWID, Apache config e `_xsp_db.php` são produzidos pelo pipeline** —
   não dá pra editar manualmente porque são regerados a cada `make release`.

4. **`script-BACKUP-20260519-*` existe.** É o painel original ANTES do
   saneamento de credenciais. Não apagar sem confirmar com o usuário —
   é o único lugar com as credenciais antigas se ele precisar reverter.

## Comandos comuns

Todos os comandos rodam de `licensing/` (a raiz é só organizacional):

```bash
cd licensing/

# Setup inicial na VPS central — 1 comando
sudo bash install-server.sh

# Operação rotineira (usa docker-compose.yml + Makefile)
make up              # sobe stack completo (caddy+api+admin+portal+db+redis+registry)
make down            # para tudo
make logs            # logs em tempo real (todos)
make logs S=api      # logs de um serviço
make status          # estado dos containers
make restart         # reinicia tudo

# Publicar nova release do painel
make release         # roda pipeline: adapt → obfuscate → encrypt → docker build → push
make release-loader  # só recompila a extensão C xsp_loader.so

# Verificação e build
cd api-license && go vet ./... && go mod tidy
cd installer-go && CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build ./cmd/installer

# Lint estático rápido
bash -n licensing/install-server.sh licensing/install-painel.sh   # sintaxe shell
python licensing/dist/_lint_php.py licensing/                     # PHP {} balanceado

# Gerar/regenerar zips de distribuição
cd licensing/dist && PYTHONIOENCODING=utf-8 python _make_zips.py
```

## Onde o quê mora

Quando o usuário pedir mudança, vá direto para a camada certa:

| Pedido típico | Onde mexer |
|---|---|
| "Adicionar X no painel IPTV" | `script/` (depois `make release`) |
| "Mudar fluxo do instalador cliente" | `licensing/install-painel.sh` |
| "Mudar setup da VPS central" | `licensing/install-server.sh` ou `docker-compose.yml` |
| "Endpoint novo da API" | `licensing/api-license/internal/handler/` |
| "Lógica de licença/plano" | `licensing/api-license/internal/service/` |
| "Cifragem / decifragem PHP" | `licensing/xsp-loader/xsp_loader.c` |
| "Validação online / heartbeat" | `licensing/painel-image/php-stub/license_check.php` |
| "Pipeline de build da release" | `licensing/painel-image/build/` |
| "Página pública de instalação" | `licensing/landing/` |
| "Painel admin (gerenciar KEYs)" | `licensing/admin-dashboard/index.php` |
| "Portal do cliente (self-service)" | `licensing/customer-portal/index.php` |
| "Integração de pagamento" | `licensing/api-license/internal/handler/mp_webhook.go` |

## Convenções não-óbvias

- **Tudo roda em Docker.** Única dep no host é Docker. Não instale pacotes
  com `apt-get` para resolver problemas — adicione ao container correto.
- **Go é compilado dentro de container** (`golang:1.22-alpine`) — não exige
  Go instalado na VPS. Veja `bootstrap-secrets.sh` para padrão.
- **Imagem cliente Docker tem `.php.enc` em vez de `.php`.** Stream wrapper
  `xsp://` decifra em memória. Mexer em `apache.conf::include_path` quebra
  isso silenciosamente.
- **Bootstrap PHP em claro é por design.** Os outros stubs (`license_check`,
  `index_router`) também ficam em claro porque chamam `xsp_unlock()` da
  extensão C — não dá pra cifrar o arquivo que destrava a cifragem.
- **`go.mod` indirects podem ficar bagunçados** com `go mod tidy` rodado em
  máquinas diferentes (Windows vs Linux). Não brigue com diferenças de
  `// indirect`.

## Segredos que NUNCA podem ser commitados

- `licensing/.env` — tem ADMIN_TOKEN, Ed25519 priv, RELEASE_MASTER_KEY
- `licensing/api-license/.env`
- `licensing/api-license/auth/htpasswd` — credenciais do registry
- Qualquer `*.pem` privado em `licensing/installer-go/` ou `/opt/xsp/keys/`

`.env.example` é seguro porque tem só placeholders.

## Subsistema licensing/ — leia antes de mexer

`licensing/CLAUDE.md` tem decisões de design específicas: por que AES-GCM em
vez de ionCube, por que MariaDB no cliente vs Postgres central, política de
heartbeat, e o modelo de ameaça. Leia antes de propor mudanças arquiteturais.
