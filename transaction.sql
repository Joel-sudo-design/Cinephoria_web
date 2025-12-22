USE cinephoria;
SET NAMES utf8mb4;

START TRANSACTION;

SET @last_wednesday = DATE_SUB(
  CURDATE(),
  INTERVAL (CASE WHEN DAYOFWEEK(CURDATE()) = 4 THEN 7 ELSE (DAYOFWEEK(CURDATE()) + 3) % 7 END) DAY
);
SET @last_wednesday_plus6 = DATE_ADD(@last_wednesday, INTERVAL 6 DAY);
SET @end_of_year = DATE(CONCAT(YEAR(CURDATE()), '-12-31'));

INSERT INTO cinema (id, name) VALUES
                                  (1, 'Toulouse'),
                                  (2, 'Nantes'),
                                  (3, 'Bordeaux'),
                                  (4, 'Lille'),
                                  (5, 'Charleroi'),
                                  (6, 'Liège'),
                                  (7, 'Paris');

INSERT INTO genre (id, name) VALUES
                                 (1, 'Action'),
                                 (2, 'Comédie'),
                                 (3, 'Horreur'),
                                 (4, 'Science-fiction'),
                                 (5, 'Romance'),
                                 (6, 'Thriller'),
                                 (7, 'Drame'),
                                 (8, 'Animation');

INSERT INTO salle (id, qualite, places) VALUES
                                            (1, '3DX', 100),
                                            (2, '4DX', 100),
                                            (3, 'IMAX', 100),
                                            (4, 'Dolby', 100);

INSERT INTO film (
    id, genre_id, name, description, date_debut, date_fin, label, age_minimum, image_name, updated_at
)
VALUES
    (1, 2, 'Ghosted', 'Lorsque Cole tombe follement amoureux de la mystérieuse Sadie, il est loin de se douter qu''elle est en réalité un agent secret. Alors que Cole est bien décidé à revoir Sadie, ils sont soudain entraînés dans une aventure pour sauver le monde',
     @last_wednesday, @end_of_year,
     0, '12', 'ghosted.jpg', NOW()),

    (2, 1, 'Avatar : la voie de l''eau', 'Se déroulant plus d’une décennie après les événements relatés dans le premier film, AVATAR : LA VOIE DE L’EAU raconte l''histoire des membres de la famille Sully (Jake, Neytiri et leurs enfants), les épreuves auxquelles ils sont confrontés, les chemins qu’ils doivent emprunter pour se protéger les uns les autres, les batailles qu’ils doivent mener pour rester en vie et les tragédies qu''ils endurent.',
     @last_wednesday, @end_of_year,
     1, '12', 'avatar.jpg', NOW()),

    (3, 1, 'Deadpool & Wolverine', 'Après avoir échoué à rejoindre l’équipe des Avengers, Wade Wilson passe d’un petit boulot à un autre sans vraiment trouver sa voie. Jusqu’au jour où un haut gradé du Tribunal des Variations Anachroniques lui propose une mission digne de lui… à condition de voir son monde et tous ceux qu’il aime être anéantis. Refusant catégoriquement, Wade endosse de nouveau le costume de Deadpool et tente de convaincre Wolverine de l’aider à sauver son univers…',
     @last_wednesday, @end_of_year,
     0, '12', 'dead.jpg', NOW()),

    (4, 7, 'Le Comte de Monte-Cristo', 'Victime d’un complot, le jeune Edmond Dantès est arrêté le jour de son mariage pour un crime qu’il n’a pas commis. Après quatorze ans de détention au château d’If, il parvient à s’évader. Devenu immensément riche, il revient sous l’identité du comte de Monte-Cristo pour se venger des trois hommes qui l’ont trahi.',
     @last_wednesday, @end_of_year,
     1, '12', 'comte.webp', NOW()),

    (5, 6, 'Killer Elite', 'Pour sauver Hunter, son ancien partenaire et mentor, Danny accepte de reprendre du service et de reformer son équipe. Mais celui qui était l’un des meilleurs agents des forces spéciales va cette fois affronter sa mission la plus périlleuse. Pour réussir, il va devoir percer les secrets d’une des unités militaires les plus redoutées qui soit, le SAS britannique. De doubles jeux en trahisons, il va découvrir un complot qui menace le monde et ce pour quoi il s’est toujours battu…',
     @last_wednesday, @end_of_year,
     0, '16', 'killer.jpg', NOW()),

    (6, 6, 'Ant-Man et la Guêpe : Quantumania', 'Le duo de super-héros Scott Lang (Paul Rudd) et Hope Van Dyne (Evangeline Lilly) revient pour de nouvelles aventures de Ant-Man et la Guêpe. Avec les parents de Hope - Hank Pym (Michael Douglas) et Janet Van Dyne (Michelle Pfeiffer), ainsi que Cassie Lang (Kathryn Newton), la fille de Scott, la famille va devoir explorer la Dimension Subatomique et côtoyer d''étranges nouvelles créatures, dans un périple qui les conduira bien au-delà de ce que tous croyaient possible. « Ant-Man et la Guêpe : Quantumania » est réalisé par Peyton Reed et produit par Kevin Feige, p.g.a., et Stephen Broussard, p.g.a., avec Jonathan Majors dans le rôle de Kang.',
     @last_wednesday, @end_of_year,
     0, '', 'ant-man.jpg', NOW()),

    (7, 1, 'Black Panther: Wakanda Forever', 'La Reine Ramonda, Shuri, M’Baku, Okoye et les Dora Milaje luttent pour protéger leur nation des ingérences d’autres puissances mondiales après la mort du roi T’Challa. Alors que le peuple s’efforce d’aller de l’avant, nos héros vont devoir s’unir et compter sur l’aide de la mercenaire Nakia et d’Everett Ross pour faire entrer le royaume du Wakanda dans une nouvelle ère. Mais une terrible menace surgit d’un royaume caché au plus profond des océans : Talokan.',
     @last_wednesday, @end_of_year,
     0, '', 'black.jpg', NOW()),

    (8, 5, 'Mariage en otage', 'Un couple décide de se marier dans un endroit exotique loin de la civilisation mais cet espace paradisiaque est sous la coupe de criminels. En protégeant leurs familles respectives, les tourtereaux redécouvrent pourquoi ils tenaient tant à se marier.',
     @last_wednesday, @end_of_year,
     0, '12', 'mariage.jpg', NOW()),

    (9, 8, 'Le Chat Potté', 'C’était bien avant que notre mythique Chat Potté ne croise la route de Shrek… Le légendaire félin, et non moins redoutable amant, s’était alors embarqué dans un périple riche en rebondissements, avec la ravissante et rusée Kitty Pattes de Velours et Humpty Alexandre Dumpty, véritable "cerveau" de l’opération. Leur objectif : s’emparer de la fameuse Oie aux Œufs d’Or pour sauver la ville où le Chat Potté a grandi. Voici l’histoire véridique du Chat, du Mythe, de la Légende et… des Bottes !',
     @last_wednesday, @end_of_year,
     0, '12', 'chat.jpg', NOW()),

    (10, 4, 'Godzilla vs Kong', 'À une époque où les monstres parcourent la Terre, et alors que l’humanité lutte pour son avenir, Godzilla et King Kong, les deux forces les plus puissantes de la nature, entrent en collision dans une bataille spectaculaire inédite. Alors que Monarch se lance dans une mission périlleuse en terrain inconnu, et qu’il découvre des indices sur les origines des Titans, un complot humain menace d’éradiquer ces créatures – qu’elles soient bonnes ou mauvaises – de la surface de la planète.',
     @last_wednesday, @end_of_year,
     0, '12', 'god.webp', NOW()),

    (11, 7, 'Joker', 'Le film, qui relate une histoire originale inédite sur grand écran, se focalise sur la figure emblématique de l’ennemi juré de Batman. Il brosse le portrait d’Arthur Fleck, un homme sans concession méprisé par la société.',
     @last_wednesday, @end_of_year,
     1, '12', 'joker.jpg', NOW()),

    (12, 3, 'M3GAN', 'M3GAN est un miracle technologique, une cyber poupée dont l’intelligence artificielle est programmée pour être la compagne idéale des enfants et la plus sûre alliée des parents. Conçue par Gemma, la brillante roboticienne d’une entreprise de jouets, M3GAN peut écouter, observer et apprendre tout en étant à la fois l’amie et le professeur, la camarade de jeu et la protectrice de l’enfant à qui elle est liée. Quand Gemma devient tout à coup responsable de sa nièce de 8 ans, Cady, dont les parents sont soudainement décédés, elle n’est absolument pas prête à assumer son rôle. Débordée et sous pression au travail, elle décide de lier le prototype M3GAN encore en développement à la petite fille, dans une tentative désespérée de résoudre ses problèmes sur ces deux fronts. Une décision qui va entraîner d’épouvantables conséquences.',
     @last_wednesday, @end_of_year,
     0, '12', 'm3gan.webp', NOW()),

    (13, 3, 'Les Guetteurs', 'Perdue dans une forêt, Mina trouve refuge dans une maison déjà occupée par trois personnes. Elle va alors découvrir les règles de ce lieu très secret : chaque nuit, les habitants doivent se laisser observer par les mystérieux occupants de cette forêt. Ils ne peuvent pas les voir, mais eux regardent tout.',
     @last_wednesday, @end_of_year,
     0, '16', 'guetteurs.webp', NOW()),

    (14, 8, 'Kung Fu Panda 4', 'Après trois aventures inoubliables en tant que Guerrier Dragon, le destin va de nouveau frapper à la porte de Po… l''invitant cette fois à prendre la relève d''Oogway pour devenir le Maître Spirituel de la Vallée de la Paix ! Un défi de taille pour le panda, qui n''a absolument aucune idée de ce qui l''attend. Surtout qu''il lui faudra d''abord trouver un nouveau Guerrier Dragon.',
     @last_wednesday, @end_of_year,
     0, '', 'panda.jpg', NOW());

INSERT INTO film_cinema (film_id, cinema_id) VALUES (1, 1), (1, 2), (1, 3), (2, 2), (2, 4), (3, 1), (3, 3), (3, 5), (4, 1), (4, 4), (5, 2), (5, 5), (5, 6), (6, 1), (6, 6), (7, 2), (7, 7), (8, 3), (8, 6), (9, 4), (9, 7), (10, 1), (10, 5), (11, 3), (11, 7), (12, 2), (12, 4), (13, 5), (13, 6), (14, 1), (14, 7);

INSERT INTO seance (heure_debut, heure_fin, price, salle_id, `date`, film_id)
SELECT r.heure_debut,
       r.heure_fin,
       r.price,
       r.salle_id,
       DATE_ADD(f.date_debut, INTERVAL n.day_offset DAY),
       f.id
FROM film f
         JOIN (
    SELECT (a.n + b.n*10 + c.n*100) AS day_offset
    FROM (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
          UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a
             CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                         UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b
             CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                         UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) c
) n
         JOIN (
    SELECT 1 AS salle_id, '08:00:00' AS heure_debut, '10:00:00' AS heure_fin, 10 AS price
    UNION ALL SELECT 2, '10:30:00','12:30:00',12
    UNION ALL SELECT 3, '14:00:00','16:00:00',15
    UNION ALL SELECT 4, '19:00:00','21:00:00',12
) r
         LEFT JOIN seance s
                   ON s.`date`   = DATE_ADD(f.date_debut, INTERVAL n.day_offset DAY)
                       AND s.salle_id = r.salle_id
                       AND s.film_id  = f.id
WHERE n.day_offset <= DATEDIFF(f.date_fin, f.date_debut)
  AND s.id IS NULL;

INSERT INTO seance_cinema (seance_id, cinema_id)
SELECT s.id, fc.cinema_id
FROM seance s
         JOIN film_cinema fc ON fc.film_id = s.film_id
         LEFT JOIN seance_cinema sc
                   ON sc.seance_id = s.id AND sc.cinema_id = fc.cinema_id
WHERE sc.seance_id IS NULL
  AND s.`date` BETWEEN @last_wednesday AND @end_of_year;

COMMIT;
