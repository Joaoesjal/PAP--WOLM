USE pap;
CREATE TABLE medicamentos(
    id INT UNSIGNED NOT NULL,
    doze VARCHAR(255) NOT NULL,
    data_hora DATETIME NOT NULL
);
ALTER TABLE medicamentos
    ADD CONSTRAINT madicamentos_pk PRIMARY KEY(id);
ALTER TABLE medicamentos CHANGE id
    id INT UNSIGNED NOT NULL AUTO_INCREMENT;