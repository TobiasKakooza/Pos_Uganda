-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: ugandan_pos_new
-- ------------------------------------------------------
-- Server version	9.3.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` int DEFAULT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'inventory_adjust','inventory',NULL,'Adjusted stock by ',NULL,'2025-12-17 14:23:11'),(2,1,'inventory_adjust','inventory',NULL,'Adjusted stock by ',NULL,'2025-12-17 14:23:25'),(3,1,'inventory_adjust','inventory',NULL,'Adjusted stock by ',NULL,'2025-12-17 14:23:35'),(4,1,'inventory_adjust','inventory',NULL,'Adjusted stock by ',NULL,'2025-12-17 14:23:45'),(5,1,'inventory_adjust','inventory',NULL,'Adjusted stock by ',NULL,'2025-12-17 14:23:54'),(6,1,'inventory_adjust','inventory',NULL,'Adjusted stock by ',NULL,'2025-12-19 14:04:10'),(7,1,'inventory_adjust','inventory',NULL,'Adjusted stock by ',NULL,'2025-12-19 14:04:24'),(8,1,'inventory_adjust','inventory',NULL,'Adjusted stock by ',NULL,'2025-12-19 14:04:34');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ap_bills`
--

DROP TABLE IF EXISTS `ap_bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ap_bills` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int NOT NULL,
  `receipt_id` int DEFAULT NULL,
  `po_id` int DEFAULT NULL,
  `bill_no` varchar(50) DEFAULT NULL,
  `bill_date` date NOT NULL,
  `due_date` date NOT NULL,
  `currency_code` char(3) DEFAULT 'UGX',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('open','partially_paid','paid','void') DEFAULT 'open',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `receipt_id` (`receipt_id`),
  KEY `po_id` (`po_id`),
  KEY `idx_bills_supplier_status` (`supplier_id`,`status`,`due_date`),
  CONSTRAINT `ap_bills_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `ap_bills_ibfk_2` FOREIGN KEY (`receipt_id`) REFERENCES `receipts` (`id`),
  CONSTRAINT `ap_bills_ibfk_3` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ap_bills`
--

LOCK TABLES `ap_bills` WRITE;
/*!40000 ALTER TABLE `ap_bills` DISABLE KEYS */;
/*!40000 ALTER TABLE `ap_bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ap_credits`
--

DROP TABLE IF EXISTS `ap_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ap_credits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int NOT NULL,
  `bill_id` int DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `bill_id` (`bill_id`),
  CONSTRAINT `ap_credits_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `ap_credits_ibfk_2` FOREIGN KEY (`bill_id`) REFERENCES `ap_bills` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ap_credits`
--

LOCK TABLES `ap_credits` WRITE;
/*!40000 ALTER TABLE `ap_credits` DISABLE KEYS */;
/*!40000 ALTER TABLE `ap_credits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ap_ledger`
--

DROP TABLE IF EXISTS `ap_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ap_ledger` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int NOT NULL,
  `txn_type` enum('opening','bill','payment','credit','adjustment') NOT NULL,
  `txn_id` int DEFAULT NULL,
  `txn_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `debit` decimal(12,2) DEFAULT '0.00',
  `credit` decimal(12,2) DEFAULT '0.00',
  `balance` decimal(12,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_apledger_supplier_date` (`supplier_id`,`txn_date`,`id`),
  CONSTRAINT `ap_ledger_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ap_ledger`
--

LOCK TABLES `ap_ledger` WRITE;
/*!40000 ALTER TABLE `ap_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `ap_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ap_payments`
--

DROP TABLE IF EXISTS `ap_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ap_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int NOT NULL,
  `bill_id` int DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `ref_no` varchar(50) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_payments_supplier` (`supplier_id`,`paid_at`),
  CONSTRAINT `ap_payments_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `ap_payments_ibfk_2` FOREIGN KEY (`bill_id`) REFERENCES `ap_bills` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ap_payments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ap_payments`
--

LOCK TABLES `ap_payments` WRITE;
/*!40000 ALTER TABLE `ap_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `ap_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_movements`
--

DROP TABLE IF EXISTS `cash_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_movements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `type` enum('in','out') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_movements`
--

LOCK TABLES `cash_movements` WRITE;
/*!40000 ALTER TABLE `cash_movements` DISABLE KEYS */;
INSERT INTO `cash_movements` VALUES (1,NULL,'in',100000.00,'The Money Given to me in the morning','2025-08-09 07:53:09'),(2,NULL,'in',50000.00,NULL,'2025-08-11 08:30:28');
/*!40000 ALTER TABLE `cash_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Electronics',NULL,'2025-07-30 07:07:01'),(2,'Groceries',NULL,'2025-07-30 07:07:01'),(3,'Furniture',NULL,'2025-07-30 07:07:01'),(4,'Grains','Edibles','2025-08-04 13:57:21'),(5,'Plastics','Mainly Plastics','2025-08-05 07:33:34'),(6,'Kitchen ware','Kitchen stuffs Only','2025-08-05 07:41:00'),(7,'Toilet','All stuff for Toilt sanitation','2025-08-05 08:59:09');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_payments`
--

DROP TABLE IF EXISTS `credit_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `sale_id` int DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `credit_payments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_payments`
--

LOCK TABLES `credit_payments` WRITE;
/*!40000 ALTER TABLE `credit_payments` DISABLE KEYS */;
INSERT INTO `credit_payments` VALUES (1,2,NULL,10000.00,'cash','Paid the 10000',NULL,'2025-08-09 09:01:13');
/*!40000 ALTER TABLE `credit_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `loyalty_points` int DEFAULT '0',
  `outstanding_balance` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'John Doe','john@example.com','0701234567',10,0.00,'2025-07-29 13:04:00'),(2,'Jane Shopper','jane@example.com','0709876543',50,10000.00,'2025-07-29 13:04:00');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `draft_sales`
--

DROP TABLE IF EXISTS `draft_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `draft_sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `payload` json NOT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `draft_sales`
--

LOCK TABLES `draft_sales` WRITE;
/*!40000 ALTER TABLE `draft_sales` DISABLE KEYS */;
INSERT INTO `draft_sales` VALUES (1,NULL,'{\"items\": [{\"name\": \"Office Desk (Stock: 2)\", \"quantity\": 1, \"product_id\": 4, \"unit_price\": 350000}, {\"name\": \"Electric Cable (Stock: 5)\", \"quantity\": 1, \"product_id\": 7, \"unit_price\": 7000}], \"comment\": \"\", \"tax_rate\": 0, \"discount_type\": null, \"discount_value\": 0}','open','2025-08-09 07:37:19'),(2,NULL,'{\"items\": [{\"name\": \"Electric Cable (Stock: 5)\", \"quantity\": 1, \"product_id\": 7, \"unit_price\": 7000}, {\"name\": \"LED TV 42\\\" (Stock: 0)\", \"quantity\": 1, \"product_id\": 2, \"unit_price\": 800000}], \"comment\": \"\", \"tax_rate\": 0, \"discount_type\": null, \"discount_value\": 0}','open','2025-08-09 07:45:15'),(3,NULL,'{\"items\": [{\"name\": \"Rice 5kg (Stock: 96)\", \"quantity\": 1, \"product_id\": 3, \"unit_price\": 20000}], \"comment\": \"\", \"tax_rate\": 0, \"discount_type\": null, \"discount_value\": 0}','open','2025-08-11 08:28:59'),(4,NULL,'{\"items\": [{\"name\": \"LED TV 42\\\" (Stock: 0)\", \"quantity\": 1, \"product_id\": 2, \"unit_price\": 800000}, {\"name\": \"Office Desk (Stock: 2)\", \"quantity\": 1, \"product_id\": 4, \"unit_price\": 350000}], \"comment\": \"\", \"tax_rate\": 0, \"discount_type\": null, \"discount_value\": 0}','open','2025-08-14 13:24:52');
/*!40000 ALTER TABLE `draft_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eod_reports`
--

DROP TABLE IF EXISTS `eod_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eod_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_date` date NOT NULL,
  `opening_balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `sales_count` int NOT NULL DEFAULT '0',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_sales` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cash_from_sales_net` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cash_in` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cash_out` decimal(12,2) NOT NULL DEFAULT '0.00',
  `credit_payments` decimal(12,2) NOT NULL DEFAULT '0.00',
  `expected_drawer` decimal(12,2) NOT NULL DEFAULT '0.00',
  `closing_balance` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_date` (`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eod_reports`
--

LOCK TABLES `eod_reports` WRITE;
/*!40000 ALTER TABLE `eod_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `eod_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('fixed','variable') DEFAULT 'variable',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES (1,'Salaries','fixed','2025-12-18 07:41:39'),(2,'Rent','fixed','2025-12-18 07:41:39'),(3,'Utilities','fixed','2025-12-18 07:41:39'),(4,'Internet','fixed','2025-12-18 07:41:39'),(5,'Transport','variable','2025-12-18 07:41:39'),(6,'Maintenance','variable','2025-12-18 07:41:39'),(7,'Marketing','variable','2025-12-18 07:41:39'),(8,'Miscellaneous','variable','2025-12-18 07:41:39');
/*!40000 ALTER TABLE `expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expense_date` date NOT NULL,
  `category_id` int NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank','mobile','card') DEFAULT 'cash',
  `reference` varchar(100) DEFAULT NULL,
  `description` text,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `expense_date` (`expense_date`),
  KEY `category_id` (`category_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (2,'2025-12-18',1,600000.00,'cash','Salaries','Paid Bashir and Camila',1,'2025-12-18 08:51:26'),(3,'2025-12-18',2,250000.00,'cash','Rentals','Paid the Rental amount today for December',1,'2025-12-18 09:03:20'),(4,'2025-12-18',4,130000.00,'mobile','MTN WAKANET','Monthly Speed',1,'2025-12-18 09:25:56'),(5,'2025-12-18',5,10000.00,'cash','Today','Daily Transport',1,'2025-12-18 10:03:51'),(6,'2025-12-18',5,5000.00,'cash','Today','Daily Transport',1,'2025-12-18 14:30:07'),(7,'2025-12-19',4,20000.00,'cash','Airtel Internet','Paid Today',1,'2025-12-19 13:47:07');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventories`
--

DROP TABLE IF EXISTS `inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `stock_after` int DEFAULT NULL,
  `type` enum('in','out') DEFAULT 'in',
  `note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `source_type` enum('po_receipt','sale','adjustment','return') DEFAULT NULL,
  `source_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_inv_source` (`source_type`,`source_id`),
  KEY `idx_inventories_product` (`product_id`),
  CONSTRAINT `inventories_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventories`
--

LOCK TABLES `inventories` WRITE;
/*!40000 ALTER TABLE `inventories` DISABLE KEYS */;
INSERT INTO `inventories` VALUES (1,1,10,NULL,'in','Initial stock - Washing Machine','2025-07-29 13:04:00','2025-08-06 09:28:01',NULL,NULL),(2,2,8,NULL,'in','Initial stock - LED TV','2025-07-29 13:04:00','2025-08-06 09:28:01',NULL,NULL),(3,3,100,NULL,'in','Initial stock - Rice','2025-07-29 13:04:00','2025-08-06 09:28:01',NULL,NULL),(4,4,5,NULL,'in','Initial stock - Office Desks','2025-07-29 13:04:00','2025-08-06 09:28:01',NULL,NULL),(5,2,23,NULL,'in','Added now','2025-07-29 14:49:27','2025-08-06 09:28:01',NULL,NULL),(6,3,2,NULL,'out','Sale ID 3','2025-08-06 06:38:36','2025-08-06 09:28:01',NULL,NULL),(9,1,1,NULL,'out','Sale ID 6','2025-08-06 07:05:00','2025-08-06 09:28:01',NULL,NULL),(10,1,1,NULL,'out','Sale ID 7','2025-08-06 07:05:34','2025-08-06 09:28:01',NULL,NULL),(11,2,1,NULL,'out','Sale ID 8','2025-08-06 07:10:26','2025-08-06 09:28:01',NULL,NULL),(12,2,1,NULL,'out','Sale ID 9','2025-08-06 07:57:59','2025-08-06 09:28:01',NULL,NULL),(13,2,1,28,'out','Sale ID 10','2025-08-06 08:28:39','2025-08-06 09:28:01',NULL,NULL),(14,3,1,97,'out','Sale ID 11','2025-08-06 08:29:38','2025-08-06 09:28:01',NULL,NULL),(15,2,1,27,'out','Sale ID 11','2025-08-06 08:29:38','2025-08-06 09:28:01',NULL,NULL),(16,2,27,0,'out','Sale ID 12','2025-08-06 08:37:19','2025-08-06 09:28:01',NULL,NULL),(17,7,12,11,'in','Added Now','2025-08-06 09:16:05','2025-08-06 09:59:12',NULL,NULL),(18,2,10,10,'in','Added Some Tvs','2025-08-06 12:02:15','2025-08-06 12:02:15',NULL,NULL),(19,4,1,4,'out','Sale ID 14','2025-08-06 14:22:45','2025-08-06 14:22:45',NULL,NULL),(20,2,1,9,'out','Sale ID 15','2025-08-06 14:23:03','2025-08-06 14:23:03',NULL,NULL),(21,2,1,8,'out','Sale ID 16','2025-08-06 14:37:34','2025-08-06 14:37:34',NULL,NULL),(22,2,1,7,'out','Sale ID 17','2025-08-06 14:44:45','2025-08-06 14:44:45',NULL,NULL),(23,2,1,6,'out','Sale ID 18','2025-08-06 14:45:13','2025-08-06 14:45:13',NULL,NULL),(25,2,1,5,'out','Sale ID 20','2025-08-06 15:04:33','2025-08-06 15:04:33',NULL,NULL),(26,4,2,2,'out','Sale ID 20','2025-08-06 15:04:33','2025-08-06 15:04:33',NULL,NULL),(27,7,2,10,'out','Sale ID 21','2025-08-08 05:55:00','2025-08-08 05:55:00',NULL,NULL),(28,7,2,8,'out','Sale ID 22','2025-08-08 05:55:33','2025-08-08 05:55:33',NULL,NULL),(29,7,1,7,'out','Sale ID 23','2025-08-08 07:01:28','2025-08-08 07:01:28',NULL,NULL),(30,2,1,4,'out','Sale ID 24','2025-08-08 08:11:54','2025-08-08 08:11:54',NULL,NULL),(31,2,1,3,'out','Sale ID 25','2025-08-08 08:28:52','2025-08-08 08:28:52',NULL,NULL),(32,2,1,2,'out','Sale ID 26','2025-08-08 08:29:44','2025-08-08 08:29:44',NULL,NULL),(33,7,1,6,'out','Sale ID 27','2025-08-08 08:34:57','2025-08-08 08:34:57',NULL,NULL),(34,7,1,5,'out','Sale ID 28','2025-08-08 08:38:18','2025-08-08 08:38:18',NULL,NULL),(35,7,1,4,'out','Sale ID 29','2025-08-08 09:25:54','2025-08-08 09:25:54',NULL,NULL),(36,7,1,3,'out','Sale ID 30','2025-08-08 10:18:38','2025-08-08 10:18:38',NULL,NULL),(37,7,1,2,'out','Sale ID 31','2025-08-08 10:20:02','2025-08-08 10:20:02',NULL,NULL),(38,7,10,12,'in','Cables Added','2025-08-08 10:25:18','2025-08-08 10:25:18',NULL,NULL),(39,7,1,11,'out','Sale ID 32','2025-08-08 10:26:53','2025-08-08 10:26:53',NULL,NULL),(40,2,2,0,'out','Sale ID 33','2025-08-08 12:58:50','2025-08-08 12:58:50',NULL,NULL),(41,7,1,10,'out','Sale ID 34','2025-08-08 12:59:51','2025-08-08 12:59:51',NULL,NULL),(42,7,1,9,'out','Sale ID 35','2025-08-08 13:22:10','2025-08-08 13:22:10',NULL,NULL),(43,7,1,8,'out','Sale ID 36','2025-08-08 13:23:39','2025-08-08 13:23:39',NULL,NULL),(44,7,2,6,'out','Sale ID 37','2025-08-08 13:32:36','2025-08-08 13:32:36',NULL,NULL),(45,7,1,5,'out','Sale ID 38','2025-08-09 06:54:06','2025-08-09 06:54:06',NULL,NULL),(46,7,5,0,'out','Sale ID 39','2025-08-11 08:23:32','2025-08-11 08:23:32',NULL,NULL),(47,3,1,96,'out','Sale ID 39','2025-08-11 08:23:32','2025-08-11 08:23:32',NULL,NULL),(48,7,10,10,'in','Added 10','2025-08-11 08:34:07','2025-08-11 08:34:07',NULL,NULL),(49,7,3,13,'in','','2025-08-11 08:34:45','2025-08-11 08:34:45',NULL,NULL),(50,2,10,10,'in','','2025-08-14 13:19:22','2025-08-14 13:19:22',NULL,NULL),(51,2,2,12,'in','','2025-08-14 13:19:45','2025-08-14 13:19:45',NULL,NULL),(52,2,12,0,'out','Sale ID 40','2025-08-14 13:23:09','2025-08-14 13:23:09',NULL,NULL),(53,7,1,12,'out','Sale ID 41','2025-09-02 14:22:09','2025-09-02 14:22:09',NULL,NULL),(54,3,5,91,'out','Sale ID 42','2025-09-03 13:35:52','2025-09-03 13:35:52','sale',42),(55,4,2,0,'out','Sale ID 42','2025-09-03 13:35:52','2025-09-03 13:35:52','sale',42),(56,7,3,9,'out','Sale ID 42','2025-09-03 13:35:52','2025-09-03 13:35:52','sale',42),(57,7,1,8,'out','Sale ID 43','2025-09-09 07:48:07','2025-09-09 07:48:07','sale',43),(58,7,8,0,'out','Sale ID 44','2025-09-19 09:26:51','2025-09-19 09:26:51','sale',44),(59,1,3,11,'in','','2025-10-08 06:15:36','2025-10-08 06:15:36',NULL,NULL),(60,1,1,10,'out','Sale ID 45','2025-10-08 06:17:24','2025-10-08 06:17:24','sale',45),(61,2,20,20,'in','New Stock','2025-12-17 06:27:08','2025-12-17 06:27:08',NULL,NULL),(62,2,2,18,'out','Sale ID 46','2025-12-17 06:27:51','2025-12-17 06:27:51','sale',46),(63,3,2,89,'out','Sale ID 46','2025-12-17 06:27:51','2025-12-17 06:27:51','sale',46),(64,2,1,17,'out','Sale ID 47','2025-12-17 12:08:31','2025-12-17 12:08:31','sale',47),(65,1,1,9,'out','Sale ID 48','2025-12-17 13:51:10','2025-12-17 13:51:10','sale',48),(66,4,10,10,'in','','2025-12-17 14:23:11','2025-12-17 14:23:11',NULL,NULL),(67,7,20,20,'in','','2025-12-17 14:23:25','2025-12-17 14:23:25',NULL,NULL),(68,10,20,20,'in','','2025-12-17 14:23:35','2025-12-17 14:23:35',NULL,NULL),(69,6,13,13,'in','','2025-12-17 14:23:45','2025-12-17 14:23:45',NULL,NULL),(70,9,18,18,'in','','2025-12-17 14:23:54','2025-12-17 14:23:54',NULL,NULL),(71,4,1,9,'out','Sale ID 49','2025-12-17 14:26:24','2025-12-17 14:26:24','sale',49),(72,4,1,8,'out','Sale ID 50','2025-12-17 14:33:11','2025-12-17 14:33:11','sale',50),(73,2,1,16,'out','Sale ID 51','2025-12-17 14:33:26','2025-12-17 14:33:26','sale',51),(74,2,1,15,'out','Sale ID 52','2025-12-17 14:38:42','2025-12-17 14:38:42','sale',52),(75,4,1,7,'out','Sale ID 53','2025-12-17 14:38:51','2025-12-17 14:38:51','sale',53),(76,4,1,6,'out','Sale ID 54','2025-12-17 14:39:15','2025-12-17 14:39:15','sale',54),(77,4,1,5,'out','Sale ID 55','2025-12-17 14:42:56','2025-12-17 14:42:56','sale',55),(78,4,1,4,'out','Sale ID 56','2025-12-17 14:43:07','2025-12-17 14:43:07','sale',56),(79,4,1,3,'out','Sale ID 57','2025-12-17 14:50:38','2025-12-17 14:50:38','sale',57),(80,4,1,2,'out','Sale ID 58','2025-12-17 14:54:06','2025-12-17 14:54:06','sale',58),(81,2,1,14,'out','Sale ID 59','2025-12-17 14:57:50','2025-12-17 14:57:50','sale',59),(82,16,22,NULL,'in','Initial stock','2025-12-18 06:59:01','2025-12-18 06:59:01',NULL,NULL),(84,16,1,21,'out','Sale ID 61','2025-12-18 07:14:11','2025-12-18 07:14:11','sale',61),(85,16,1,20,'out','Sale ID 62','2025-12-18 10:22:38','2025-12-18 10:22:38','sale',62),(86,16,1,19,'out','Sale ID 63','2025-12-18 14:25:55','2025-12-18 14:25:55','sale',63),(87,16,2,17,'out','Sale ID 64','2025-12-19 09:29:09','2025-12-19 09:29:09','sale',64),(89,16,2,15,'out','Sale ID 66','2025-12-19 13:56:52','2025-12-19 13:56:52','sale',66),(90,11,10,10,'in','','2025-12-19 14:04:10','2025-12-19 14:04:10',NULL,NULL),(91,14,10,10,'in','','2025-12-19 14:04:24','2025-12-19 14:04:24',NULL,NULL),(92,11,2,12,'in','','2025-12-19 14:04:34','2025-12-19 14:04:34',NULL,NULL);
/*!40000 ALTER TABLE `inventories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int DEFAULT NULL,
  `message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) DEFAULT '0',
  `user_id` int DEFAULT NULL COMMENT 'Who triggered this',
  `target_user_id` int DEFAULT NULL,
  `target_role` enum('admin','cashier','inventory') DEFAULT 'admin',
  `type` varchar(50) DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `idx_notif_read_role` (`is_read`,`target_role`),
  KEY `fk_notif_user` (`user_id`),
  KEY `idx_notifications_target_user` (`target_user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,NULL,'TEST notification','2025-12-17 14:57:07',0,2,NULL,'admin','sale','/test'),(2,NULL,'Cashier completed sale #59','2025-12-17 14:57:50',1,6,NULL,'admin','sale','/POS_UG/views/reports/sales.php?id=59'),(3,NULL,'Cashier completed sale #61','2025-12-18 07:14:11',0,1,NULL,'admin','sale','/POS_UG/views/reports/sales.php?id=61'),(4,NULL,'Cashier completed sale #62','2025-12-18 10:22:38',1,1,NULL,'admin','sale','/POS_UG/views/reports/sales.php?id=62'),(5,NULL,'Cashier completed sale #63','2025-12-18 14:25:55',1,6,NULL,'admin','sale','/POS_UG/views/reports/sales.php?id=63'),(6,NULL,'Cashier completed sale #64','2025-12-19 09:29:09',1,6,NULL,'admin','sale','/POS_UG/views/reports/sales.php?id=64'),(7,NULL,'Cashier completed sale #66','2025-12-19 13:56:52',1,1,NULL,'admin','sale','/POS_UG/views/reports/sales.php?id=66');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_terms`
--

DROP TABLE IF EXISTS `payment_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_terms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(40) NOT NULL,
  `days` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_terms`
--

LOCK TABLES `payment_terms` WRITE;
/*!40000 ALTER TABLE `payment_terms` DISABLE KEYS */;
INSERT INTO `payment_terms` VALUES (1,'Net 30',30),(2,'Net 45',45);
/*!40000 ALTER TABLE `payment_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'sales_access','Access POS / Sales','Sales'),(2,'sales_refund','Refund Sales','Sales'),(3,'sales_discount','Apply Discounts','Sales'),(4,'inventory_view','View Inventory','Inventory'),(5,'inventory_adjust','Adjust Stock','Inventory'),(6,'products_manage','Manage Products','Products'),(7,'categories_manage','Manage Categories','Products'),(8,'reports_view','View Reports','Reports'),(9,'reports_eod','Close End of Day','Reports'),(10,'users_manage','Manage Users','Users'),(11,'suppliers_manage','Manage Suppliers','Suppliers'),(12,'expenses_manage','Manage Expenses','Expenses');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `tax_rate` decimal(5,2) DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `stock_alert_threshold` int DEFAULT '2',
  `unit_id` int DEFAULT NULL,
  `avg_cost` decimal(12,4) DEFAULT '0.0000',
  `last_cost` decimal(12,4) DEFAULT '0.0000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `category_id` (`category_id`),
  KEY `fk_unit` (`unit_id`),
  CONSTRAINT `fk_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Washing Machine','SKU001','BAR001',570000.00,18.00,1,'2025-07-29 13:04:00',2,NULL,399000.0000,399000.0000),(2,'LED TV 42\"','SKU002','BAR002',760000.00,18.00,1,'2025-07-29 13:04:00',2,NULL,532000.0000,532000.0000),(3,'Rice 5kg','SKU003','BAR003',19000.00,0.00,2,'2025-07-29 13:04:00',2,NULL,13300.0000,13300.0000),(4,'Office Desk','SKU004','BAR004',332500.00,18.00,3,'2025-07-29 13:04:00',2,NULL,232750.0000,232750.0000),(6,'Necklace','SKU005','22133',2945.00,0.00,2,'2025-08-04 08:52:39',2,NULL,2061.5000,2061.5000),(7,'Electric Cable','SKU006','8483938',6650.00,2.00,1,'2025-08-04 09:05:44',2,NULL,4655.0000,4655.0000),(9,'Sofa 1st Class','SKU007','22133242',2945000.00,12.00,3,'2025-08-04 13:29:36',2,NULL,2061500.0000,2061500.0000),(10,'LED TV 65\"','SKU008','8483938354',1520000.00,18.00,1,'2025-08-06 13:41:09',2,10,1064000.0000,1064000.0000),(11,'Hisense Home Theatre','SKU0010','4434567566',646000.00,18.00,1,'2025-12-18 06:31:33',2,7,452200.0000,452200.0000),(14,'Samsung Home Theatre','SKU0011','38994002',741000.00,18.00,1,'2025-12-18 06:43:04',2,7,518700.0000,518700.0000),(16,'MT Home Theatre','SKU0013','3232334423',655500.00,18.00,1,'2025-12-18 06:59:01',2,7,458850.0000,458850.0000);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_id` int NOT NULL,
  `product_id` int NOT NULL,
  `qty_ordered` int NOT NULL,
  `qty_received` int NOT NULL DEFAULT '0',
  `unit_cost` decimal(12,2) NOT NULL,
  `tax_rate` decimal(6,3) DEFAULT '0.000',
  `discount` decimal(12,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `idx_poi_po` (`po_id`),
  CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int DEFAULT NULL,
  `status` enum('pending','received') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reference_no` varchar(50) DEFAULT NULL,
  `expected_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `currency_code` char(3) DEFAULT 'UGX',
  `terms_id` int DEFAULT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_po_terms` (`terms_id`),
  KEY `fk_po_created_by` (`created_by`),
  KEY `fk_po_updated_by` (`updated_by`),
  KEY `idx_po_supplier_status` (`supplier_id`,`status`,`created_at`),
  CONSTRAINT `fk_po_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_po_terms` FOREIGN KEY (`terms_id`) REFERENCES `payment_terms` (`id`),
  CONSTRAINT `fk_po_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`),
  CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,1,'received',12000000.00,'2025-07-29 13:04:00',NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,'2025-08-13 07:31:09'),(2,2,'pending',500000.00,'2025-07-29 13:04:00',NULL,NULL,NULL,'UGX',NULL,NULL,NULL,NULL,'2025-08-13 07:31:09');
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receipt_items`
--

DROP TABLE IF EXISTS `receipt_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `receipt_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `receipt_id` int NOT NULL,
  `product_id` int NOT NULL,
  `qty` int NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL,
  `batch_no` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `idx_ri_receipt` (`receipt_id`),
  CONSTRAINT `receipt_items_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `receipts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `receipt_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipt_items`
--

LOCK TABLES `receipt_items` WRITE;
/*!40000 ALTER TABLE `receipt_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `receipt_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receipts`
--

DROP TABLE IF EXISTS `receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `receipts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_id` int DEFAULT NULL,
  `supplier_id` int NOT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `po_id` (`po_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `receipts_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `receipts_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `receipts_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipts`
--

LOCK TABLES `receipts` WRITE;
/*!40000 ALTER TABLE `receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1),(2,1),(1,2),(1,3),(2,3),(1,4),(3,4),(1,5),(3,5),(1,6),(3,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Admin'),(2,'Cashier'),(3,'Inventory Manager');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sale_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `cogs_unit` decimal(12,4) DEFAULT NULL,
  `cogs_total` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
INSERT INTO `sale_items` VALUES (1,1,1,2,600000.00,0.0000,0.00),(2,2,2,1,800000.00,0.0000,0.00),(3,2,3,3,20000.00,0.0000,0.00),(4,3,3,2,20000.00,0.0000,0.00),(5,4,1,2,600000.00,0.0000,0.00),(6,5,1,1,600000.00,0.0000,0.00),(7,6,1,1,600000.00,0.0000,0.00),(8,7,1,1,600000.00,0.0000,0.00),(9,8,2,1,800000.00,0.0000,0.00),(10,9,2,1,800000.00,0.0000,0.00),(11,10,2,1,800000.00,0.0000,0.00),(12,11,3,1,20000.00,0.0000,0.00),(13,11,2,1,800000.00,0.0000,0.00),(14,12,2,27,800000.00,0.0000,0.00),(15,14,4,1,350000.00,0.0000,0.00),(16,15,2,1,800000.00,0.0000,0.00),(17,16,2,1,800000.00,0.0000,0.00),(18,17,2,1,800000.00,0.0000,0.00),(19,18,2,1,800000.00,0.0000,0.00),(21,20,2,1,800000.00,0.0000,0.00),(22,20,4,2,350000.00,0.0000,0.00),(23,21,7,2,7000.00,0.0000,0.00),(24,22,7,2,7000.00,0.0000,0.00),(25,23,7,1,7000.00,0.0000,0.00),(26,24,2,1,800000.00,0.0000,0.00),(27,25,2,1,800000.00,0.0000,0.00),(28,26,2,1,800000.00,0.0000,0.00),(29,27,7,1,7000.00,0.0000,0.00),(30,28,7,1,7000.00,0.0000,0.00),(31,29,7,1,7000.00,0.0000,0.00),(32,30,7,1,7000.00,0.0000,0.00),(33,31,7,1,7000.00,0.0000,0.00),(34,32,7,1,7000.00,0.0000,0.00),(35,33,2,2,800000.00,0.0000,0.00),(36,34,7,1,7000.00,0.0000,0.00),(37,35,7,1,7000.00,0.0000,0.00),(38,36,7,1,7000.00,0.0000,0.00),(39,37,7,2,7000.00,0.0000,0.00),(40,38,7,1,7000.00,0.0000,0.00),(41,39,7,5,7000.00,0.0000,0.00),(42,39,3,1,20000.00,0.0000,0.00),(43,40,2,12,800000.00,0.0000,0.00),(44,41,7,1,7000.00,0.0000,0.00),(45,42,3,5,20000.00,0.0000,0.00),(46,42,4,2,350000.00,0.0000,0.00),(47,42,7,3,7000.00,0.0000,0.00),(48,43,7,1,7000.00,0.0000,0.00),(49,44,7,8,7000.00,0.0000,0.00),(50,45,1,1,600000.00,0.0000,0.00),(51,46,2,2,800000.00,0.0000,0.00),(52,46,3,2,20000.00,0.0000,0.00),(53,47,2,1,800000.00,0.0000,0.00),(54,48,1,1,600000.00,0.0000,0.00),(55,49,4,1,350000.00,0.0000,0.00),(56,50,4,1,350000.00,0.0000,0.00),(57,51,2,1,800000.00,0.0000,0.00),(58,52,2,1,800000.00,0.0000,0.00),(59,53,4,1,350000.00,0.0000,0.00),(60,54,4,1,350000.00,0.0000,0.00),(61,55,4,1,350000.00,0.0000,0.00),(62,56,4,1,350000.00,0.0000,0.00),(63,57,4,1,350000.00,0.0000,0.00),(64,58,4,1,350000.00,0.0000,0.00),(65,59,2,1,800000.00,0.0000,0.00),(67,61,16,1,690000.00,580000.0000,580000.00),(68,62,16,1,655500.00,458850.0000,458850.00),(69,63,16,1,655500.00,458850.0000,458850.00),(70,64,16,2,655500.00,458850.0000,917700.00),(72,66,16,2,655500.00,458850.0000,917700.00);
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subtotal` decimal(12,2) DEFAULT '0.00',
  `discount_type` enum('percent','amount') DEFAULT NULL,
  `discount_value` decimal(12,2) DEFAULT '0.00',
  `discount_amount` decimal(12,2) DEFAULT '0.00',
  `tax_rate` decimal(6,3) DEFAULT '0.000',
  `tax_amount` decimal(12,2) DEFAULT '0.00',
  `paid_amount` decimal(12,2) DEFAULT '0.00',
  `change_amount` decimal(12,2) DEFAULT '0.00',
  `comment` text,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_type` varchar(50) DEFAULT NULL,
  `status` enum('completed','void','cancelled','refunded') DEFAULT 'completed',
  `user_id` int DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `customer_id` (`customer_id`),
  KEY `idx_sales_created_at` (`created_at`),
  KEY `idx_sales_status` (`status`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (1,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,1200000.00,'cash','completed',2,NULL,'2025-07-29 13:04:00'),(2,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,860000.00,'cash','completed',2,NULL,'2025-07-29 13:04:00'),(3,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,40000.00,'Cash','completed',2,NULL,'2025-08-06 06:38:36'),(4,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,1200000.00,'Cash','completed',2,NULL,'2025-08-06 06:49:51'),(5,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,600000.00,'Cash','completed',2,NULL,'2025-08-06 07:02:52'),(6,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,600000.00,'Cash','completed',2,NULL,'2025-08-06 07:05:00'),(7,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,600000.00,'Cash','completed',2,NULL,'2025-08-06 07:05:34'),(8,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,800000.00,'Cash','completed',2,NULL,'2025-08-06 07:10:26'),(9,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,800000.00,'Cash','completed',2,NULL,'2025-08-06 07:57:59'),(10,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,800000.00,'Cash','completed',2,NULL,'2025-08-06 08:28:39'),(11,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,820000.00,'Cash','completed',2,NULL,'2025-08-06 08:29:38'),(12,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,21600000.00,'Cash','completed',2,NULL,'2025-08-06 08:37:19'),(14,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,350000.00,'Cash','completed',2,NULL,'2025-08-06 14:22:45'),(15,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,800000.00,'Cash','completed',2,NULL,'2025-08-06 14:23:03'),(16,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,800000.00,'Cash','completed',2,NULL,'2025-08-06 14:37:34'),(17,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,800000.00,'Cash','completed',2,NULL,'2025-08-06 14:44:45'),(18,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,800000.00,'Cash','completed',2,NULL,'2025-08-06 14:45:13'),(20,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,1500000.00,'Cash','completed',2,NULL,'2025-08-06 15:04:33'),(21,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,14000.00,'Cash','completed',2,NULL,'2025-08-08 05:55:00'),(22,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,14000.00,'Cash','completed',2,NULL,'2025-08-08 05:55:33'),(23,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,7000.00,'Cash','completed',2,NULL,'2025-08-08 07:01:28'),(24,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,800000.00,'Cash','completed',2,NULL,'2025-08-08 08:11:54'),(25,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,800000.00,'Debit','completed',2,NULL,'2025-08-08 08:28:52'),(26,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,800000.00,'Cash','completed',2,NULL,'2025-08-08 08:29:44'),(27,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,7000.00,'Cash','completed',2,NULL,'2025-08-08 08:34:57'),(28,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,7000.00,'Cash','completed',2,NULL,'2025-08-08 08:38:18'),(29,0.00,NULL,0.00,0.00,0.000,0.00,0.00,0.00,NULL,7000.00,'Cash','completed',2,NULL,'2025-08-08 09:25:54'),(30,7000.00,'amount',20.00,20.00,0.000,0.00,6980.00,0.00,'',6980.00,'Cash','completed',2,NULL,'2025-08-08 10:18:38'),(31,7000.00,'amount',50.00,50.00,0.000,0.00,6950.00,0.00,'You Have some Ka Small Discount on your Fee',6950.00,'Cash','completed',2,NULL,'2025-08-08 10:20:02'),(32,7000.00,'amount',500.00,500.00,0.000,0.00,6500.00,0.00,'Good Customer',6500.00,'Cash','completed',2,NULL,'2025-08-08 10:26:53'),(33,1600000.00,NULL,0.00,0.00,0.000,0.00,2000000.00,400000.00,'',1600000.00,'Cash','completed',2,NULL,'2025-08-08 12:58:50'),(34,7000.00,'percent',3.00,210.00,0.000,0.00,6790.00,0.00,'Great one',6790.00,'Cash','completed',2,NULL,'2025-08-08 12:59:51'),(35,7000.00,NULL,0.00,0.00,0.000,0.00,7000.00,0.00,'',7000.00,'Credit','completed',2,NULL,'2025-08-08 13:22:10'),(36,7000.00,'percent',1.00,70.00,0.000,0.00,20000.00,13070.00,'Fair enough man',6930.00,'Cash','completed',2,NULL,'2025-08-08 13:23:39'),(37,14000.00,NULL,0.00,0.00,0.000,0.00,50000.00,36000.00,'',14000.00,'Cash','completed',2,NULL,'2025-08-08 13:32:36'),(38,7000.00,'amount',50.00,50.00,0.000,0.00,10000.00,3050.00,'Very Good Customer',6950.00,'Cash','completed',2,NULL,'2025-08-09 06:54:06'),(39,55000.00,'amount',500.00,500.00,0.000,0.00,60000.00,5500.00,'This is my Daily Customer',54500.00,'Cash','completed',2,NULL,'2025-08-11 08:23:32'),(40,9600000.00,'amount',200.00,200.00,0.000,0.00,10000000.00,400200.00,'',9599800.00,'Cash','completed',2,NULL,'2025-08-14 13:23:09'),(41,7000.00,NULL,0.00,0.00,0.000,0.00,10000.00,3000.00,'',7000.00,'Cash','completed',2,NULL,'2025-09-02 14:22:09'),(42,821000.00,'percent',2.00,16420.00,18.000,144824.40,1000000.00,50595.60,'',949404.40,'Cash','completed',2,NULL,'2025-09-03 13:35:52'),(43,7000.00,NULL,0.00,0.00,0.000,0.00,10000.00,3000.00,'',7000.00,'Cash','completed',2,NULL,'2025-09-09 07:48:07'),(44,56000.00,NULL,0.00,0.00,0.000,0.00,56000.00,0.00,'',56000.00,'Cash','completed',2,NULL,'2025-09-19 09:26:51'),(45,600000.00,'percent',1.00,6000.00,0.000,0.00,650000.00,56000.00,'',594000.00,'Cash','completed',2,NULL,'2025-10-08 06:17:24'),(46,1640000.00,NULL,0.00,0.00,0.000,0.00,1700000.00,60000.00,'',1640000.00,'Cash','completed',2,NULL,'2025-12-17 06:27:51'),(47,800000.00,NULL,0.00,0.00,0.000,0.00,800000.00,0.00,'',800000.00,'Cash','completed',1,NULL,'2025-12-17 12:08:31'),(48,600000.00,NULL,0.00,0.00,0.000,0.00,600000.00,0.00,'',600000.00,'Cash','completed',6,NULL,'2025-12-17 13:51:10'),(49,350000.00,NULL,0.00,0.00,0.000,0.00,400000.00,50000.00,'',350000.00,'Cash','completed',6,NULL,'2025-12-17 14:26:24'),(50,350000.00,NULL,0.00,0.00,0.000,0.00,350000.00,0.00,'',350000.00,'Cash','completed',6,NULL,'2025-12-17 14:33:11'),(51,800000.00,NULL,0.00,0.00,0.000,0.00,800000.00,0.00,'',800000.00,'Cash','completed',6,NULL,'2025-12-17 14:33:26'),(52,800000.00,NULL,0.00,0.00,0.000,0.00,800000.00,0.00,'',800000.00,'Cash','completed',6,NULL,'2025-12-17 14:38:42'),(53,350000.00,NULL,0.00,0.00,0.000,0.00,350000.00,0.00,'',350000.00,'Cash','completed',6,NULL,'2025-12-17 14:38:51'),(54,350000.00,NULL,0.00,0.00,0.000,0.00,350000.00,0.00,'',350000.00,'Cash','completed',6,NULL,'2025-12-17 14:39:15'),(55,350000.00,NULL,0.00,0.00,0.000,0.00,350000.00,0.00,'',350000.00,'Cash','completed',6,NULL,'2025-12-17 14:42:56'),(56,350000.00,NULL,0.00,0.00,0.000,0.00,350000.00,0.00,'',350000.00,'Cash','completed',6,NULL,'2025-12-17 14:43:07'),(57,350000.00,NULL,0.00,0.00,0.000,0.00,350000.00,0.00,'',350000.00,'Cash','completed',6,NULL,'2025-12-17 14:50:38'),(58,350000.00,NULL,0.00,0.00,0.000,0.00,350000.00,0.00,'',350000.00,'Cash','completed',6,NULL,'2025-12-17 14:54:06'),(59,800000.00,NULL,0.00,0.00,0.000,0.00,800000.00,0.00,'',800000.00,'Cash','completed',6,NULL,'2025-12-17 14:57:50'),(61,690000.00,NULL,0.00,0.00,0.000,0.00,690000.00,0.00,'',690000.00,'Cash','completed',1,NULL,'2025-12-18 07:14:11'),(62,655500.00,'percent',5.00,32775.00,0.000,0.00,622725.00,0.00,'',622725.00,'Cash','completed',1,NULL,'2025-12-18 10:22:38'),(63,655500.00,NULL,0.00,0.00,0.000,0.00,700000.00,44500.00,'',655500.00,'Cash','completed',6,NULL,'2025-12-18 14:25:55'),(64,1311000.00,NULL,0.00,0.00,0.000,0.00,1500000.00,189000.00,'',1311000.00,'Cash','completed',6,NULL,'2025-12-19 09:29:09'),(66,1311000.00,NULL,0.00,0.00,0.000,0.00,2000000.00,689000.00,'',1311000.00,'Cash','completed',1,NULL,'2025-12-19 13:56:52');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shifts`
--

DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `opening_balance` decimal(10,2) DEFAULT NULL,
  `closing_balance` decimal(10,2) DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `opened_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `closed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `shifts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifts`
--

LOCK TABLES `shifts` WRITE;
/*!40000 ALTER TABLE `shifts` DISABLE KEYS */;
INSERT INTO `shifts` VALUES (1,2,2000000.00,4060000.00,'closed','2025-07-29 13:04:00','2025-07-29 13:04:00');
/*!40000 ALTER TABLE `shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_contacts`
--

DROP TABLE IF EXISTS `supplier_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role_title` varchar(100) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `supplier_contacts_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_contacts`
--

LOCK TABLES `supplier_contacts` WRITE;
/*!40000 ALTER TABLE `supplier_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_products`
--

DROP TABLE IF EXISTS `supplier_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_products` (
  `supplier_id` int NOT NULL,
  `product_id` int NOT NULL,
  `supplier_sku` varchar(100) DEFAULT NULL,
  `pack_size` int DEFAULT '1',
  `uom` varchar(20) DEFAULT NULL,
  `moq` int DEFAULT '1',
  `lead_time_days` int DEFAULT '0',
  `last_cost` decimal(12,2) DEFAULT '0.00',
  `currency_code` char(3) DEFAULT 'UGX',
  `preferred` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`supplier_id`,`product_id`),
  KEY `idx_supprod_product` (`product_id`,`preferred`),
  CONSTRAINT `supplier_products_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_products`
--

LOCK TABLES `supplier_products` WRITE;
/*!40000 ALTER TABLE `supplier_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','blocked') DEFAULT 'active',
  `tax_id` varchar(50) DEFAULT NULL,
  `address1` varchar(150) DEFAULT NULL,
  `address2` varchar(150) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `region` varchar(80) DEFAULT NULL,
  `country` varchar(80) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `currency_code` char(3) DEFAULT 'UGX',
  `payment_terms_id` int DEFAULT NULL,
  `credit_limit` decimal(12,2) DEFAULT '0.00',
  `opening_balance` decimal(12,2) DEFAULT '0.00',
  `current_balance` decimal(12,2) DEFAULT '0.00',
  `notes` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sup_created_by` (`created_by`),
  KEY `fk_sup_updated_by` (`updated_by`),
  KEY `idx_suppliers_name` (`name`),
  KEY `idx_suppliers_phone` (`phone`),
  KEY `idx_suppliers_email` (`email`),
  CONSTRAINT `fk_sup_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_sup_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Tech Suppliers Ltd','Mike Tech','0711122233','mike@techsuppliers.com','2025-07-29 13:04:00','active',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'UGX',1,5000000.00,0.00,0.00,NULL,'2025-08-13 09:23:46',NULL,NULL),(2,'Grocery Distributors','Sarah Grocer','0722233344','sarah@grocerydist.com','2025-07-29 13:04:00','active','','','','','','','','UGX',NULL,0.00,20000.00,10000.00,'','2025-09-03 13:45:44',NULL,1),(3,'Riham Company Ltd','Loro Francis','0786754362','rihamltg@co.ug','2025-08-13 09:46:11','active','12343','Kawempe','','Kampala','Africa','Uganda','','UGX',2,30000.00,0.00,0.00,'','2025-08-13 10:06:45',1,1);
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (1,'Piece'),(2,'Kilogram'),(3,'Gram'),(4,'Liter'),(5,'Milliliter'),(6,'Dozen'),(7,'Box'),(8,'Packet'),(9,'Set'),(10,'Inch');
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_permissions` (
  `user_id` int NOT NULL,
  `permission_id` int NOT NULL,
  `allowed` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`user_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permissions`
--

LOCK TABLES `user_permissions` WRITE;
/*!40000 ALTER TABLE `user_permissions` DISABLE KEYS */;
INSERT INTO `user_permissions` VALUES (2,1,1),(2,3,1),(2,10,1),(3,4,1),(3,5,1),(3,6,1),(6,1,1),(6,3,1);
/*!40000 ALTER TABLE `user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Tobias Admin','admin@gmail.com','/POS_UG/uploads/avatars/user_1.jpg','$2y$10$4Sc.PylpFOfFgKuV3sbLYejp7uyM5GXyzlYqJBKcClSIy51Psl7HC',1,'2025-07-29 13:04:00'),(2,'Bob Cashier','cashier@example.com',NULL,'$2y$10$CKjZdh9ZjVmQgIhdUSh1w.FOHKgJ9i8QudhN1usdUDVeWhP02ixn6',2,'2025-07-29 13:04:00'),(3,'Carol Inventory','inventory@example.com',NULL,'inventory123',3,'2025-07-29 13:04:00'),(6,'Katwita Dennis','kats@gmail.com','/POS_UG/uploads/avatars/user_6.jpg','$2y$10$ssu4fdWN.uLOjVMQYsP2SuK2Y/hdyswSSbpd3c63Ow3iV4wXbEvD.',2,'2025-12-17 09:11:03');
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

-- Dump completed on 2025-12-20 11:58:00
