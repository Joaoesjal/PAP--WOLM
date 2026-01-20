USE pap;
CREATE TABLE localizacoes (
    id INT UNSIGNED NOT NULL,
    id_pulseira INT UNSIGNED NOT NULL,
    latitude DECIMAL(9,6) NOT NULL,
    longitude DECIMAL(9,6) NOT NULL,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE localizacoes
    ADD CONSTRAINT localizacoes_pk PRIMARY KEY(id);
    
ALTER TABLE localizacoes 
    CHANGE id id INT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE localizacoes
    ADD CONSTRAINT pulseiras_fk_id_pulseira
    FOREIGN KEY (id_pulseira) REFERENCES pulseiras(id);
