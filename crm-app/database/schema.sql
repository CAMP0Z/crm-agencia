-- ============================================================
-- CRM AGÊNCIA - SCHEMA COMPLETO
-- Versão: 1.0.0 | Data: 2026-03-27
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- BANCO DE DADOS
-- ============================================================
CREATE DATABASE IF NOT EXISTS crm_agencia
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE crm_agencia;

-- ============================================================
-- TABELA: usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `nome`       VARCHAR(120) NOT NULL,
    `email`      VARCHAR(180) NOT NULL UNIQUE,
    `senha`      VARCHAR(255) NOT NULL,
    `avatar`     VARCHAR(255) DEFAULT NULL,
    `perfil`     ENUM('admin','gestor','analista') NOT NULL DEFAULT 'analista',
    `ativo`      TINYINT(1) NOT NULL DEFAULT 1,
    `ultimo_login` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuário admin padrão (senha: admin123)
INSERT INTO `usuarios` (`nome`, `email`, `senha`, `perfil`) VALUES
('Administrador', 'admin@agencia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ============================================================
-- TABELA: clientes
-- ============================================================
CREATE TABLE IF NOT EXISTS `clientes` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `empresa`             VARCHAR(200) NOT NULL,
    `responsavel`         VARCHAR(150) NOT NULL,
    `email`               VARCHAR(200) DEFAULT NULL,
    `telefone`            VARCHAR(30) DEFAULT NULL,
    `instagram`           VARCHAR(120) DEFAULT NULL,
    `nicho`               VARCHAR(120) DEFAULT NULL,
    `observacoes`         TEXT DEFAULT NULL,
    `status`              ENUM('onboarding','aguardando_cliente','em_execucao','revisao','concluido','pausado') NOT NULL DEFAULT 'onboarding',
    `saude_operacional`   ENUM('critico','atencao','estavel','avancado') NOT NULL DEFAULT 'estavel',
    `origem`              VARCHAR(100) DEFAULT NULL,
    `valor_mensal`        DECIMAL(10,2) DEFAULT 0.00,
    `plataforma_principal` VARCHAR(80) DEFAULT NULL,
    `gestor_responsavel`  VARCHAR(150) DEFAULT NULL,
    `cor_avatar`          VARCHAR(7) DEFAULT '#0ea5e9',
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: demandas_cliente (o que eu preciso do cliente)
-- ============================================================
CREATE TABLE IF NOT EXISTS `demandas_cliente` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `cliente_id` INT NOT NULL,
    `titulo`     VARCHAR(255) NOT NULL,
    `descricao`  TEXT DEFAULT NULL,
    `status`     ENUM('pendente','em_andamento','concluido') NOT NULL DEFAULT 'pendente',
    `ordem`      INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: entregas_agencia (o que o cliente precisa de mim)
-- ============================================================
CREATE TABLE IF NOT EXISTS `entregas_agencia` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `cliente_id` INT NOT NULL,
    `titulo`     VARCHAR(255) NOT NULL,
    `descricao`  TEXT DEFAULT NULL,
    `status`     ENUM('pendente','em_andamento','concluido') NOT NULL DEFAULT 'pendente',
    `ordem`      INT NOT NULL DEFAULT 0,
    `prazo`      DATE DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: tarefas
-- ============================================================
CREATE TABLE IF NOT EXISTS `tarefas` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `cliente_id`   INT DEFAULT NULL,
    `titulo`       VARCHAR(255) NOT NULL,
    `descricao`    TEXT DEFAULT NULL,
    `categoria`    VARCHAR(100) DEFAULT NULL,
    `prioridade`   ENUM('baixa','media','alta','urgente') NOT NULL DEFAULT 'media',
    `prazo`        DATE DEFAULT NULL,
    `status`       ENUM('pendente','em_andamento','concluida','atrasada') NOT NULL DEFAULT 'pendente',
    `responsavel`  VARCHAR(150) DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: metricas_trafego
-- ============================================================
CREATE TABLE IF NOT EXISTS `metricas_trafego` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `cliente_id`      INT NOT NULL,
    `plataforma`      ENUM('meta_ads','google_ads','tiktok_ads','linkedin_ads','outros') NOT NULL DEFAULT 'meta_ads',
    `campanha`        VARCHAR(255) DEFAULT NULL,
    `conta_anuncio`   VARCHAR(100) DEFAULT NULL,
    `periodo_inicio`  DATE NOT NULL,
    `periodo_fim`     DATE NOT NULL,
    `valor_investido` DECIMAL(10,2) DEFAULT 0.00,
    `impressoes`      INT DEFAULT 0,
    `cliques`         INT DEFAULT 0,
    `ctr`             DECIMAL(5,2) DEFAULT 0.00,
    `cpc`             DECIMAL(8,2) DEFAULT 0.00,
    `cpm`             DECIMAL(8,2) DEFAULT 0.00,
    `leads`           INT DEFAULT 0,
    `conversas`       INT DEFAULT 0,
    `custo_por_lead`  DECIMAL(8,2) DEFAULT 0.00,
    `conversoes`      INT DEFAULT 0,
    `roas`            DECIMAL(6,2) DEFAULT 0.00,
    `criado_via`      ENUM('manual','api','csv') NOT NULL DEFAULT 'manual',
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: vendas_plataformas
-- ============================================================
CREATE TABLE IF NOT EXISTS `vendas_plataformas` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `cliente_id`   INT DEFAULT NULL,
    `plataforma`   ENUM('kiwify','tmb','hotmart','monetizze','eduzz','outros') NOT NULL DEFAULT 'kiwify',
    `produto`      VARCHAR(255) NOT NULL,
    `comprador`    VARCHAR(200) DEFAULT NULL,
    `valor_bruto`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `valor_liquido` DECIMAL(10,2) DEFAULT 0.00,
    `comissao`     DECIMAL(10,2) DEFAULT 0.00,
    `status`       ENUM('aprovado','pendente','cancelado','reembolsado','chargeback') NOT NULL DEFAULT 'aprovado',
    `data_venda`   DATE NOT NULL,
    `id_externo`   VARCHAR(255) DEFAULT NULL COMMENT 'ID da venda na plataforma',
    `criado_via`   ENUM('manual','api','csv','webhook') NOT NULL DEFAULT 'manual',
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: notas_cliente
-- ============================================================
CREATE TABLE IF NOT EXISTS `notas_cliente` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `cliente_id` INT NOT NULL,
    `autor`      VARCHAR(150) DEFAULT 'Admin',
    `titulo`     VARCHAR(255) DEFAULT NULL,
    `conteudo`   TEXT NOT NULL,
    `tipo`       ENUM('nota','reuniao','ligacao','email','outro') NOT NULL DEFAULT 'nota',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: historico_status
-- ============================================================
CREATE TABLE IF NOT EXISTS `historico_status` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `cliente_id`      INT NOT NULL,
    `status_anterior` VARCHAR(80) NOT NULL,
    `status_novo`     VARCHAR(80) NOT NULL,
    `alterado_por`    VARCHAR(150) DEFAULT 'Sistema',
    `observacao`      TEXT DEFAULT NULL,
    `alterado_em`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DADOS DE EXEMPLO
-- ============================================================
INSERT INTO `clientes` (`empresa`, `responsavel`, `email`, `telefone`, `instagram`, `nicho`, `status`, `saude_operacional`, `origem`, `valor_mensal`, `plataforma_principal`, `gestor_responsavel`) VALUES
('Clínica Bem Estar', 'Dra. Ana Lima', 'ana@clinica.com', '(11) 99999-0001', '@clinica_bem_estar', 'Saúde', 'em_execucao', 'estavel', 'Indicação', 3500.00, 'meta_ads', 'Admin'),
('Academia PowerFit', 'Carlos Mendes', 'carlos@powerfit.com', '(11) 99999-0002', '@powerfit_sp', 'Fitness', 'onboarding', 'atencao', 'Instagram', 2800.00, 'meta_ads', 'Admin'),
('Escritório Jurídico Silva', 'Dr. Roberto Silva', 'roberto@drsilva.com.br', '(11) 99999-0003', '@drsilva_adv', 'Jurídico', 'aguardando_cliente', 'critico', 'Google', 4200.00, 'google_ads', 'Admin'),
('Loja Moda Trend', 'Fernanda Costa', 'fernanda@modatrend.com', '(11) 99999-0004', '@modatrend_oficial', 'E-commerce', 'em_execucao', 'avancado', 'Indicação', 5100.00, 'meta_ads', 'Admin');

INSERT INTO `demandas_cliente` (`cliente_id`, `titulo`, `status`) VALUES
(1, 'Acesso BM', 'concluido'),
(1, 'Acesso Página Facebook', 'concluido'),
(1, 'Acesso Instagram', 'pendente'),
(1, 'Criativos Aprovados', 'em_andamento'),
(2, 'Acesso BM', 'pendente'),
(2, 'Briefing Completo', 'pendente'),
(2, 'Orçamento Mensal', 'concluido');

INSERT INTO `entregas_agencia` (`cliente_id`, `titulo`, `status`) VALUES
(1, 'Campanha Criada', 'concluido'),
(1, 'Landing Page', 'em_andamento'),
(1, 'Relatório Mensal', 'pendente'),
(2, 'Campanha Criada', 'pendente'),
(2, 'Criativos Desenvolvidos', 'pendente');

INSERT INTO `tarefas` (`cliente_id`, `titulo`, `prioridade`, `prazo`, `status`, `responsavel`) VALUES
(1, 'Otimizar campanha de leads', 'alta', '2026-04-05', 'em_andamento', 'Admin'),
(1, 'Enviar relatório mensal', 'media', '2026-03-31', 'pendente', 'Admin'),
(2, 'Criar briefing da campanha', 'urgente', '2026-03-28', 'pendente', 'Admin'),
(3, 'Aguardar acesso ao BM', 'alta', '2026-03-30', 'pendente', 'Admin'),
(NULL, 'Renovar domínio da agência', 'baixa', '2026-04-15', 'pendente', 'Admin');

INSERT INTO `metricas_trafego` (`cliente_id`, `plataforma`, `campanha`, `periodo_inicio`, `periodo_fim`, `valor_investido`, `impressoes`, `cliques`, `ctr`, `cpc`, `cpm`, `leads`, `conversas`, `custo_por_lead`) VALUES
(1, 'meta_ads', 'Campanha Leads Q1', '2026-03-01', '2026-03-31', 3500.00, 120000, 3600, 3.00, 0.97, 29.17, 72, 58, 48.61),
(4, 'meta_ads', 'Campanha Vendas', '2026-03-01', '2026-03-31', 5100.00, 200000, 8000, 4.00, 0.64, 25.50, 160, 0, 31.88);

INSERT INTO `vendas_plataformas` (`cliente_id`, `plataforma`, `produto`, `valor_bruto`, `valor_liquido`, `comissao`, `status`, `data_venda`) VALUES
(4, 'kiwify', 'Curso de Moda Online', 997.00, 850.00, 147.00, 'aprovado', '2026-03-15'),
(4, 'hotmart', 'Mentoria Fashion', 1997.00, 1700.00, 297.00, 'aprovado', '2026-03-18'),
(1, 'kiwify', 'Consultoria Clínica', 497.00, 420.00, 77.00, 'aprovado', '2026-03-20'),
(4, 'tmb', 'Planilha Estoque', 97.00, 82.00, 15.00, 'aprovado', '2026-03-22');

SET FOREIGN_KEY_CHECKS = 1;
