ALTER DATABASE property CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- === COUNTER TABLES ===
CREATE TABLE CityPropertyCounter (
    city VARCHAR(20) PRIMARY KEY,
    counter INT UNSIGNED NOT NULL DEFAULT 0
);

CREATE TABLE StaffCounter (
    prefix CHAR(1) PRIMARY KEY,
    counter INT UNSIGNED NOT NULL DEFAULT 0
);

CREATE TABLE ClientCounter (
    prefix CHAR(2) PRIMARY KEY,
    counter INT UNSIGNED NOT NULL DEFAULT 0
);

CREATE TABLE OwnerCounter (
    prefix CHAR(2) PRIMARY KEY,
    counter INT UNSIGNED NOT NULL DEFAULT 0
);

-- Insert base prefixes
INSERT INTO StaffCounter (prefix, counter) VALUES ('A', 0);
INSERT INTO ClientCounter (prefix, counter) VALUES ('CR', 0);
INSERT INTO OwnerCounter (prefix, counter) VALUES ('CO', 0);

-- === MAIN TABLES ===

CREATE TABLE Branch (
    branchNo CHAR(4) NOT NULL,
    street VARCHAR(25) NOT NULL DEFAULT '',
    city VARCHAR(20) NOT NULL DEFAULT '',
    postcode CHAR(7) NOT NULL DEFAULT '',
    PRIMARY KEY (branchNo),
    CONSTRAINT branchNo_format CHECK (branchNo REGEXP '^B[0-9]{3}$')
);

CREATE TABLE Staff (
    staffNo CHAR(4) NOT NULL,
    fName VARCHAR(50) NOT NULL DEFAULT '',
    lName VARCHAR(50) NOT NULL DEFAULT '',
    email VARCHAR(100) NOT NULL,
    password VARCHAR(256) NOT NULL,
    sPosition VARCHAR(15) NOT NULL DEFAULT '',
    sex CHAR(1),
    DOB DATE,
    salary INT,
    branchNo CHAR(4) NOT NULL,
    PRIMARY KEY (staffNo),
    FOREIGN KEY (branchNo)
        REFERENCES Branch (branchNo)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'),
    CONSTRAINT staffNo_format CHECK (staffNo REGEXP '^S[A-Za-z][0-9]{2}$')
);

CREATE TABLE PrivateOwner (
    ownerNo CHAR(4) NOT NULL,
    fName VARCHAR(50) NOT NULL DEFAULT '',
    lName VARCHAR(50) NOT NULL DEFAULT '',
    password VARCHAR(256) NOT NULL,
    street VARCHAR(25) NOT NULL DEFAULT '',
    city VARCHAR(20) NOT NULL DEFAULT '',
    postcode CHAR(7) NOT NULL DEFAULT '',
    telNo VARCHAR(14) NOT NULL,
    eMail VARCHAR(50) NOT NULL,
    PRIMARY KEY (ownerNo),
    INDEX (lName),
    INDEX (postcode),
    CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'),
    CONSTRAINT ownerNo_format CHECK (ownerNo REGEXP '^CO[0-9]{2}$')
);

CREATE TABLE PropertyForRent (
    propertyNo CHAR(4) NOT NULL,
    street VARCHAR(25) NOT NULL DEFAULT '',
    city VARCHAR(20) NOT NULL DEFAULT '',
    postcode CHAR(7) NOT NULL DEFAULT '',
    pType VARCHAR(18) NOT NULL DEFAULT ' ',
    rooms TINYINT UNSIGNED,
    rent SMALLINT UNSIGNED,
    ownerNo CHAR(4) NOT NULL,
    staffNo CHAR(4),
    branchNo CHAR(4) NOT NULL,
    PRIMARY KEY (propertyNo),
    FOREIGN KEY (ownerNo)
        REFERENCES PrivateOwner (ownerNo)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (staffNo)
        REFERENCES Staff (staffNo)
        ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (branchNo)
        REFERENCES Branch (branchNo)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE PropertyImage (
    propertyNo CHAR(4) NOT NULL,
    image VARCHAR(64) NOT NULL DEFAULT ' ',
    PRIMARY KEY (propertyNo, image),
    FOREIGN KEY (propertyNo)
        REFERENCES PropertyForRent (propertyNo)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE CClient (
    clientNo CHAR(4) NOT NULL,
    fName VARCHAR(50) NOT NULL DEFAULT '',
    lName VARCHAR(50) NOT NULL DEFAULT '',
    password VARCHAR(256) NOT NULL,
    telNo VARCHAR(14) NOT NULL,
    prefType VARCHAR(18) NOT NULL DEFAULT ' ',
    maxRent SMALLINT UNSIGNED,
    eMail VARCHAR(50) NOT NULL,
    PRIMARY KEY (clientNo),
    INDEX (lName),
    CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'),
    CONSTRAINT clientNo_format CHECK (clientNo REGEXP '^CR[0-9]{2}$')
);

CREATE TABLE Viewing (
    clientNo CHAR(4) NOT NULL,
    propertyNo CHAR(4) NOT NULL,
    viewDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    vComment MEDIUMTEXT,
    PRIMARY KEY (clientNo, propertyNo, viewDate),
    FOREIGN KEY (clientNo)
        REFERENCES CClient (clientNo)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (propertyNo)
        REFERENCES PropertyForRent (propertyNo)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Registration (
    clientNo CHAR(4) NOT NULL,
    branchNo CHAR(4),
    staffNo CHAR(4),
    dateJoined DATE NOT NULL,
    PRIMARY KEY (clientNo),
    FOREIGN KEY (clientNo)
        REFERENCES CClient (clientNo)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (branchNo)
        REFERENCES Branch (branchNo)
        ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (staffNo)
        REFERENCES Staff (staffNo)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- === TRIGGERS ===

DELIMITER //

CREATE TRIGGER before_insert_property
BEFORE INSERT ON PropertyForRent
FOR EACH ROW
BEGIN
    DECLARE cityInitial CHAR(1);
    DECLARE newCounter INT;
    DECLARE formattedNo CHAR(2);

    SET cityInitial = UPPER(LEFT(NEW.city, 1));

    INSERT INTO CityPropertyCounter (city, counter)
    VALUES (NEW.city, 0)
    ON DUPLICATE KEY UPDATE counter = counter + 1;

    SELECT counter INTO newCounter FROM CityPropertyCounter WHERE city = NEW.city;

    SET formattedNo = LPAD(newCounter, 2, '0');

    SET NEW.propertyNo = CONCAT('P', cityInitial, formattedNo);
END;
//

CREATE TRIGGER before_insert_Staff
BEFORE INSERT ON Staff
FOR EACH ROW
BEGIN
    DECLARE newCounter INT;
    DECLARE randomAlphabet CHAR(1);
    SET randomAlphabet = CHAR(FLOOR(65 + (RAND() * 26))); -- Random Alphabet from A-Z
    
    UPDATE StaffCounter SET counter = counter + 1 WHERE prefix = randomAlphabet;
    SELECT counter INTO newCounter FROM StaffCounter WHERE prefix = randomAlphabet;

    SET NEW.staffNo = CONCAT('S', randomAlphabet, LPAD(newCounter, 2, '0'));
END;
//

CREATE TRIGGER before_insert_PrivateOwner
BEFORE INSERT ON PrivateOwner
FOR EACH ROW
BEGIN
    DECLARE newCounter INT;
    SET @prefix := 'CO';

    UPDATE OwnerCounter SET counter = counter + 1 WHERE prefix = @prefix;
    SELECT counter INTO newCounter FROM OwnerCounter WHERE prefix = @prefix;

    SET NEW.ownerNo = CONCAT(@prefix, LPAD(newCounter, 2, '0'));
END;
//

CREATE TRIGGER before_insert_CClient
BEFORE INSERT ON CClient
FOR EACH ROW
BEGIN
    DECLARE newCounter INT;
    SET @prefix := 'CR';

    UPDATE ClientCounter SET counter = counter + 1 WHERE prefix = @prefix;
    SELECT counter INTO newCounter FROM ClientCounter WHERE prefix = @prefix;

    SET NEW.clientNo = CONCAT(@prefix, LPAD(newCounter, 2, '0'));
END;
//

DELIMITER ;
