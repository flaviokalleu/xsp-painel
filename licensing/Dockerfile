###############################################################################
#  XSP Licensing — Imagem do instalador do servidor central
#
#  Uso na VPS:
#    docker run --rm -it \
#      -v /var/run/docker.sock:/var/run/docker.sock \
#      -v /root:/root \
#      -w /root \
#      ghcr.io/flaviokalleu/xsp-licensing:latest
###############################################################################

# ── Estágio 1: compilar o instalador ─────────────────────────────────────────
FROM golang:1.22-alpine AS builder

RUN apk add --no-cache zip

WORKDIR /src
COPY . .

RUN zip -r bundle.zip \
      api-license \
      admin-dashboard \
      customer-portal \
      landing \
      builder \
      painel-image \
      xsp-loader \
      docker-compose.yml \
      Makefile \
      .env.example \
      install-painel.sh \
      Caddyfile && \
    CGO_ENABLED=0 GOOS=linux GOARCH=amd64 \
      go build -ldflags="-s -w" -o /install-server install-server.go && \
    rm -f bundle.zip

# ── Estágio 2: imagem mínima de execução ─────────────────────────────────────
FROM alpine:3.19

# docker CLI necessário para rodar docker compose dentro do container
RUN apk add --no-cache docker-cli curl bash

COPY --from=builder /install-server /usr/local/bin/install-server
RUN chmod +x /usr/local/bin/install-server

ENTRYPOINT ["install-server"]
