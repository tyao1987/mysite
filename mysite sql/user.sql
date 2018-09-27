/*
SQLyog 企业版 - MySQL GUI v8.14 
MySQL - 5.5.32-0ubuntu0.12.04.1 : Database - user
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`user` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `user`;

/*Table structure for table `SiteUserProperties` */

DROP TABLE IF EXISTS `SiteUserProperties`;

CREATE TABLE `SiteUserProperties` (
  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `PropertyKeyName` varchar(60) DEFAULT NULL COMMENT '属性名称',
  `DisplayName` varchar(150) DEFAULT NULL COMMENT '属性displayName',
  `IsActive` varchar(9) DEFAULT NULL COMMENT '是否使用',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

/*Data for the table `SiteUserProperties` */

insert  into `SiteUserProperties`(`ID`,`PropertyKeyName`,`DisplayName`,`IsActive`) values (1,'FIRST_NAME','First Name','YES'),(2,'LAST_NAME','Last Name','YES'),(3,'GENDER','Gender','YES'),(4,'YEAR_OF_BIRTH','Year of Birth','YES'),(5,'POST_ADDRESS','Post Address','YES'),(6,'POST_CODE','Post Code','YES'),(7,'CITY','City','YES'),(8,'MOBILE_PHONE','Mobile Phone','YES');

/*Table structure for table `SiteUserPropertiesMapping` */

DROP TABLE IF EXISTS `SiteUserPropertiesMapping`;

CREATE TABLE `SiteUserPropertiesMapping` (
  `UserID` int(11) unsigned NOT NULL DEFAULT '0',
  `PropertyID` int(11) unsigned NOT NULL DEFAULT '0',
  `PropertyValue` varchar(500) NOT NULL DEFAULT '',
  `AddDate` timestamp NOT NULL DEFAULT '2000-01-01 08:00:00',
  `LastChangeDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`UserID`,`PropertyID`),
  KEY `PropertyValue` (`PropertyValue`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `SiteUserPropertiesMapping` */

insert  into `SiteUserPropertiesMapping`(`UserID`,`PropertyID`,`PropertyValue`,`AddDate`,`LastChangeDate`) values (2,1,'terry','2014-09-28 20:22:05','2014-11-12 01:32:40'),(2,2,'yao','2014-09-28 20:22:05','2014-11-12 01:32:40'),(2,3,'F','2014-09-28 20:40:24','2014-10-08 19:49:53'),(2,4,'2010','2014-09-28 23:09:47','2014-11-30 23:40:07'),(2,5,'pudong','2014-09-28 23:27:55','2014-09-29 02:44:42'),(2,6,'200198','2014-09-28 23:27:55','2014-09-29 02:44:42'),(2,7,'shanghai','2014-09-28 23:27:55','2014-09-29 02:44:42'),(2,8,'123456789','2014-09-28 23:54:56','2014-11-30 23:04:48');

/*Table structure for table `SiteUsers` */

DROP TABLE IF EXISTS `SiteUsers`;

CREATE TABLE `SiteUsers` (
  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `UserName` varchar(100) NOT NULL DEFAULT '' COMMENT '昵称,用户名',
  `Email` varchar(100) NOT NULL DEFAULT '' COMMENT 'email地址',
  `EmailValid` enum('YES','NO','UNKNOWN') DEFAULT NULL COMMENT '是否验证',
  `Password` varchar(200) NOT NULL DEFAULT '' COMMENT '密码',
  `VerifyKey` varchar(200) NOT NULL DEFAULT '' COMMENT 'email验证的key',
  `ResetVerifyKey` varchar(200) NOT NULL DEFAULT '' COMMENT '忘记密码时需要验证key',
  `CreatedDate` timestamp NOT NULL DEFAULT '2000-01-01 08:00:00' COMMENT '注册时间',
  `VerifyEmailDate` timestamp NULL DEFAULT NULL COMMENT '通过验证时间',
  `LastChangeDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

/*Data for the table `SiteUsers` */

insert  into `SiteUsers`(`ID`,`UserName`,`Email`,`EmailValid`,`Password`,`VerifyKey`,`ResetVerifyKey`,`CreatedDate`,`VerifyEmailDate`,`LastChangeDate`) values (1,'sdf','sdfs@email.com','NO','SHA-256:[ktzpwLFTj0+uR2/pwq/5i7w0oeCMrVu3axdFcyhx3bU=]','1a23db4a743c3992bcf221cabdc98220','','2014-08-25 13:41:35',NULL,'2014-08-24 22:41:35'),(2,'tyao1987','tyao1988@163.com','YES','SHA-256:[Vcrq7NPT1n4T8fkqoAgmivHM0LVjmerwbBwaFP5lfN0=]','095774a30a86b1d63e72250f6b4bd735','','2014-08-26 09:05:52','2014-08-29 15:34:42','2015-02-12 19:08:04'),(3,'tyao11987','tyao19187@163.com','NO','SHA-256:[MOzmlKKs6sz0smy5WbUyiQLbijmqy2qVhquG/qXJsmg=]','9cc50d0e579a8d8c1463c0527edadb30','','2014-08-29 09:08:30',NULL,'2014-09-28 02:30:23'),(4,'asdf','aa@email.com','NO','SHA-256:[qr/zc3g0Cajd7ln78Y3sU1Iku6R5hrFAdnS+t+YegGo=]','da8d31979c21224024b979597ac562f7','','2014-08-29 17:43:03',NULL,'2014-08-29 02:43:03'),(5,'sdfs','dfs@aa.com','YES','SHA-256:[qze8SiPMUazDwSJ7Eaf8GeWbvBWhyX5lNt6wdGXE6e4=]','','','2014-08-29 17:46:05','2014-09-02 10:50:54','2014-09-01 19:50:54'),(6,'sdfaa','sdf@a.com','YES','SHA-256:[e6OTm2ns/mzP6KxMWFOTWKwYvVOTmEckpeYxb6WVRZg=]','','','2014-09-02 10:35:58','2014-09-02 10:36:53','2014-09-01 19:36:53'),(7,'wwerw','sdfdww@a.com','NO','SHA-256:[UaOqrb1FXF35sE91+8cfOgmqwd2wMUKfpBS72p5HtLs=]','2c63592f5ec17b45cb58168a461cc0fd','','2014-09-22 17:43:19',NULL,'2014-09-22 02:43:19');

/* Procedure structure for procedure `ActiveUserAccount` */

/*!50003 DROP PROCEDURE IF EXISTS  `ActiveUserAccount` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `ActiveUserAccount`(IN u_verifyKey TEXT, IN u_verifyEmailDate TEXT)
BEGIN
	DECLARE u_id INT DEFAULT 0;
	SELECT `ID` INTO u_id
	FROM `SiteUsers`
	WHERE
		`VerifyKey`=u_verifyKey;
	UPDATE `SiteUsers`
	SET
		`EmailValid`='YES',
		`VerifyKey`='',
		`VerifyEmailDate`=u_verifyEmailDate
	WHERE
		`VerifyKey`=u_verifyKey;
	SELECT `ID`, `UserName`, `Email`
	FROM `SiteUsers`
	WHERE
		`ID`=u_id;
END */$$
DELIMITER ;

/* Procedure structure for procedure `ChangeUserEmailById` */

/*!50003 DROP PROCEDURE IF EXISTS  `ChangeUserEmailById` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `ChangeUserEmailById`(IN u_id INT, IN u_email TEXT)
BEGIN
	UPDATE `SiteUsers`
	SET
		`Email`=u_email
	WHERE
		`ID`=u_id;
	
END */$$
DELIMITER ;

/* Procedure structure for procedure `CheckIfExistsEmail` */

/*!50003 DROP PROCEDURE IF EXISTS  `CheckIfExistsEmail` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `CheckIfExistsEmail`(IN u_email TEXT, OUT return_value TEXT)
BEGIN
	SELECT
		COUNT(1)  INTO return_value
	FROM
		`SiteUsers`
	WHERE
		`Email`=u_email;
END */$$
DELIMITER ;

/* Procedure structure for procedure `CheckIfExistsResetKey` */

/*!50003 DROP PROCEDURE IF EXISTS  `CheckIfExistsResetKey` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `CheckIfExistsResetKey`(IN u_verifyKey VARCHAR(200), OUT return_value TEXT)
BEGIN
	SELECT `ID` INTO return_value FROM `SiteUsers` WHERE `ResetVerifyKey`=u_verifyKey;
END */$$
DELIMITER ;

/* Procedure structure for procedure `CheckIfExistsUserName` */

/*!50003 DROP PROCEDURE IF EXISTS  `CheckIfExistsUserName` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `CheckIfExistsUserName`(IN u_name TEXT, OUT return_value TEXT)
BEGIN
	SELECT
		COUNT(1)  INTO return_value
	FROM
		`SiteUsers`
	WHERE
		`UserName`=u_name;
END */$$
DELIMITER ;

/* Procedure structure for procedure `CheckIfExistsVerifyKey` */

/*!50003 DROP PROCEDURE IF EXISTS  `CheckIfExistsVerifyKey` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `CheckIfExistsVerifyKey`(IN u_verifyKey VARCHAR(200), OUT return_value TEXT)
BEGIN
	SELECT `ID` INTO return_value FROM `SiteUsers` WHERE `VerifyKey`=u_verifyKey;
END */$$
DELIMITER ;

/* Procedure structure for procedure `CleanResetKeyById` */

/*!50003 DROP PROCEDURE IF EXISTS  `CleanResetKeyById` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `CleanResetKeyById`(IN u_id INT)
BEGIN
	UPDATE `SiteUsers`
	SET `ResetVerifyKey`=''
	WHERE
		`ID`=u_id;
END */$$
DELIMITER ;

/* Procedure structure for procedure `CleanVerifyKeyById` */

/*!50003 DROP PROCEDURE IF EXISTS  `CleanVerifyKeyById` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `CleanVerifyKeyById`(IN u_id INT)
BEGIN
	UPDATE `SiteUsers`
	SET `VerifyKey`=''
	WHERE
		`ID`=u_id;
END */$$
DELIMITER ;

/* Procedure structure for procedure `GetUserAccountIsActivedByEmail` */

/*!50003 DROP PROCEDURE IF EXISTS  `GetUserAccountIsActivedByEmail` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserAccountIsActivedByEmail`(IN u_email TEXT)
BEGIN
	SELECT
		`EmailValid`
	FROM
		`SiteUsers`
	WHERE
		`Email`=u_email;
END */$$
DELIMITER ;

/* Procedure structure for procedure `GetUserAccountIsActivedById` */

/*!50003 DROP PROCEDURE IF EXISTS  `GetUserAccountIsActivedById` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserAccountIsActivedById`(IN u_id TEXT, OUT return_value TEXT)
BEGIN
	DECLARE result TEXT DEFAULT NULL;
	SELECT
		`EmailValid` INTO result
	FROM
		`SiteUsers`
	WHERE
		`ID`=u_id;
	SET return_value=result;
END */$$
DELIMITER ;

/* Procedure structure for procedure `GetUserDataById` */

/*!50003 DROP PROCEDURE IF EXISTS  `GetUserDataById` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserDataById`(IN u_id TEXT)
BEGIN
	SELECT
		*
	FROM
		`SiteUsers`
	WHERE
		`ID`=u_id;
END */$$
DELIMITER ;

/* Procedure structure for procedure `GetUserIdByEmail` */

/*!50003 DROP PROCEDURE IF EXISTS  `GetUserIdByEmail` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserIdByEmail`(IN v_email TEXT, OUT v_ret TEXT)
BEGIN
    SELECT
        ID INTO v_ret
    FROM
        `SiteUsers`
    WHERE
        `Email`=v_email;
END */$$
DELIMITER ;

/* Procedure structure for procedure `GetUserLoginData` */

/*!50003 DROP PROCEDURE IF EXISTS  `GetUserLoginData` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserLoginData`(IN u_email TEXT, OUT u_id INT, OUT u_userName TEXT, OUT u_password TEXT, OUT u_emailValid TEXT)
BEGIN
	SELECT
		`ID`, `UserName`, `Password`, `EmailValid` INTO u_id, u_userName, u_password, u_emailValid
	FROM
		`SiteUsers`
	WHERE
		`Email`=u_email;
END */$$
DELIMITER ;

/* Procedure structure for procedure `GetUserPropertiesData` */

/*!50003 DROP PROCEDURE IF EXISTS  `GetUserPropertiesData` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserPropertiesData`(IN u_id INT)
BEGIN
	SELECT PropertyID, PropertyValue FROM SiteUserPropertiesMapping WHERE UserId=u_id;
END */$$
DELIMITER ;

/* Procedure structure for procedure `GetUserPropertiesID` */

/*!50003 DROP PROCEDURE IF EXISTS  `GetUserPropertiesID` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserPropertiesID`()
BEGIN
	SELECT ID, PropertyKeyName FROM SiteUserProperties;
END */$$
DELIMITER ;

/* Procedure structure for procedure `GetVerifyKeyByEmail` */

/*!50003 DROP PROCEDURE IF EXISTS  `GetVerifyKeyByEmail` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `GetVerifyKeyByEmail`(IN u_email TEXT, OUT u_verifyKey TEXT, OUT u_emailValid TEXT)
BEGIN
	SELECT
		VerifyKey, EmailValid INTO u_verifyKey, u_emailValid
	FROM SiteUsers
	WHERE
		Email=u_email;
END */$$
DELIMITER ;

/* Procedure structure for procedure `SetPasswordVerifyKey` */

/*!50003 DROP PROCEDURE IF EXISTS  `SetPasswordVerifyKey` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `SetPasswordVerifyKey`(IN u_email TEXT, IN u_verifyKey VARCHAR(200))
BEGIN
	UPDATE `SiteUsers`
	SET
		`ResetVerifyKey`=u_verifyKey
	WHERE
		`Email`=u_email;
END */$$
DELIMITER ;

/* Procedure structure for procedure `SetUserPropertiesData` */

/*!50003 DROP PROCEDURE IF EXISTS  `SetUserPropertiesData` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `SetUserPropertiesData`(IN u_id INT, IN p_id INT, IN p_value TEXT)
BEGIN
	DECLARE a INT DEFAULT 0;
	
	SELECT `UserID` INTO a FROM `SiteUserPropertiesMapping` WHERE `UserID`=u_id AND `PropertyID`=p_id;
	IF a=0 THEN
		INSERT INTO `SiteUserPropertiesMapping` VALUES (u_id, p_id, p_value, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP());
	END IF;
	IF a<>0 THEN
		UPDATE `SiteUserPropertiesMapping` 
		SET `PropertyValue`=p_value 
		WHERE `UserID`=u_id AND `PropertyID`=p_id;
	END IF;
END */$$
DELIMITER ;

/* Procedure structure for procedure `SetVerifyKeyById` */

/*!50003 DROP PROCEDURE IF EXISTS  `SetVerifyKeyById` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `SetVerifyKeyById`(IN u_id INT, IN u_verifyKey TEXT)
BEGIN
	UPDATE `SiteUsers`
	SET
		`VerifyKey`=u_verifyKey
	WHERE
		`ID`=u_id;
END */$$
DELIMITER ;

/* Procedure structure for procedure `UpdateUserPassword` */

/*!50003 DROP PROCEDURE IF EXISTS  `UpdateUserPassword` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateUserPassword`(IN u_id INT, IN u_password TEXT)
BEGIN
	UPDATE `SiteUsers` 
	SET `Password`=u_password 
	WHERE `ID`=u_id;
END */$$
DELIMITER ;

/* Procedure structure for procedure `UserRegistration` */

/*!50003 DROP PROCEDURE IF EXISTS  `UserRegistration` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`root`@`localhost` PROCEDURE `UserRegistration`(IN u_name TEXT, IN u_email TEXT, IN u_emailValid TEXT, IN u_verifyKey TEXT, IN u_createdDate TEXT,  OUT return_value TEXT)
BEGIN
	INSERT INTO `SiteUsers` (`UserName`, `Email`, `EmailValid`, `VerifyKey`, `CreatedDate`)
	VALUES(u_name, u_email, u_emailValid, u_verifyKey, u_createdDate);
	SELECT `ID` INTO return_value
	FROM `SiteUsers`
	WHERE
		`UserName` = u_name;
END */$$
DELIMITER ;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
