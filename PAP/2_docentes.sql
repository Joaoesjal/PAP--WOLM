USE pap;
CREATE TABLE docentes(
    id INT UNSIGNED NOT NULL,
    nome VARCHAR(255) NOT NULL,
    data_nascimento DATETIME NOT NULL,
    num_telefone SMALLINT NOT NULL,
    email VARCHAR(255) NOT NULL,
    morada VARCHAR(255) NOT NULL
);
ALTER TABLE docentes
    ADD CONSTRAINT docentes_pk PRIMARY KEY(id);
ALTER TABLE docentes CHANGE id
    id INT UNSIGNED NOT NULL AUTO_INCREMENT;