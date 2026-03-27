# 🚀 CRM Agência — Guia de Instalação

## Pré-requisitos

| Software | Versão mínima |
|----------|--------------|
| PHP | 8.1+ |
| MySQL | 8.0+ (ou MariaDB 10.6+) |
| Servidor Web | Apache / Nginx / XAMPP / Laragon |
| Node.js | Não necessário (CDN) |

---

## 1. Copiar os arquivos

Você precisa enviar os arquivos do projeto para o seu servidor.

### Opção A: Servidor Local (Para testes no seu computador)
Coloque a pasta `crm-app` dentro do seu servidor web:
- **XAMPP / WAMP** → `C:\xampp\htdocs\crm-app`
- **Laragon** → `C:\laragon\www\crm-app`

### Opção B: Hostinger (VPS ou Hospedagem Compartilhada) ⭐ Recomendado
Se você tem uma Hospedagem ou VPS na Hostinger que usa o painel hPanel:

1. **Compacte** a pasta `crm-app` no seu computador (crie um arquivo `crm-app.zip`).
2. Acesse o painel da **Hostinger** e clique em **Gerenciar** no seu site.
3. No menu lateral, vá em **Arquivos** -> **Gerenciador de Arquivos**.
4. Abra a pasta **`public_html`**.
5. Faça o **Upload** do seu arquivo `crm-app.zip` para dentro do `public_html`.
6. Após o upload terminar, clique com o botão direito no arquivo `.zip` e escolha **Extrair**.
7. Isso criará uma pasta chamada `crm-app` no seu servidor.

---

## 2. Criar o banco de dados

1. Abra o **phpMyAdmin** ou MySQL CLI.
2. Execute o arquivo SQL completo:

```sql
SOURCE /caminho/para/crm-app/database/schema.sql;
```

Ou copie e cole o conteúdo de `database/schema.sql` no phpMyAdmin.

---

## 3. Configurar a conexão com o banco

Edite o arquivo `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'crm_agencia');
define('DB_USER', 'root');       // ← seu usuário MySQL
define('DB_PASS', '');           // ← sua senha MySQL
```

---

## 4. Configurar a URL base

Edite o arquivo `config/app.php`:

```php
// Desenvolvimento local:
define('BASE_URL', 'http://localhost/crm-app');

// Produção (exemplo):
define('BASE_URL', 'https://crm.suaagencia.com.br');
```

---

## 5. Acessar o sistema

Abra no navegador:
```
http://localhost/crm-app/admin/login.php
```

**Credenciais padrão:**
- E-mail: `admin@agencia.com`
- Senha: `password`

> ⚠️ **Importante:** Altere a senha padrão nas Configurações após o primeiro acesso!

---

## 6. Estrutura de Pastas

```
crm-app/
├── admin/               ← Páginas admin
│   ├── index.php        ← Dashboard principal
│   ├── clientes.php     ← Lista de clientes
│   ├── cliente-detalhe.php ← Detalhes do cliente
│   ├── kanban.php       ← Kanban visual
│   ├── tarefas.php      ← Gestão de tarefas
│   ├── receitas.php     ← Receitas / vendas
│   ├── trafego.php      ← Métricas de tráfego
│   ├── configuracoes.php ← Configurações
│   ├── login.php
│   └── logout.php
├── api/                 ← Endpoints REST internos
│   ├── clients/         ← create, update, delete
│   ├── tasks/           ← create, update, delete
│   ├── demands/         ← create, update, delete
│   ├── kanban/          ← update-status
│   ├── revenue/         ← create, delete
│   ├── traffic/         ← create
│   ├── notes/           ← create
│   └── notifications/   ← list
├── assets/
│   ├── css/app.css      ← Todos os estilos (dark theme)
│   └── js/app.js        ← Utilitários JS globais
├── components/
│   ├── sidebar.php      ← Navegação lateral
│   └── header.php       ← Cabeçalho
├── config/
│   ├── app.php          ← Configuração global
│   ├── database.php     ← Conexão PDO
│   └── auth.php         ← Autenticação de sessão
├── database/
│   └── schema.sql       ← SQL completo (tabelas + exemplos)
├── includes/
│   └── functions.php    ← Funções utilitárias
└── integrations/
    ├── meta/            ← Meta Ads API (pronto para configurar)
    ├── kiwify/          ← Kiwify API service
    ├── hotmart/         ← Hotmart webhook receiver
    └── tmb/             ← (a decorrer)
```

---

## 7. Configurar Integrações (Futuro)

### Meta Ads
1. Crie um App em [developers.facebook.com](https://developers.facebook.com)
2. Gere um Access Token com permissões: `ads_read`, `ads_management`
3. Defina em `config/app.php`:
   ```php
   define('META_ACCESS_TOKEN',  'EAAZAp...');
   define('META_AD_ACCOUNT_ID', 'act_000000000');
   ```
4. Execute `integrations/meta/fetch-campaigns.php?cliente_id=1`

### Hotmart Webhooks
1. No painel Hotmart → Ferramentas → Webhook
2. Configure a URL: `https://seu-dominio.com/crm-app/integrations/hotmart/webhook.php`
3. As vendas serão cadastradas automaticamente.

### Kiwify
1. Em Kiwify → Conta → Configurações → API, gere sua API Key
2. Defina em `config/app.php`:
   ```php
   define('KIWIFY_API_KEY', 'kw_...');
   ```

---

## 8. Segurança para Produção

```apache
# .htaccess na raiz do projeto (Apache)
<FilesMatch "\.(sql|md|env)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

- Nunca exponha o diretório `/database` publicamente
- Use HTTPS em produção
- Altere as credenciais padrão do banco
- Configure `display_errors = Off` no `php.ini` de produção
- Use variáveis de ambiente para tokens sensíveis

---

## 9. Funcionalidades por Módulo

| Módulo | Funcionalidades |
|--------|----------------|
| **Dashboard** | 8 KPIs, 5 gráficos ApexCharts, clientes recentes, alertas |
| **Clientes** | CRUD completo, filtros por status/nicho, busca |
| **Detalhe Cliente** | Demandas, entregas, tarefas, notas, histórico, métricas |
| **Kanban** | Drag & Drop com SortableJS, atualização AJAX |
| **Tarefas** | CRUD, prioridades, prazo, atualização automática de atraso |
| **Receitas** | CRUD de vendas, KPIs por plataforma |
| **Tráfego** | Métricas de campanhas, KPIs de performance |
| **Configurações** | Senha, integrações, info do sistema |
| **Saúde Operacional** | Cálculo automático: Crítico / Atenção / Estável / Avançado |
| **Notificações** | Tarefas atrasadas, clientes críticos, pendências |

---

## 10. Suporte

Para dúvidas ou customizações, consulte o código — todos os arquivos estão documentados com comentários em português.

**Credenciais de exemplo incluídas no SQL:**
- 4 clientes de demonstração
- Tarefas, demandas, métricas e vendas de exemplo
