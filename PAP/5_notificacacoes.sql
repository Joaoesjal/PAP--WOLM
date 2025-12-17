USE pap;
CREATE TABLE notificacoes(
    id INT UNSIGNED NOT NULL,
    id_medicamento INT UNSIGNED NOT NULL,
    data_hora DATETIME NOT NULL

);
ALTER TABLE notificacoes
    ADD CONSTRAINT notificacoes PRIMARY KEY(id);

ALTER TABLE notificacoes
    ADD CONSTRAINT medicamentos_fk_id_medicamento
    FOREIGN KEY (id_medicamento) REFERENCES medicamentos(id);

ALTER TABLE notificacoes CHANGE id
    id INT UNSIGNED NOT NULL AUTO_INCREMENT;

