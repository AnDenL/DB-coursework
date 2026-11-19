/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.4.13-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: expeditions_db
-- ------------------------------------------------------
-- Server version	11.4.13-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `artifacts`
--

DROP TABLE IF EXISTS `artifacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `artifacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `expedition_id` bigint(20) unsigned NOT NULL,
  `item_condition` enum('Good','Mediocre','Needs restoration') NOT NULL,
  `estimated_century` int(11) DEFAULT NULL,
  `estimated_value` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expedition_id` (`expedition_id`),
  CONSTRAINT `artifacts_ibfk_1` FOREIGN KEY (`expedition_id`) REFERENCES `expeditions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifacts`
--

LOCK TABLES `artifacts` WRITE;
/*!40000 ALTER TABLE `artifacts` DISABLE KEYS */;
INSERT INTO `artifacts` VALUES
(1,'Поліський тканий рушник-набожник','Домоткане полотно з геометричним червоним орнаментом, знайдено в с. Сварицевичі.','uploads/artifacts/rushnyk.jpg',1,'Needs restoration',19,18500.00),
(2,'Збірник веснянок та русальних пісень','Аудіозапис та розшифровка 24 обрядових пісень від місцевих старожилів.',NULL,1,'Good',20,4000.00),
(3,'Гуцульський топірець-бартка','Латунне карбування по топорищу з ясена, родинна реліквія з Верховинщини.','uploads/artifacts/bartka.jpg',2,'Good',19,32000.00),
(4,'Керамічна миска з підполив\'яним розписом','Традиційний подільський орнамент із зображенням птахів, є тріщина по краю.','uploads/artifacts/myska.jpg',3,'Mediocre',18,24000.00),
(5,'Дерев\'яна скриня з кованими окуттями','Весільна скриня, розписана рослинними мотивами, потребує очищення та зміцнення деревини.','uploads/artifacts/skrynia.jpg',3,'Needs restoration',19,29500.00),
(6,'Мідний натільний хрест','Знайдений поблизу старого козацького тракту, патина збережена.',NULL,4,'Mediocre',17,15000.00),
(7,'Фрагмент кам\'яної плити з петрогліфами','Неідентифікований знак зі скельного комплексу, передано на експертизу.','uploads/artifacts/stone.jpg',5,'Needs restoration',NULL,NULL);
/*!40000 ALTER TABLE `artifacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `estimated_cost` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment`
--

LOCK TABLES `equipment` WRITE;
/*!40000 ALTER TABLE `equipment` DISABLE KEYS */;
INSERT INTO `equipment` VALUES
(1,'Польовий диктофон Zoom H5',11500.00),
(2,'Фотоапарат Sony Alpha + об\'єктив',34000.00),
(3,'Набір для консервації текстилю',4800.00),
(4,'Захищений GPS-навігатор Garmin',14200.00),
(5,'Експедиційний намет (4-місний)',8900.00),
(6,'Портативний LED-освітлювач для зйомки',3600.00),
(7,'Герметичний кофр для артефактів',5400.00);
/*!40000 ALTER TABLE `equipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expedition_equipment`
--

DROP TABLE IF EXISTS `expedition_equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expedition_equipment` (
  `expedition_id` bigint(20) unsigned NOT NULL,
  `equipment_id` bigint(20) unsigned NOT NULL,
  `count` int(10) unsigned NOT NULL,
  PRIMARY KEY (`expedition_id`,`equipment_id`),
  KEY `fk_equip_item` (`equipment_id`),
  CONSTRAINT `fk_equip_expedition` FOREIGN KEY (`expedition_id`) REFERENCES `expeditions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_equip_item` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expedition_equipment`
--

LOCK TABLES `expedition_equipment` WRITE;
/*!40000 ALTER TABLE `expedition_equipment` DISABLE KEYS */;
INSERT INTO `expedition_equipment` VALUES
(1,1,2),
(1,4,1),
(1,5,2),
(2,2,1),
(2,4,2),
(2,5,2),
(2,7,2),
(3,3,2),
(3,6,2),
(4,1,1),
(4,2,1),
(4,5,1),
(4,6,1),
(5,2,1),
(6,1,2);
/*!40000 ALTER TABLE `expedition_equipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expedition_members`
--

DROP TABLE IF EXISTS `expedition_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expedition_members` (
  `expedition_id` bigint(20) unsigned NOT NULL,
  `researcher_id` bigint(20) unsigned NOT NULL,
  `researcher_role` enum('Leader','Folklorist','Collector','Restorer','Archivist','Bodyguard') NOT NULL,
  PRIMARY KEY (`expedition_id`,`researcher_id`),
  KEY `fk_members_researcher` (`researcher_id`),
  CONSTRAINT `fk_members_expedition` FOREIGN KEY (`expedition_id`) REFERENCES `expeditions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_members_researcher` FOREIGN KEY (`researcher_id`) REFERENCES `researchers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expedition_members`
--

LOCK TABLES `expedition_members` WRITE;
/*!40000 ALTER TABLE `expedition_members` DISABLE KEYS */;
INSERT INTO `expedition_members` VALUES
(1,1,'Leader'),
(1,2,'Folklorist'),
(1,5,'Restorer'),
(2,1,'Leader'),
(2,3,'Collector'),
(2,4,'Archivist'),
(2,6,'Bodyguard'),
(3,3,'Leader'),
(3,5,'Restorer'),
(4,4,'Leader'),
(5,1,'Leader'),
(6,2,'Leader');
/*!40000 ALTER TABLE `expedition_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expeditions`
--

DROP TABLE IF EXISTS `expeditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expeditions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `region_id` bigint(20) unsigned NOT NULL,
  `budget` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `region_id` (`region_id`),
  CONSTRAINT `expeditions_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expeditions`
--

LOCK TABLES `expeditions` WRITE;
/*!40000 ALTER TABLE `expeditions` DISABLE KEYS */;
INSERT INTO `expeditions` VALUES
(1,'Поліська архаїка: обряди та побут',1,85000.00,'2026-05-10','2026-05-24'),
(2,'Скарби Чорногори та полонин',2,110000.00,'2026-06-15','2026-07-02'),
(3,'Гончарні традиції Східного Поділля',3,65000.00,'2026-07-20','2026-08-01'),
(4,'Степові шляхи Таврії',4,72000.00,'2026-08-12','2026-08-23'),
(5,'Печерні храми Подністров\'я',5,95000.00,'2026-09-05','2026-09-18'),
(6,'Осінній збір фольклору Приазов\'я',6,60000.00,'2026-11-10',NULL);
/*!40000 ALTER TABLE `expeditions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regions`
--

DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `regions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `temperature_zone` enum('Freezing','Cold','Temperate','Warm','Scorching') NOT NULL,
  `humidity_level` enum('Arid','Dry','Moderate','Humid','Swampy') NOT NULL,
  `terrain_type` enum('Forest','Mountains','Plains','Swamp','Coast','Underground','Desert') NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `population` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regions`
--

LOCK TABLES `regions` WRITE;
/*!40000 ALTER TABLE `regions` DISABLE KEYS */;
INSERT INTO `regions` VALUES
(1,'Українське Полісся','Лісисто-болотистий регіон на півночі України з багатою архаїчною обрядовістю та дерев\'яним зодчеством.','Temperate','Swampy','Swamp','uploads/regions/6afdec913f50b0.92430739.png',100),
(2,'Гуцульщина (Чорногора)','Високогірний карпатський регіон із самобутнім ткацтвом, мосяжництвом та полонинським господарством.','Cold','Humid','Mountains','uploads/regions/6afdee2e202350.26251225.png',54321),
(3,'Східне Поділля','Лісостепова зона з традиціями настінного розпису хат та гончарними осередками.','Temperate','Moderate','Plains','uploads/regions/6afdfc612c69d1.18883064.jpg',646),
(4,'Таврійський степ','Посушливий степовий регіон півдня з курганними комплексами та козацькими зимівниками.','Warm','Dry','Desert',NULL,0),
(5,'Покуття та Подністров\'я','Горбиста місцевість уздовж Дністра з печерними скитами та традиційною вишивкою.','Temperate','Humid','Underground','uploads/regions/6afe01fcd80dd9.26697428.jpg',5777),
(6,'Приазов\'я','Прибережна степова смуга з багатонаціональним фольклором греків та українців.','Warm','Moderate','Coast','uploads/regions/azov.jpg',0);
/*!40000 ALTER TABLE `regions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `researchers`
--

DROP TABLE IF EXISTS `researchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `researchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(120) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `academic_degree` varchar(80) NOT NULL,
  `phone` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `researchers`
--

LOCK TABLES `researchers` WRITE;
/*!40000 ALTER TABLE `researchers` DISABLE KEYS */;
INSERT INTO `researchers` VALUES
(1,'Ковальчук Олена Петрівна','uploads/researchers/kovalchuk.jpg','Доктор історичних наук','+380671234501'),
(2,'Мельник Тарас Григорович','uploads/researchers/melnyk.jpg','Кандидат філологічних наук','+380502345602'),
(3,'Бойко Ірина Василівна','uploads/researchers/boyko.jpg','Кандидат мистецтвознавства','+380933456703'),
(4,'Савченко Андрій Миколайович','uploads/researchers/savchenko.jpg','Старший науковий співробітник','+380684567804'),
(5,'Ткаченко Марія Олександрівна','uploads/researchers/tkachenko.jpg','Реставратор вищої категорії','+380975678905'),
(6,'Гнатюк Роман Степанович','uploads/researchers/hnatiuk.jpg','Молодший науковий співробітник','+380636789006');
/*!40000 ALTER TABLE `researchers` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-11-18 23:49:10
