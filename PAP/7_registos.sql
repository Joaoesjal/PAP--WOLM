USE pap;

INSERT INTO cuidadores (nome, email, password) VALUES
('Maria Silva', 'maria@email.com', 'senha123'),
('João Pereira', 'joao@email.com', 'senha456'),
('Ana Fernandes', 'ana@email.com', 'senha789');



INSERT INTO utentes (id_cuidador, id_pulseira, nome, data_nascimento, peso, altura) VALUES
(1, 1, 'Ana Costa', '1945-08-12', 65.50, 1.65),
(2, 2, 'Carlos Sousa', '1950-03-20', 70.00, 1.70),
(1, NULL, 'Joana Lima', '1948-11-05', 60.30, 1.60); 



INSERT INTO medicamentos (id_utente, nome, dose, data_hora) VALUES
(1, 'Paracetamol', '500mg', '2026-01-17 08:00:00'),
(1, 'Ibuprofeno', '200mg', '2026-01-17 12:00:00'),
(2, 'Metformina', '850mg', '2026-01-17 09:00:00');



INSERT INTO pulseiras (id_unico_esp32, id_utente) VALUES
('AA:BB:CC:DD:01', 1),
('AA:BB:CC:DD:02', 2),
('AA:BB:CC:DD:03', NULL);  -- ainda não associada


INSERT INTO localizacoes (id_pulseira, latitude, longitude) VALUES
(1, 37.774929, -122.419416),   -- Pulseira da Ana Costa
(2, 38.907192, -77.036873),    -- Pulseira do Carlos Sousa
(1, 37.775000, -122.418000);   -- nova posição da Ana Costa




