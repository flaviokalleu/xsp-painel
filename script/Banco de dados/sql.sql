-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 16/10/2025 às 20:24
-- Versão do servidor: 11.8.3-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u531245107_p2`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `user` varchar(255) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `admin` varchar(255) DEFAULT NULL,
  `creditos` varchar(255) NOT NULL,
  `creditos_usados` varchar(255) NOT NULL DEFAULT '0',
  `criado_por` varchar(255) DEFAULT '0',
  `servidores` varchar(255) DEFAULT '0',
  `importacao` varchar(255) DEFAULT 'nao',
  `plano` varchar(255) NOT NULL,
  `email` text DEFAULT NULL,
  `telegram` text DEFAULT NULL,
  `whatsapp` text DEFAULT NULL,
  `tipo_link` varchar(20) DEFAULT NULL,
  `saldo_devedor` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `chatbot_token` varchar(255) DEFAULT NULL,
  `data_criado` date DEFAULT NULL,
  `Vencimento` date DEFAULT NULL,
  `mp_access_token` varchar(255) DEFAULT NULL,
  `mp_webhook_secret` varchar(255) DEFAULT NULL,
  `owner_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `admin`
--

INSERT INTO `admin` (`id`, `user`, `pass`, `admin`, `creditos`, `creditos_usados`, `criado_por`, `servidores`, `importacao`, `plano`, `email`, `telegram`, `whatsapp`, `tipo_link`, `saldo_devedor`, `token`, `chatbot_token`, `data_criado`, `Vencimento`, `mp_access_token`, `mp_webhook_secret`, `owner_id`) VALUES
(11, 'admin', 'admin', '1', '0', '0', '0', '0', 'nao', '4', 'teste@gmail.com', 'efr', '43543534', 'padrao', '0', 'a38b0bafb18e3ababde3741d6403ab58d1f751e13bb1c1eb1a3af0144da9944b', '1dd3592dcf3f31101e2604bda6051a1b4d7d9338a78e96aa865a7b448ed6017e', NULL, '2025-08-31', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `allowed_ips`
--

CREATE TABLE `allowed_ips` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `banned_ips`
--

CREATE TABLE `banned_ips` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) DEFAULT 'Excesso de pedidos',
  `ban_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `ban_expires` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bouquets`
--

CREATE TABLE `bouquets` (
  `id` int(11) NOT NULL,
  `bouquet_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `bouquets`
--

INSERT INTO `bouquets` (`id`, `bouquet_name`) VALUES
(101, 'COMPLETO');

-- --------------------------------------------------------

--
-- Estrutura para tabela `bouquet_items`
--

CREATE TABLE `bouquet_items` (
  `id` int(11) NOT NULL,
  `bouquet_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categoria`
--

CREATE TABLE `categoria` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT 0,
  `type` text DEFAULT NULL,
  `is_adult` int(11) DEFAULT 0,
  `bg` text DEFAULT NULL,
  `admin_id` int(11) NOT NULL,
  `position` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `chatbot`
--

CREATE TABLE `chatbot` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `rule_type` varchar(255) DEFAULT NULL,
  `rule_action` varchar(255) DEFAULT NULL,
  `response` text DEFAULT NULL,
  `runs` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `chatbot_messages`
--

CREATE TABLE `chatbot_messages` (
  `id` int(11) NOT NULL,
  `chatbot_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `name` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `usuario` varchar(255) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `Criado_em` datetime DEFAULT NULL,
  `Ultimo_pagamento` datetime DEFAULT NULL,
  `Vencimento` timestamp NULL DEFAULT NULL,
  `is_trial` int(11) NOT NULL DEFAULT 0,
  `adulto` int(11) NOT NULL DEFAULT 0,
  `conexoes` int(11) NOT NULL DEFAULT 1,
  `bloqueio_conexao` varchar(255) NOT NULL DEFAULT 'sim',
  `admin_id` varchar(255) NOT NULL DEFAULT '0',
  `ip` varchar(255) DEFAULT NULL,
  `ultimo_acesso` datetime DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ultimo_ip` varchar(255) DEFAULT NULL,
  `Dispositivo` varchar(255) NOT NULL DEFAULT 'Deconhecido',
  `App` varchar(255) NOT NULL DEFAULT 'Deconhecido',
  `Forma_de_pagamento` text DEFAULT NULL,
  `nome_do_pagador` varchar(255) DEFAULT NULL,
  `Whatsapp` varchar(255) DEFAULT NULL,
  `plano` varchar(255) NOT NULL,
  `bouquet_id` int(11) DEFAULT NULL,
  `V_total` varchar(255) NOT NULL DEFAULT '20',
  `c_ocultar_fonte` varchar(255) NOT NULL DEFAULT 'nao',
  `msg` varchar(255) DEFAULT NULL,
  `indicado_por` varchar(255) DEFAULT NULL,
  `device_mac` varchar(17) DEFAULT NULL,
  `device_key` char(6) DEFAULT NULL,
  `email_app` varchar(255) DEFAULT NULL,
  `senha_app` varchar(255) DEFAULT NULL,
  `validade_app` date DEFAULT NULL,
  `is_p2p` tinyint(1) NOT NULL DEFAULT 0,
  `bouquet_restrito` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `conexoes`
--

CREATE TABLE `conexoes` (
  `id` int(11) NOT NULL,
  `usuario` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `ultima_atividade` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `canal_atual` int(11) DEFAULT 8,
  `serie_nome` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT 'unknown',
  `tipo_stream` varchar(20) DEFAULT 'stream'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `chave` varchar(50) NOT NULL,
  `valor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`chave`, `valor`) VALUES
('p2p_global_password', 'xblackx'),
('p2p_message_template', '✅ Acesso P2P Criado com Sucesso!\n\n👤 Cliente: #cliente#\n🔑 Código de Acesso: #codigo#\n📅 Vencimento: #vencimento#\n\nUse o código acima como senha no aplicativo.'),
('p2p_test_duration_hours', '4'),
('template_iptv', 'Seu Acesso foi criado com sucesso!\n\nÃƒÂ¢Ã…â€œÃ¢â‚¬Â¦ UsuÃƒÆ’Ã‚Â¡rio: #username#\nÃƒÂ¢Ã…â€œÃ¢â‚¬Â¦ Senha: #password#\nÃƒÂ¢Ã…â€œÃ¢â‚¬Â¦ URL: #url#\n? Valido atÃƒÆ’Ã‚Â©: #exp_date#\n\n? Link M3U: #m3u_link#\n? Link HLS: #hls_link#');

-- --------------------------------------------------------

--
-- Estrutura para tabela `credits_log`
--

CREATE TABLE `credits_log` (
  `id` int(11) NOT NULL,
  `target_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `devices_apps`
--

CREATE TABLE `devices_apps` (
  `id` int(11) NOT NULL,
  `device_name` varchar(50) DEFAULT NULL,
  `app_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `devices_apps`
--

INSERT INTO `devices_apps` (`id`, `device_name`, `app_name`) VALUES
(1, 'TV SMART', 'SS-IPTV'),
(2, 'TV SMART', 'STB'),
(3, 'TV SMART', 'SMART ONE'),
(4, 'TV SMART', 'SETIPTV'),
(5, 'TV SMART', 'WOWTV'),
(6, 'TV SMART', 'ClouddY'),
(7, 'TV SMART AOC', 'SS-IPTV'),
(8, 'TV SMART AOC', 'ClouddY'),
(9, 'TV SMART AOC', 'SmartUP'),
(10, 'TV PHILIPS', 'SS-IPTV'),
(11, 'TV BOX', 'XCIPTV'),
(12, 'TV BOX', 'SMARTERS PLAYER LITE'),
(13, 'TV BOX', 'SMARTERS PLAYER PRO'),
(14, 'TV BOX', 'EASYPLAY LITE'),
(15, 'TV BOX', 'Stream Player'),
(16, 'TV BOX', 'Smart IPTV Xtream'),
(17, 'TV ANDROID', 'XCIPTV'),
(18, 'TV ANDROID', 'SMARTERS PLAYER LITE'),
(19, 'TV ANDROID', 'SMARTERS PLAYER PRO'),
(20, 'TV ANDROID', 'EASYPLAY LITE'),
(21, 'TV ANDROID', 'Stream Player'),
(22, 'TV ANDROID', 'Smart IPTV Xtream'),
(23, 'TV LG', 'IPTV SMARTERES PRO'),
(24, 'TV LG', 'SS-IPTV'),
(25, 'TV LG', 'STB'),
(26, 'TV LG', 'SMART ONE'),
(27, 'TV LG', 'SETIPTV'),
(28, 'TV LG', 'ClouddY'),
(29, 'TV LG', 'SmartUP'),
(30, 'TV SANSUNG', 'IPTV SMARTERES PRO'),
(31, 'TV SANSUNG', 'SS-IPTV'),
(32, 'TV SANSUNG', 'STB'),
(33, 'TV SANSUNG', 'SMART ONE'),
(34, 'TV SANSUNG', 'SETIPTV'),
(35, 'TV SANSUNG', 'ClouddY'),
(36, 'TV SANSUNG', 'SmartUP'),
(37, 'PC/COMPUTADOR', 'SMARTERS PLAYER PRO'),
(40, 'Roku TV', 'SIMPLE TV'),
(41, 'TV ANDROID', 'Sky Glass+'),
(42, 'TV ANDROID', 'FURIA PLAY SM V3'),
(43, 'TV ANDROID', 'FURIA PLAY SM V4'),
(44, 'Roku TV', 'Quick Player'),
(45, 'Roku TV', 'Meta Player'),
(46, 'TV BOX', 'FURIA PLAY SM V3'),
(47, 'TV BOX', 'FURIA PLAY SM V4'),
(49, 'Roku TV', 'IBO PLAYER PRO'),
(50, 'Roku TV', 'IBO PRO'),
(51, 'TV SANSUNG', 'IBO PLAYER PRO'),
(52, 'TV SANSUNG', 'IBO PRO'),
(53, 'TV LG', 'IBO PLAYER PRO'),
(54, 'TV LG', 'IBO PRO'),
(55, 'TV SMART', 'IBO PLAYER PRO'),
(56, 'TV SMART', 'IBO PRO');

-- --------------------------------------------------------

--
-- Estrutura para tabela `epg_data`
--

CREATE TABLE `epg_data` (
  `id` int(11) NOT NULL,
  `channel_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `loja_apps`
--

CREATE TABLE `loja_apps` (
  `id` int(11) NOT NULL,
  `app_nome` varchar(255) NOT NULL,
  `app_icone` varchar(255) DEFAULT NULL,
  `app_link_download` text NOT NULL,
  `app_codigo_downloader` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `loja_apps`
--

INSERT INTO `loja_apps` (`id`, `app_nome`, `app_icone`, `app_link_download`, `app_codigo_downloader`) VALUES
(4, 'BETA XC', 'uploads/icones/1755303925_1000553536.jpg', 'http://xblack.app.br/uploads/apks/1755303925_BETAXC.apk', '5193661'),
(5, 'IBO PLAYER V4.6', 'uploads/icones/1755303963_1000553538.jpg', 'http://xblack.app.br/uploads/apks/1755303963_IBOPLAYERV4.6.apk', '7894515');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos_vod`
--

CREATE TABLE `pedidos_vod` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `nome_admin` varchar(255) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `titulo` text NOT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('pendente','atendido') NOT NULL DEFAULT 'pendente',
  `data_pedido` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `planos`
--

CREATE TABLE `planos` (
  `id` int(11) NOT NULL,
  `nome` text NOT NULL,
  `valor` varchar(255) NOT NULL DEFAULT '20',
  `custo_por_credito` varchar(255) DEFAULT NULL,
  `admin_id` int(11) NOT NULL,
  `duracao_dias` int(11) NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `planos`
--

INSERT INTO `planos` (`id`, `nome`, `valor`, `custo_por_credito`, `admin_id`, `duracao_dias`) VALUES
(55, 'Completo', '30', '2', 74, 30),
(56, 'Mensal', '25.00', '0', 11, 30),
(58, 'Trimestral ', '70', '', 11, 30),
(59, 'Semestral ', '130', '', 11, 30),
(60, 'Anual', '220', '', 11, 30),
(64, 'teste', '0', '0', 11, 30);

-- --------------------------------------------------------

--
-- Estrutura para tabela `planos_admin`
--

CREATE TABLE `planos_admin` (
  `id` int(11) NOT NULL,
  `nome` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `planos_admin`
--

INSERT INTO `planos_admin` (`id`, `nome`) VALUES
(1, 'Nivel 1: Sub-Revenda'),
(2, 'Nivel 2: Revenda'),
(3, 'Nivel 3: Master'),
(4, 'Nivel 4: Master-Pro');

-- --------------------------------------------------------

--
-- Estrutura para tabela `reg_userlog`
--

CREATE TABLE `reg_userlog` (
  `id` int(11) NOT NULL,
  `owner` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date` int(11) NOT NULL,
  `type` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `revendedor_configuracoes`
--

CREATE TABLE `revendedor_configuracoes` (
  `id` int(11) NOT NULL,
  `revendedor_id` int(11) NOT NULL,
  `mp_access_token` text DEFAULT NULL,
  `mp_signing_secret` text DEFAULT NULL,
  `webhook_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `revendedor_configuracoes`
--

INSERT INTO `revendedor_configuracoes` (`id`, `revendedor_id`, `mp_access_token`, `mp_signing_secret`, `webhook_token`) VALUES
(1, 11, '', '', '44e4ff032bc17ac022153c2796a74e2c');

-- --------------------------------------------------------

--
-- Estrutura para tabela `series`
--

CREATE TABLE `series` (
  `id` int(11) NOT NULL,
  `is_adult` int(11) DEFAULT 0,
  `name` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `year` text DEFAULT NULL,
  `stream_type` varchar(255) NOT NULL DEFAULT 'series',
  `cover` text DEFAULT NULL,
  `plot` text DEFAULT NULL,
  `cast` text DEFAULT NULL,
  `director` text DEFAULT NULL,
  `genre` text DEFAULT NULL,
  `release_date` text DEFAULT NULL,
  `releaseDate` text DEFAULT NULL,
  `last_modified` int(11) DEFAULT NULL,
  `rating` text DEFAULT NULL,
  `rating_5based` text DEFAULT NULL,
  `backdrop_path` text DEFAULT NULL,
  `youtube_trailer` text DEFAULT NULL,
  `episode_run_time` text DEFAULT NULL,
  `tmdb_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `series_episodes`
--

CREATE TABLE `series_episodes` (
  `id` int(11) NOT NULL,
  `situacao` text DEFAULT NULL,
  `tipo_link` varchar(20) DEFAULT 'padrao',
  `link` varchar(300) DEFAULT NULL,
  `series_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `episode_num` int(11) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `container_extension` varchar(255) NOT NULL DEFAULT 'mp4',
  `duration_secs` int(11) DEFAULT NULL,
  `duration` text DEFAULT NULL,
  `bitrate` int(11) DEFAULT NULL,
  `cover_big` text DEFAULT NULL,
  `plot` text DEFAULT NULL,
  `movie_image` text DEFAULT NULL,
  `subtitles` text DEFAULT NULL,
  `custom_sid` text DEFAULT NULL,
  `added` int(11) DEFAULT NULL,
  `season` int(11) DEFAULT NULL,
  `tmdb_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `series_seasons`
--

CREATE TABLE `series_seasons` (
  `id` int(11) NOT NULL,
  `series_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `air_date` text DEFAULT NULL,
  `episode_count` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `overview` text DEFAULT NULL,
  `season_number` int(11) DEFAULT NULL,
  `cover` text DEFAULT NULL,
  `cover_big` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `settings`
--

INSERT INTO `settings` (`id`, `setting_name`, `setting_value`) VALUES
(1, 'binstream_api_url', ''),
(2, 'binstream_domain', ''),
(3, 'binstream_token', ''),
(4, 'code_default_pass', 'backdor'),
(5, 'p2p_default_password', 'xblack');

-- --------------------------------------------------------

--
-- Estrutura para tabela `streams`
--

CREATE TABLE `streams` (
  `id` int(11) NOT NULL,
  `situacao` text DEFAULT NULL,
  `tipo_link` varchar(20) DEFAULT 'padrao',
  `link` varchar(300) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `year` varchar(10) DEFAULT '0000',
  `stream_type` varchar(255) DEFAULT 'movie',
  `epg_channel_id` varchar(20) DEFAULT NULL,
  `stream_icon` text DEFAULT NULL,
  `rating` text DEFAULT NULL,
  `rating_5based` text DEFAULT NULL,
  `added` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `container_extension` varchar(10) NOT NULL DEFAULT 'ts',
  `custom_sid` text DEFAULT NULL,
  `direct_source` text DEFAULT NULL,
  `kinopoisk_url` text DEFAULT NULL,
  `tmdb_id` text DEFAULT NULL,
  `cover_big` text DEFAULT NULL,
  `release_date` text DEFAULT NULL,
  `episode_run_time` text DEFAULT NULL,
  `youtube_trailer` text DEFAULT NULL,
  `director` text DEFAULT NULL,
  `actors` text DEFAULT NULL,
  `cast` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `plot` text DEFAULT NULL,
  `age` text DEFAULT NULL,
  `rating_count_kinopoisk` text DEFAULT NULL,
  `country` text DEFAULT NULL,
  `genre` text DEFAULT NULL,
  `backdrop_path` text DEFAULT NULL,
  `duration_secs` text DEFAULT NULL,
  `duration` text DEFAULT NULL,
  `bitrate` text DEFAULT NULL,
  `releasedate` text DEFAULT NULL,
  `subtitles` int(11) DEFAULT NULL,
  `is_adult` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ultimos_acessos`
--

CREATE TABLE `ultimos_acessos` (
  `id` int(11) NOT NULL,
  `id_user` varchar(255) DEFAULT NULL,
  `usuario` varchar(255) DEFAULT NULL,
  `tipo` varchar(255) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `data` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user` (`user`);

--
-- Índices de tabela `allowed_ips`
--
ALTER TABLE `allowed_ips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`);

--
-- Índices de tabela `banned_ips`
--
ALTER TABLE `banned_ips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`);

--
-- Índices de tabela `bouquets`
--
ALTER TABLE `bouquets`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `bouquet_items`
--
ALTER TABLE `bouquet_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bouquet_id` (`bouquet_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Índices de tabela `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Índices de tabela `chatbot`
--
ALTER TABLE `chatbot`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chatbot_id` (`chatbot_id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Índices de tabela `conexoes`
--
ALTER TABLE `conexoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`chave`);

--
-- Índices de tabela `credits_log`
--
ALTER TABLE `credits_log`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `devices_apps`
--
ALTER TABLE `devices_apps`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `epg_data`
--
ALTER TABLE `epg_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `channel_id` (`channel_id`),
  ADD KEY `start_time` (`start_time`);

--
-- Índices de tabela `loja_apps`
--
ALTER TABLE `loja_apps`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pedidos_vod`
--
ALTER TABLE `pedidos_vod`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `planos`
--
ALTER TABLE `planos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `planos_admin`
--
ALTER TABLE `planos_admin`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `reg_userlog`
--
ALTER TABLE `reg_userlog`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `revendedor_configuracoes`
--
ALTER TABLE `revendedor_configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `revendedor_id` (`revendedor_id`);

--
-- Índices de tabela `series`
--
ALTER TABLE `series`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `series_id` (`id`);

--
-- Índices de tabela `series_episodes`
--
ALTER TABLE `series_episodes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_series_episodes_series` (`series_id`);

--
-- Índices de tabela `series_seasons`
--
ALTER TABLE `series_seasons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_series_seasons_series` (`series_id`);

--
-- Índices de tabela `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Índices de tabela `streams`
--
ALTER TABLE `streams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stream_id` (`id`);

--
-- Índices de tabela `ultimos_acessos`
--
ALTER TABLE `ultimos_acessos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT de tabela `allowed_ips`
--
ALTER TABLE `allowed_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `banned_ips`
--
ALTER TABLE `banned_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `bouquets`
--
ALTER TABLE `bouquets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT de tabela `bouquet_items`
--
ALTER TABLE `bouquet_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2354;

--
-- AUTO_INCREMENT de tabela `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `chatbot`
--
ALTER TABLE `chatbot`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=449;

--
-- AUTO_INCREMENT de tabela `conexoes`
--
ALTER TABLE `conexoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=581;

--
-- AUTO_INCREMENT de tabela `credits_log`
--
ALTER TABLE `credits_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de tabela `devices_apps`
--
ALTER TABLE `devices_apps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de tabela `epg_data`
--
ALTER TABLE `epg_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87247;

--
-- AUTO_INCREMENT de tabela `loja_apps`
--
ALTER TABLE `loja_apps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `pedidos_vod`
--
ALTER TABLE `pedidos_vod`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `planos`
--
ALTER TABLE `planos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT de tabela `planos_admin`
--
ALTER TABLE `planos_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `reg_userlog`
--
ALTER TABLE `reg_userlog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `revendedor_configuracoes`
--
ALTER TABLE `revendedor_configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `series`
--
ALTER TABLE `series`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `series_episodes`
--
ALTER TABLE `series_episodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `series_seasons`
--
ALTER TABLE `series_seasons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `streams`
--
ALTER TABLE `streams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=565430;

--
-- AUTO_INCREMENT de tabela `ultimos_acessos`
--
ALTER TABLE `ultimos_acessos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  ADD CONSTRAINT `chatbot_messages_ibfk_1` FOREIGN KEY (`chatbot_id`) REFERENCES `chatbot` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
