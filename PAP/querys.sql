
##DOCENTES##
INSERT INTO docentes (nome, data_nascimento, num_telefone, email, morada) VALUES
('Maria Silva', '1975-04-12', '912345678', 'maria.silva@escola.pt', 'Rua das Flores, Lisboa'),
('João Pereira', '1980-09-23', '913567890', 'joao.pereira@escola.pt', 'Avenida Central, Porto'),
('Ana Costa', '1969-01-15', '914678901', 'ana.costa@escola.pt', 'Rua do Carmo, Coimbra');


##DOENTES##
INSERT INTO doentes (nome, data_nascimento, num_telefone, peso) VALUES
('Diogo Martins', '2008-03-10', '931234567', 74.50),
('Sofia Alves', '1995-07-22', '932345678', 62.30),
('Carlos Rocha', '1982-11-05', '933456789', 85.10);


##MEDICAMENTOS##
INSERT INTO medicamentos (dose, data_hora) VALUES
('500mg Paracetamol', '2025-10-02 08:00:00'),
('20mg Omeprazol', '2025-10-02 09:00:00'),
('10mg Loratadina', '2025-10-02 10:30:00');


##NOTIFICACOES##
INSERT INTO notificacoes (id_medicamento, data_hora) VALUES
(1, '2025-10-02 07:55:00'),
(2, '2025-10-02 08:55:00'),
(3, '2025-10-02 10:25:00');


##PULSEIRAS##
INSERT INTO pulseiras (id_doente, id_notificacao) VALUES
(1, 1),
(2, 2),
(3, 3);



