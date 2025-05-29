-- MySQL dump 10.13  Distrib 8.0.42, for Linux (x86_64)
--
-- Host: localhost    Database: manutencao_maquinas
-- ------------------------------------------------------
-- Server version	8.0.42-0ubuntu0.22.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `attachments`
--

DROP TABLE IF EXISTS `attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attachments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `service_orders` (`id`),
  CONSTRAINT `attachments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attachments`
--

LOCK TABLES `attachments` WRITE;
/*!40000 ALTER TABLE `attachments` DISABLE KEYS */;
INSERT INTO `attachments` VALUES (7,4,NULL,'682f77a6bdfb9_Captura de tela de 2025-05-22 14-00-26.png','2025-05-22 16:14:46');
/*!40000 ALTER TABLE `attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chamado_anexos`
--

DROP TABLE IF EXISTS `chamado_anexos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chamado_anexos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chamado_id` int NOT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `nome_original` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `chamado_id` (`chamado_id`),
  CONSTRAINT `chamado_anexos_ibfk_1` FOREIGN KEY (`chamado_id`) REFERENCES `chamados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chamado_anexos`
--

LOCK TABLES `chamado_anexos` WRITE;
/*!40000 ALTER TABLE `chamado_anexos` DISABLE KEYS */;
/*!40000 ALTER TABLE `chamado_anexos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chamado_historico`
--

DROP TABLE IF EXISTS `chamado_historico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chamado_historico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chamado_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `acao` varchar(50) NOT NULL COMMENT 'edição, atribuição, comentário, etc',
  `comentario` text NOT NULL,
  `data` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `chamado_id` (`chamado_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `chamado_historico_ibfk_1` FOREIGN KEY (`chamado_id`) REFERENCES `chamados` (`id`),
  CONSTRAINT `chamado_historico_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chamado_historico`
--

LOCK TABLES `chamado_historico` WRITE;
/*!40000 ALTER TABLE `chamado_historico` DISABLE KEYS */;
INSERT INTO `chamado_historico` VALUES (1,18,1,'comentário','Ok','2025-05-22 14:37:46'),(2,18,1,'atribuição','Chamado atribuído ao técnico','2025-05-22 14:38:04'),(3,17,1,'atribuição','Chamado atribuído ao técnico','2025-05-23 08:03:24'),(4,19,1,'atribuição','Chamado atribuído ao técnico','2025-05-23 08:03:32'),(5,16,1,'atribuição','Chamado atribuído ao técnico','2025-05-23 08:05:05'),(6,15,1,'atribuição','Chamado atribuído ao técnico','2025-05-23 08:05:10'),(7,19,1,'edição','Chamado editado por administrador','2025-05-23 08:06:18'),(8,18,1,'edição','Chamado editado por administrador','2025-05-23 08:10:44'),(9,20,1,'edição','Chamado editado por administrador','2025-05-29 15:56:11'),(10,20,1,'atribuição','Chamado atribuído ao técnico','2025-05-29 15:57:10'),(11,18,1,'atribuição','Chamado atribuído ao técnico','2025-05-29 15:57:15');
/*!40000 ALTER TABLE `chamado_historico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chamado_service_order`
--

DROP TABLE IF EXISTS `chamado_service_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chamado_service_order` (
  `chamado_id` int NOT NULL,
  `service_order_id` int NOT NULL,
  PRIMARY KEY (`chamado_id`),
  KEY `service_order_id` (`service_order_id`),
  CONSTRAINT `chamado_service_order_ibfk_1` FOREIGN KEY (`chamado_id`) REFERENCES `chamados` (`id`),
  CONSTRAINT `chamado_service_order_ibfk_2` FOREIGN KEY (`service_order_id`) REFERENCES `service_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chamado_service_order`
--

LOCK TABLES `chamado_service_order` WRITE;
/*!40000 ALTER TABLE `chamado_service_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `chamado_service_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chamados`
--

DROP TABLE IF EXISTS `chamados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chamados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setor_id` int NOT NULL,
  `maquina_id` int NOT NULL,
  `descricao` text NOT NULL,
  `status` enum('aberto','em_andamento','concluido') DEFAULT 'aberto',
  `criado_por` int NOT NULL,
  `atribuido_a` int DEFAULT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `setor_id` (`setor_id`),
  KEY `maquina_id` (`maquina_id`),
  KEY `criado_por` (`criado_por`),
  KEY `atribuido_a` (`atribuido_a`),
  CONSTRAINT `chamados_ibfk_1` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`),
  CONSTRAINT `chamados_ibfk_2` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`),
  CONSTRAINT `chamados_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `users` (`id`),
  CONSTRAINT `chamados_ibfk_4` FOREIGN KEY (`atribuido_a`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chamados`
--

LOCK TABLES `chamados` WRITE;
/*!40000 ALTER TABLE `chamados` DISABLE KEYS */;
INSERT INTO `chamados` VALUES (15,11,2,'Solicitante: Wesley\n\nProblema Grave','em_andamento',1,1,'2025-05-22 11:42:33','2025-05-23 08:05:10'),(16,17,2,'Solicitante: Wesley\n\nProblema','em_andamento',1,1,'2025-05-22 11:43:09','2025-05-23 08:05:05'),(17,22,3,'Solicitante: Wesley\n\nTetse','em_andamento',1,1,'2025-05-22 11:44:42','2025-05-23 08:03:24'),(18,14,2,'Solicitante: Wesley\r\n\r\nteste','em_andamento',1,1,'2025-05-22 14:20:24','2025-05-29 15:57:15'),(19,17,2,'Solicitante: wesley\r\n\r\nMáquina apresenta sérios problemas no rolamento principal','concluido',1,NULL,'2025-05-23 08:02:26','2025-05-23 08:06:18'),(20,22,2,'Solicitante: Cristina\r\n\r\nProblema grave no rolamento','em_andamento',1,1,'2025-05-29 15:55:38','2025-05-29 15:57:10');
/*!40000 ALTER TABLE `chamados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `machines`
--

DROP TABLE IF EXISTS `machines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `machines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `model` varchar(50) DEFAULT NULL,
  `manufacturer` varchar(50) DEFAULT NULL,
  `acquisition_date` date DEFAULT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `sector` varchar(100) NOT NULL,
  `equipment` varchar(100) NOT NULL,
  `axis` varchar(50) DEFAULT NULL,
  `rotor` varchar(50) DEFAULT NULL,
  `gasket` varchar(50) DEFAULT NULL,
  `motor` varchar(50) DEFAULT NULL,
  `hp` varchar(20) DEFAULT NULL,
  `rpm` varchar(20) DEFAULT NULL,
  `amp` varchar(20) DEFAULT NULL,
  `motor_bearing` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `machines`
--

LOCK TABLES `machines` WRITE;
/*!40000 ALTER TABLE `machines` DISABLE KEYS */;
INSERT INTO `machines` VALUES (1,'MÁQUINA DE PAPEL','FILIPERSON','FILIPERSON','1970-01-15','Produção de Papel','2025-04-15 17:01:36','MÁQUINA DE PAPEL','MÁQUINA DE PAPEL','TESTE','TESTE','TESTE','TESTE','555','555','555','555'),(2,'CORTADEIRA','CORTADEIRA','JANGERBERG','1980-04-29','','2025-04-29 13:11:27','cutsize','MÁQUINA','TESTE','TESTE','TESTE','TESTE','TESTE','TESTE','TESTE','TESTE'),(3,'IMPRESSORA OFF-SET','200','ROLAND','2022-04-29','','2025-04-29 13:12:44','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `machines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_schedules`
--

DROP TABLE IF EXISTS `maintenance_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `machine_id` int NOT NULL,
  `type` enum('preventiva','corretiva') NOT NULL,
  `frequency` enum('diaria','semanal','mensal','anual') DEFAULT NULL,
  `next_date` date DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `duration` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `machine_id` (`machine_id`),
  CONSTRAINT `maintenance_schedules_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_schedules`
--

LOCK TABLES `maintenance_schedules` WRITE;
/*!40000 ALTER TABLE `maintenance_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenance_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mapeamento_machines_maquinas`
--

DROP TABLE IF EXISTS `mapeamento_machines_maquinas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mapeamento_machines_maquinas` (
  `machine_id` int NOT NULL,
  `maquina_id` int NOT NULL,
  PRIMARY KEY (`machine_id`),
  KEY `maquina_id` (`maquina_id`),
  CONSTRAINT `mapeamento_machines_maquinas_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`),
  CONSTRAINT `mapeamento_machines_maquinas_ibfk_2` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mapeamento_machines_maquinas`
--

LOCK TABLES `mapeamento_machines_maquinas` WRITE;
/*!40000 ALTER TABLE `mapeamento_machines_maquinas` DISABLE KEYS */;
/*!40000 ALTER TABLE `mapeamento_machines_maquinas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maquinas`
--

DROP TABLE IF EXISTS `maquinas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maquinas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `fabricante` varchar(100) DEFAULT NULL,
  `setor_id` int DEFAULT NULL,
  `machine_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `setor_id` (`setor_id`),
  CONSTRAINT `maquinas_ibfk_1` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maquinas`
--

LOCK TABLES `maquinas` WRITE;
/*!40000 ALTER TABLE `maquinas` DISABLE KEYS */;
INSERT INTO `maquinas` VALUES (1,'MÁQUINA DE PAPEL','FILIPERSON','FILIPERSON',NULL,NULL),(2,'CORTADEIRA','CORTADEIRA','JANGERBERG',NULL,NULL),(3,'IMPRESSORA OFF-SET','200','ROLAND',NULL,NULL);
/*!40000 ALTER TABLE `maquinas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `order_id` int DEFAULT NULL COMMENT 'Pode referenciar service_orders ou chamados',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (37,1,'Novo Chamado #18','Um novo chamado foi aberto no sistema',18,0,'2025-05-22 17:20:24'),(38,2,'Novo Chamado #18','Um novo chamado foi aberto no sistema',18,0,'2025-05-22 17:20:24'),(39,7,'Novo Chamado #18','Um novo chamado foi aberto no sistema',18,0,'2025-05-22 17:20:24'),(40,1,'Novo Chamado #19','Um novo chamado foi aberto no sistema',19,0,'2025-05-23 11:02:27'),(41,2,'Novo Chamado #19','Um novo chamado foi aberto no sistema',19,0,'2025-05-23 11:02:27'),(42,7,'Novo Chamado #19','Um novo chamado foi aberto no sistema',19,0,'2025-05-23 11:02:27'),(43,1,'Novo Chamado #20','Um novo chamado foi aberto no sistema',20,0,'2025-05-29 18:55:38'),(44,2,'Novo Chamado #20','Um novo chamado foi aberto no sistema',20,0,'2025-05-29 18:55:39'),(45,7,'Novo Chamado #20','Um novo chamado foi aberto no sistema',20,0,'2025-05-29 18:55:39');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_attachments`
--

DROP TABLE IF EXISTS `order_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_attachments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_attachments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `service_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_attachments`
--

LOCK TABLES `order_attachments` WRITE;
/*!40000 ALTER TABLE `order_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_comments`
--

DROP TABLE IF EXISTS `order_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `order_comments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `service_orders` (`id`),
  CONSTRAINT `order_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_comments`
--

LOCK TABLES `order_comments` WRITE;
/*!40000 ALTER TABLE `order_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_history`
--

DROP TABLE IF EXISTS `order_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `order_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `service_orders` (`id`),
  CONSTRAINT `order_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_history`
--

LOCK TABLES `order_history` WRITE;
/*!40000 ALTER TABLE `order_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_orders`
--

DROP TABLE IF EXISTS `service_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `machine_id` int NOT NULL,
  `type` enum('preventiva','corretiva','preditiva') NOT NULL,
  `work_type` varchar(20) NOT NULL,
  `description` text,
  `status` enum('aberta','em_andamento','concluida') DEFAULT 'aberta',
  `scheduled_at` datetime DEFAULT NULL,
  `planned_end_at` datetime DEFAULT NULL COMMENT 'Data/Hora prevista para término da OS',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `requester_name` varchar(255) DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `priority` enum('baixa','media','alta') DEFAULT 'media',
  PRIMARY KEY (`id`),
  KEY `machine_id` (`machine_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `service_orders_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`),
  CONSTRAINT `service_orders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_orders`
--

LOCK TABLES `service_orders` WRITE;
/*!40000 ALTER TABLE `service_orders` DISABLE KEYS */;
INSERT INTO `service_orders` VALUES (4,1,'preventiva','elétrico','teste','aberta',NULL,NULL,'2025-05-22 16:13:19','2025-05-22',NULL,7,'Wesley','RH','baixa'),(5,1,'preventiva','mecânico','teste','concluida',NULL,NULL,'2025-05-22 17:42:20','2025-05-22',NULL,7,'Wesley','RH','alta'),(6,1,'preventiva','elétrico','teste','concluida',NULL,NULL,'2025-05-22 17:49:01','2025-05-22',NULL,7,'Wesley','14','baixa'),(7,1,'corretiva','outros','teste','concluida',NULL,NULL,'2025-05-22 17:49:34','2025-05-15',NULL,7,'Wesley','17','baixa'),(8,2,'preventiva','elétrico','Teste gantt','em_andamento',NULL,NULL,'2025-05-29 15:53:11','2025-05-29',NULL,7,'Wesley','12','media');
/*!40000 ALTER TABLE `service_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `setores`
--

DROP TABLE IF EXISTS `setores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `setores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `setores`
--

LOCK TABLES `setores` WRITE;
/*!40000 ALTER TABLE `setores` DISABLE KEYS */;
INSERT INTO `setores` VALUES (6,'Outro',NULL),(10,'RH','Recursos Humanos'),(11,'TI','Tecnologia da Informação'),(12,'Produção','Setor de produção industrial'),(13,'Manutenção','Setor de manutenção de máquinas'),(14,'Administrativo','Setor administrativo'),(15,'Qualidade','Controle de qualidade'),(16,'CutSize','Producao'),(17,'Artefatos','Artefatos'),(18,'Sala de escolha','Sala de escolha'),(19,'Financeiro','Financeiro'),(20,'Máquina de papel','Maquina de papel'),(21,'Diretoria','Diretoria'),(22,'Comercial','Comercial');
/*!40000 ALTER TABLE `setores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','technician','requester') DEFAULT 'technician',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Wesley','$2y$10$QwwuMhL6eOcfQmtW1JD3YOAqeMobWNuDnEg7rjEokuvp/dpv.gvHu','awesley.jazzblues@gmail.com',NULL,'admin','2025-02-16 11:13:36'),(2,'Evandro','$1$9HeytqB5$jkEXwTYuRaKxWiKCK1PFn1','evandro.paes@filiperson.com.br',NULL,'admin','2025-02-16 11:17:09'),(7,'Guilherme','$2y$10$T2nF.gDqDem554OXyNvDmOkxyhxWku6YU.JKzFXtqPwl9iEY8YsQy','filiperson@filiperson','(21) 965361971','technician','2025-04-15 17:02:33'),(8,'Cristina','$2y$10$oJ3yXVm62JY6fktCDweXpuEUERAd94dJLw2nzG.4JWBRpQ2annefC','cristina.ribeiro@filiperson.com.br','21992099041','requester','2025-05-29 15:41:24');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-29 17:14:01
