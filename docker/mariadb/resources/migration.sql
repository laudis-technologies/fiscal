-- MariaDB dump 10.18  Distrib 10.5.8-MariaDB, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: test
-- ------------------------------------------------------
-- Server version	10.5.7-MariaDB-1:10.5.7+maria~focal

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `indexed_value_group_pivot`
--

DROP TABLE IF EXISTS `indexed_value_group_pivot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indexed_value_group_pivot` (
                                             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                             `created_at` timestamp NULL DEFAULT NULL,
                                             `updated_at` timestamp NULL DEFAULT NULL,
                                             `indexed_value_id` bigint(20) unsigned NOT NULL,
                                             `group_id` bigint(20) unsigned NOT NULL,
                                             PRIMARY KEY (`id`),
                                             UNIQUE KEY `unique_indexed_value_group_pivot` (`indexed_value_id`,`group_id`),
                                             KEY `indexed_value_group_pivot_group_id_foreign` (`group_id`),
                                             CONSTRAINT `indexed_value_group_pivot_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `indexed_value_groups` (`id`),
                                             CONSTRAINT `indexed_value_group_pivot_indexed_value_id_foreign` FOREIGN KEY (`indexed_value_id`) REFERENCES `indexed_values` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_value_group_pivot`
--

LOCK TABLES `indexed_value_group_pivot` WRITE;
/*!40000 ALTER TABLE `indexed_value_group_pivot` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_value_group_pivot` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_value_groups`
--

DROP TABLE IF EXISTS `indexed_value_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indexed_value_groups` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `created_at` timestamp NULL DEFAULT NULL,
                                        `updated_at` timestamp NULL DEFAULT NULL,
                                        `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                                        `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                                        `parent_group_id` bigint(20) unsigned DEFAULT NULL,
                                        PRIMARY KEY (`id`),
                                        KEY `indexed_value_groups_parent_group_id_foreign` (`parent_group_id`),
                                        KEY `indexed_value_groups_name_index` (`name`),
                                        KEY `indexed_value_groups_slug_index` (`slug`),
                                        CONSTRAINT `indexed_value_groups_parent_group_id_foreign` FOREIGN KEY (`parent_group_id`) REFERENCES `indexed_value_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_value_groups`
--

LOCK TABLES `indexed_value_groups` WRITE;
/*!40000 ALTER TABLE `indexed_value_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_value_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_value_versions`
--

DROP TABLE IF EXISTS `indexed_value_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indexed_value_versions` (
                                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                          `created_at` timestamp NULL DEFAULT NULL,
                                          `updated_at` timestamp NULL DEFAULT NULL,
                                          `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                          `value` double(16,8) DEFAULT NULL,
                                          `added_since` date NOT NULL,
                                          `removed_since` date NOT NULL,
                                          `original_date` date DEFAULT NULL,
                                          `contextual_id` bigint(20) unsigned NOT NULL,
                                          PRIMARY KEY (`id`),
                                          KEY `indexed_values_v2_version_contextual_id_foreign` (`contextual_id`),
                                          KEY `indexed_values_v2_version_added_since_index` (`added_since`),
                                          KEY `indexed_values_v2_version_name_index` (`name`),
                                          KEY `indexed_values_v2_version_original_date_index` (`original_date`),
                                          KEY `indexed_values_v2_version_removed_since_index` (`removed_since`),
                                          KEY `indexed_values_v2_version_value_index` (`value`),
                                          CONSTRAINT `indexed_values_v2_version_contextual_id_foreign` FOREIGN KEY (`contextual_id`) REFERENCES `indexed_values` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_value_versions`
--

LOCK TABLES `indexed_value_versions` WRITE;
/*!40000 ALTER TABLE `indexed_value_versions` DISABLE KEYS */;
INSERT INTO `indexed_value_versions` VALUES (1,NULL,NULL,NULL,100.00000000,'2019-01-01','9999-12-31',NULL,1),(2,NULL,NULL,NULL,110.00000000,'2020-01-01','9999-12-31',NULL,1),(3,NULL,NULL,NULL,121.00000000,'2021-01-01','9999-12-31',NULL,1),(4,NULL,NULL,NULL,200.00000000,'2019-01-01','9999-12-31',NULL,2),(5,NULL,NULL,NULL,220.00000000,'2020-01-01','9999-12-31',NULL,2),(6,NULL,NULL,NULL,242.00000000,'2021-01-01','9999-12-31',NULL,2),(7,NULL,NULL,NULL,0.50000000,'2019-01-01','9999-12-31',NULL,3),(8,NULL,NULL,NULL,0.55000000,'2020-01-01','9999-12-31',NULL,3),(9,NULL,NULL,NULL,0.52000000,'2021-01-01','9999-12-31',NULL,3),(10,NULL,NULL,NULL,0.30000000,'2019-01-01','9999-12-31',NULL,4),(11,NULL,NULL,NULL,0.25000000,'2020-01-01','9999-12-31',NULL,4),(12,NULL,NULL,NULL,0.20000000,'2021-01-01','9999-12-31',NULL,4);
/*!40000 ALTER TABLE `indexed_value_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_values`
--

DROP TABLE IF EXISTS `indexed_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
create table indexed_values
(
    id bigint unsigned auto_increment
        primary key,
    created_at timestamp null,
    updated_at timestamp null,
    slug varchar(255) not null,
    original_name varchar(255) not null,
    type varchar(255) not null,
    `precision` int null,
    constraint indexed_values_v2_slug_unique
        unique (slug)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

create index indexed_values_v2_original_name_index
    on indexed_values (original_name);

create index indexed_values_v2_type_index
    on indexed_values (type);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_values`
--

LOCK TABLES `indexed_values` WRITE;
/*!40000 ALTER TABLE `indexed_values` DISABLE KEYS */;
INSERT INTO `indexed_values` VALUES (1,NULL,NULL,'a','a','euro', 2),(2,NULL,NULL,'b','b','euro', 2),(3,NULL,NULL,'c','c','percentage', 4),(4,NULL,NULL,'d','d','percentage', 4);
/*!40000 ALTER TABLE `indexed_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexed_values_categories`
--

DROP TABLE IF EXISTS `indexed_values_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indexed_values_categories` (
                                             `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                             `name` text COLLATE utf8_unicode_ci NOT NULL,
                                             `created_at` timestamp NULL DEFAULT NULL,
                                             `updated_at` timestamp NULL DEFAULT NULL,
                                             `deleted_at` timestamp NULL DEFAULT NULL,
                                             `contextual_id` int(10) unsigned NOT NULL,
                                             `parent_contextual_id` int(10) unsigned DEFAULT NULL,
                                             `added_since` date NOT NULL,
                                             `removed_since` date DEFAULT NULL,
                                             `slug` varchar(191) COLLATE utf8_unicode_ci NOT NULL,
                                             PRIMARY KEY (`id`),
                                             UNIQUE KEY `unique_contextual_id` (`contextual_id`,`added_since`,`deleted_at`),
                                             UNIQUE KEY `unique_slug` (`slug`,`added_since`,`deleted_at`),
                                             KEY `indexed_values_categories_parent_contextual_id_foreign` (`parent_contextual_id`),
                                             KEY `indexed_values_categories_added_since_index` (`added_since`),
                                             KEY `indexed_values_categories_contextual_id_index` (`contextual_id`),
                                             KEY `indexed_values_categories_removed_since_index` (`removed_since`),
                                             KEY `indexed_values_categories_slug_index` (`slug`),
                                             CONSTRAINT `indexed_values_categories_parent_contextual_id_foreign` FOREIGN KEY (`parent_contextual_id`) REFERENCES `indexed_values_categories` (`contextual_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexed_values_categories`
--

LOCK TABLES `indexed_values_categories` WRITE;
/*!40000 ALTER TABLE `indexed_values_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexed_values_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scale_rules`
--

DROP TABLE IF EXISTS `scale_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scale_rules` (
                               `id` int(11) NOT NULL AUTO_INCREMENT,
                               `upper_limit` bigint(20) unsigned DEFAULT NULL,
                               `factor` bigint(20) unsigned DEFAULT NULL,
                               `scale_id` int(11) NOT NULL,
                               `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                               `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
                               `start` date NOT NULL,
                               `end` date NOT NULL,
                               PRIMARY KEY (`id`),
                               KEY `factor` (`factor`),
                               KEY `scale_id` (`scale_id`),
                               KEY `upper_limit` (`upper_limit`),
                               CONSTRAINT `scale_rules_ibfk_1` FOREIGN KEY (`scale_id`) REFERENCES `scales` (`id`),
                               CONSTRAINT `scale_rules_ibfk_2` FOREIGN KEY (`upper_limit`) REFERENCES `indexed_values` (`id`),
                               CONSTRAINT `scale_rules_ibfk_3` FOREIGN KEY (`factor`) REFERENCES `indexed_values` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scale_rules`
--

LOCK TABLES `scale_rules` WRITE;
/*!40000 ALTER TABLE `scale_rules` DISABLE KEYS */;
INSERT INTO `scale_rules` VALUES (2,1,NULL,1,'2020-12-26 19:54:03',NULL,'2020-01-01','9999-12-31'),(3,2,3,1,'2020-12-26 19:54:03',NULL,'2020-01-01','9999-12-31'),(4,NULL,4,1,'2020-12-26 19:54:33',NULL,'2020-01-01','9999-12-31'),(5,1,3,2,'2020-12-26 19:55:58',NULL,'2020-01-01','9999-12-31'),(6,NULL,NULL,2,'2020-12-26 19:55:58',NULL,'2020-01-01','2020-12-31'),(7,2,4,2,'2020-12-26 19:55:58',NULL,'2020-01-01','9999-12-31');
/*!40000 ALTER TABLE `scale_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scales`
--

DROP TABLE IF EXISTS `scales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scales` (
                          `id` int(11) NOT NULL AUTO_INCREMENT,
                          `name` varchar(255) NOT NULL,
                          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
                          `slug` varchar(255) NOT NULL,
                          PRIMARY KEY (`id`),
                          KEY `name` (`name`),
                          KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scales`
--

LOCK TABLES `scales` WRITE;
/*!40000 ALTER TABLE `scales` DISABLE KEYS */;
INSERT INTO `scales` VALUES (1,'e','2020-12-26 19:52:26',NULL,'e'),(2,'f','2020-12-26 19:52:26',NULL,'f');
/*!40000 ALTER TABLE `scales` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-12-26 21:22:25
