CREATE TABLE Branch (
    branchNo CHAR(4) NOT NULL DEFAULT (UUID()),
    street VARCHAR(25) NOT NULL DEFAULT '',
    city VARCHAR(20) NOT NULL DEFAULT '',
    postcode CHAR(7) NOT NULL DEFAULT '',
    PRIMARY KEY (branchNo)
);

CREATE TABLE Staff (
    staffNo CHAR(4) NOT NULL DEFAULT (UUID()),
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
    CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')
);
 
CREATE TABLE PrivateOwner (
    ownerNo CHAR(4) NOT NULL DEFAULT (UUID()),
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
    CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')
);

CREATE TABLE PropertyForRent (
    propertyNo CHAR(4) NOT NULL DEFAULT (UUID()),
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
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (staffNo)
        REFERENCES Staff (staffNo)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    FOREIGN KEY (branchNo)
        REFERENCES Branch (branchNo)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);   

CREATE TABLE PropertyImage (
    propertyNo CHAR(4) NOT NULL,
    image VARCHAR(64) NOT NULL DEFAULT ' ',
    PRIMARY KEY (propertyNo, image),
    FOREIGN KEY (propertyNo)
        REFERENCES PropertyForRent (propertyNo)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE CClient (
    clientNo CHAR(4) NOT NULL DEFAULT (UUID()),
    fName VARCHAR(50) NOT NULL DEFAULT '',
    lName VARCHAR(50) NOT NULL DEFAULT '',
    password VARCHAR(256) NOT NULL,
    telNo VARCHAR(14) NOT NULL,
    prefType VARCHAR(18) NOT NULL DEFAULT ' ',
    maxRent SMALLINT UNSIGNED,
    eMail VARCHAR(50) NOT NULL,
    PRIMARY KEY (clientNo),
    INDEX (lName),
    CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')
);
 
CREATE TABLE Viewing (
    clientNo CHAR(4) NOT NULL,
    propertyNo CHAR(4) NOT NULL,
    viewDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    vComment MEDIUMTEXT,
    PRIMARY KEY (clientNo , propertyNo , viewDate),
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
    staffNo CHAR(4) ,
    dateJoined DATE NOT NULL,
    PRIMARY KEY (clientNo),
    FOREIGN KEY (clientNo)
        REFERENCES CClient (clientNo)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (branchNo)
        REFERENCES Branch (branchNo)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    FOREIGN KEY (staffNo)
        REFERENCES Staff (staffNo)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);
 
