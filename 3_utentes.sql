USE pap;

CREATE TABLE utentes(
    id INT UNSIGNED NOT NULL,
    id_cuidador INT UNSIGNED NOT NULL,
    id_pulseira INT UNSIGNED NULL,
    nome VARCHAR(255) NOT NULL,
    data_nascimento DATE NOT NULL,
    peso DOUBLE(5,2) NOT NULL,
    altura DOUBLE(3,2)
);

ALTER TABLE utentes
    ADD CONSTRAINT utentes_pk PRIMARY KEY(id);

ALTER TABLE utentes 
    CHANGE id id INT UNSIGNED NOT NULL AUTO_INCREMENT;

 ALTER TABLE utentes
    ADD CONSTRAINT cuidadores_fk_id_cuidador
    FOREIGN KEY (id_cuidador) REFERENCES cuidadores(id);

 ALTER TABLE utentes
    ADD CONSTRAINT pulseiras_fk_id_pulseira
    FOREIGN KEY (id_pulseira) REFERENCES pulseiras(id);