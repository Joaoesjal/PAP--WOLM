USE pap;
CREATE TABLE pulseiras(
    id INT UNSIGNED NOT NULL,
    id_doente INT UNSIGNED NOT NULL,
    id_notificacao INT UNSIGNED NOT NULL
);
ALTER TABLE pulseiras
    ADD CONSTRAINT pulseiras PRIMARY KEY(id);

ALTER TABLE pulseiras
    ADD CONSTRAINT doentes_fk_id_doente
    Foreign Key (id_doente) REFERENCES  doentes(id);

ALTER TABLE pulseiras
    ADD CONSTRAINT notificacoes_fk_id_notificacao
    Foreign Key (id_notificacao) REFERENCES notificacoes(id);

ALTER TABLE pulseiras CHANGE id
    id INT UNSIGNED NOT NULL AUTO_INCREMENT;
