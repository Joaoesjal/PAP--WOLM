USE pap;

CREATE TABLE pulseiras(
    id INT UNSIGNED NOT NULL,
    id_utente INT UNSIGNED NULL,
    id_unico_esp32 VARCHAR(255) UNIQUE NOT NULL,
    data_registo DATETIME DEFAULT CURRENT_TIMESTAMP,
    associada TINYINT(1) DEFAULT 0
);

ALTER TABLE pulseiras
    ADD CONSTRAINT pulseiras_pk PRIMARY KEY(id);
    
ALTER TABLE pulseiras 
    CHANGE id id INT UNSIGNED NOT NULL AUTO_INCREMENT;



