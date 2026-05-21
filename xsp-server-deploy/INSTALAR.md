# XSP Licensing — Instalação do Servidor Central

## Requisitos da VPS

- Ubuntu 22.04 / 24.04 LTS (ou Debian 12)
- Mínimo: 2 vCPU, 2 GB RAM, 20 GB disco
- Porta 80 e 443 abertas no firewall da provedora
- Domínio ou IP apontando para a VPS

---

## Instalação em 2 passos

**1. Suba o pacote para a VPS:**

```bash
scp -r xsp-server-deploy/ root@SEU_IP_VPS:/opt/xsp-licensing
```

**2. Na VPS, execute:**

```bash
cd /opt/xsp-licensing
chmod +x install-server
sudo ./install-server
```

O instalador pergunta interativamente:
- Modo de acesso (subdomínios / domínio único / IP)
- Domínio(s) e e-mail Let's Encrypt
- Usuário do admin dashboard

E faz tudo automaticamente:
- Instala Docker
- Gera todos os segredos (.env)
- Configura Caddy (TLS automático)
- Sobe o stack completo
- Personaliza o install.sh dos clientes

---

## Após instalação

| O que | Como |
|---|---|
| Ver estado dos containers | `make status` |
| Ver logs em tempo real | `make logs` |
| Logs de um serviço só | `make logs S=api` |
| Parar tudo | `make down` |
| Reiniciar tudo | `make restart` |
| Publicar nova release do painel | `make release` |

---

## Fluxo de uso

```
1. Acesse o admin dashboard → crie uma KEY para o cliente
2. O dashboard mostra o comando de instalação pronto
3. Envie o comando ao cliente (formato):
   curl -sSL https://SEUDOMINIO/install.sh | sudo bash -s -- XSP-XXXX-XXXX-XXXX-XXXX
4. O cliente roda o comando → painel instala automaticamente
```

---

## Segurança

- O arquivo `.env` gerado contém todos os segredos — não compartilhe
- Cada KEY permite exatamente **1 instalação** vinculada ao IP do cliente
- Se o cliente precisar trocar de VPS: revogue a KEY e gere uma nova no admin

---

## Problemas comuns

**API não sobe:**
```bash
make logs S=api
make logs S=db
```

**Certificado TLS não gerado:**
- Verifique se o domínio aponta para o IP da VPS
- `make logs S=caddy`

**Reinstalar do zero:**
```bash
make clean   # apaga volumes — CUIDADO, apaga dados
sudo ./install-server
```
