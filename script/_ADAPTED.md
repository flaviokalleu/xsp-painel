# Painel adaptado (script-cleaned)

Este diretório é o resultado de `adapt-panel.py` rodando sobre o painel
original. Nada aqui é cifrado — a cifragem acontece depois, em `encrypt.sh`.

## O que foi alterado

- Credenciais de banco substituídas por `getenv('DB_*')`.
- Adicionado `_xsp_db.php` como conector central.
- Removidos: TUTORIAL.txt, error_log, *.bak, *.log, *.gz, backups/.

## Como conectar ao DB nos arquivos NOVOS

```php
require_once 'xsp:///var/www/html/_xsp_db.php';
$pdo = xsp_db();
```

Para arquivos que ainda usam variáveis soltas, ainda funciona — as vars
agora vêm de env e apontam para o MariaDB do container.

## Próximo passo

```
bash encrypt.sh C:\Users\Flavio\Downloads\Script Painel Office Xtream Server Pro V10 Com Chatbot Atual\script-cleaned /caminho/saida
```
