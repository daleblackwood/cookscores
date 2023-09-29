source ./dbutils.sh

run_query "
    CREATE TABLE foxtrot_scores (
        id SERIAL PRIMARY KEY NOT NULL,
        pid VARCHAR(100) NOT NULL,
        seed INT NOT NULL,
        levelId varchar(100) NOT NULL,
        total INT NOT NULL,
        time INT NOT NULL,
        stats TEXT NOT NULL,
        ts timestamp without time zone default (now() at time zone 'utc')
    );
    CREATE TABLE foxtrot_replays (
        scoreId INT PRIMARY KEY NOT NULL,
        replay TEXT NOT NULL,
        CONSTRAINT fk_scoreId FOREIGN KEY (scoreId)
            REFERENCES foxtrot_scores (id)
            ON DELETE CASCADE
    );
"