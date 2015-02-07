-- Table structure for table `athlete_data`
-- met = 0 distance, 1 duration

DROP TABLE IF EXISTS `sports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(32) NOT NULL,
  `abrev` varchar(32) NOT NULL,
  `extra_weight` float NOT NULL,
  `met` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- INSERT generic data for all users
INSERT INTO sports select null, id, "Correr", "RUN", 0, 0 from users;
INSERT INTO sports select null, id, "Ciclismo", "BIK", 0, 0 from users;
INSERT INTO sports select null, id, "Andar", "WLK", 0, 0 from users;
INSERT INTO sports select null, id, "Natación", "SWI", 0, 0 from users;

-- INSERT Elíptica just for nibble uses
INSERT INTO sports select null, id, "Elíptica", "ELI", 0, 1 from users where username='nibble';


-- update records set sport_id = 0 where id in (1, 4) and user_id = 1;
--  update records set sport_id = 4 where id = 2 and user_id = 1;


-- UPDATE sport_id * 10 to avoid changing one id twice
ALTER TABLE records MODIFY COLUMN `sport_id` int(11);

-- UPDATE "Correr" whith sport_id of the user
UPDATE records
INNER JOIN sports ON records.user_id = sports.user_id and records.sport_id = 0 and sports.name = 'Correr'
SET records.sport_id = 10 * sports.id;
 
-- UPDATE "Ciclismo" whith sport_id of the user
UPDATE records
INNER JOIN sports ON records.user_id = sports.user_id and records.sport_id = 1 and sports.name = 'Ciclismo'
SET records.sport_id = 10 * sports.id;

-- UPDATE "Andar" whith sport_id of the user
UPDATE records
INNER JOIN sports ON records.user_id = sports.user_id and records.sport_id = 2 and sports.name = 'Andar'
SET records.sport_id = 10 * sports.id;

-- UPDATE "Natación" whith sport_id of the user
UPDATE records
INNER JOIN sports ON records.user_id = sports.user_id and records.sport_id = 3 and sports.name = 'Natación'
SET records.sport_id = 10 * sports.id;

-- UPDATE "Elliptical" whith sport_id of the user
UPDATE records
INNER JOIN sports ON records.user_id = sports.user_id and records.sport_id = 4 and sports.name = 'Elíptica'
SET records.sport_id = 10 * sports.id;

UPDATE records
SET records.sport_id = records.sport_id / 10;


