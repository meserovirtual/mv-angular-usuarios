# USUARIOS
CREATE TABLE usuarios (
  usuario_id       INT(11)      NOT NULL AUTO_INCREMENT,
  nombre           VARCHAR(100) NOT NULL,
  apellido         VARCHAR(100) NOT NULL,
  mail             VARCHAR(100) NOT NULL,
  nacionalidad_id  INT(11)               DEFAULT NULL,
  tipo_doc         INT(11)      NOT NULL,
  nro_doc          VARCHAR(20)  NOT NULL,
  comentarios      VARCHAR(450)          DEFAULT NULL,
  marcado          VARCHAR(8)            DEFAULT NULL,
  telefono         VARCHAR(45)           DEFAULT NULL,
  fecha_nacimiento VARCHAR(45)           DEFAULT NULL,
  profesion_id     INT(11)               DEFAULT NULL,
  saldo            VARCHAR(8)   NOT NULL DEFAULT '0.0',
  password         VARCHAR(100)          DEFAULT NULL,
  rol_id           INT(11)      NOT NULL DEFAULT '0', -- TODO: Just for now: 0 - Admin; 1 - Usuario; 2 - Proveedor; 3 - Cliente
  news_letter      INT(1)                DEFAULT NULL,
  cbu              VARCHAR(20)           DEFAULT NULL,
  social_login     INT(1)                DEFAULT 0
  COMMENT 'Especifica si utiliza una cuenta social para logearse | 0: no, 1:google, 2:facebook',
  modified         TIMESTAMP             DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  status           INT(1)                DEFAULT '1',
  cta_cte          INT(1)                DEFAULT '0',
  PRIMARY KEY (usuario_id),
  UNIQUE KEY 'mail' ('mail')
)
  ENGINE = MyISAM
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8;

# DIRECCIONES
CREATE TABLE direcciones (
  direccion_id INT(11)      NOT NULL AUTO_INCREMENT,
  usuario_id   INT(11)               DEFAULT NULL,
  calle        VARCHAR(150) NOT NULL,
  nro          INT(11)      NOT NULL,
  piso         INT(3)                DEFAULT NULL,
  puerta       VARCHAR(3)            DEFAULT NULL,
  ciudad_id    INT(11)               DEFAULT NULL,
  PRIMARY KEY (direccion_id)
)
  ENGINE = MyISAM
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8;

# HISTORICO DE LOGIN
CREATE TABLE logins (
  login_id    INT(11)     NOT NULL AUTO_INCREMENT,
  usuario_id  INT(11)              DEFAULT NULL,
  sucursal_id VARCHAR(45) NOT NULL,
  caja_id     VARCHAR(45) NOT NULL,
  ok          INT(1)      NOT NULL DEFAULT 0, -- Login no ok == 0 / Login ok == 1
  fecha       TIMESTAMP            DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (login_id)
)
  ENGINE = MyISAM
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8;