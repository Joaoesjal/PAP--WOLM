USE pap;

CREATE TABLE medicamentos(
    id INT UNSIGNED NOT NULL,
    id_utente INT UNSIGNED NOT NULL,
    nome VARCHAR(255) NOT NULL,
    dose VARCHAR(255) NOT NULL,
    data_hora DATETIME NOT NULL
);

ALTER TABLE medicamentos
    ADD CONSTRAINT medicamentos_pk PRIMARY KEY(id);

ALTER TABLE medicamentos 
    CHANGE id id INT UNSIGNED NOT NULL AUTO_INCREMENT;

 ALTER TABLE medicamentos
    ADD CONSTRAINT utentes_fk_id_utente
    FOREIGN KEY (id_utente) REFERENCES utentes(id);
