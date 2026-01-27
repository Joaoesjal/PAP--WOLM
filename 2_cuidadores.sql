USE pap;

CREATE TABLE cuidadores(
    id INT UNSIGNED NOT NULL,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    foto VARCHAR(255) DEFAULT NULL
);

ALTER TABLE cuidadores
    ADD CONSTRAINT cuidadores_pk PRIMARY KEY(id);

ALTER TABLE cuidadores 
    CHANGE id id INT UNSIGNED NOT NULL AUTO_INCREMENT;
