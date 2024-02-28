CREATE TABLE scores (
    id SERIAL PRIMARY KEY,
    pid character varying(100) NOT NULL,
    seed integer NOT NULL,
    levelid character varying(100) NOT NULL,
    total integer NOT NULL,
    "time" integer NOT NULL,
    stats text NOT NULL,
    ts timestamp without time zone DEFAULT timezone('utc', now())
);

CREATE TABLE replays (
    scoreid integer NOT NULL,
    replay text NOT NULL,
    CONSTRAINT fk_scoreid FOREIGN KEY (scoreid) REFERENCES scores(id) ON DELETE CASCADE
);

CREATE TABLE fplayers (
    pid VARCHAR(100) NOT NULL UNIQUE,
    pname VARCHAR(50) NOT NULL,
    email VARCHAR(50) DEFAULT NULL,
    pword VARCHAR(50) DEFAULT NULL
);