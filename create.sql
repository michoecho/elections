BEGIN;

DROP TABLE IF EXISTS pending_vote;
DROP TABLE IF EXISTS vote;
DROP TABLE IF EXISTS candidature;
DROP TABLE IF EXISTS election;
DROP TABLE IF EXISTS account;

CREATE TABLE account (
    id serial  NOT NULL,
    login varchar(16)  NOT NULL UNIQUE,
    password varchar(256)  NOT NULL,
    category varchar(16)  NOT NULL,
    name varchar(64)  NOT NULL,
    PRIMARY KEY (id),
    CHECK (category in ('admin', 'commission', 'voter'))
);

CREATE TABLE election (
    id serial  NOT NULL,
    name varchar(128)  NOT NULL,
    seats int  NOT NULL,
    filing_deadline timestamp  NOT NULL,
    voting_start timestamp  NOT NULL,
    voting_end timestamp  NOT NULL,
    published boolean  NOT NULL,
    commission_id int  NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (commission_id) REFERENCES account (id),
    CHECK (seats > 0),
    CHECK (filing_deadline < voting_start),
    CHECK (voting_start < voting_end)
);

/* vote_count shall not be updated directly. */
CREATE TABLE candidature (
    election_id int  NOT NULL,
    candidate_id int  NOT NULL,
    vote_count int  NOT NULL,
    PRIMARY KEY (election_id, candidate_id),
    FOREIGN KEY (election_id) REFERENCES election (id),
    FOREIGN KEY (candidate_id) REFERENCES account (id)
);

/* This table shall not be updated directly. */
CREATE TABLE vote (
    election_id int  NOT NULL,
    voter_id int  NOT NULL,
    PRIMARY KEY (election_id, voter_id),
    FOREIGN KEY (election_id) REFERENCES election (id),
    FOREIGN KEY (voter_id) REFERENCES account (id)
);

/* All votes shall be inserted into the database via this table.
   It will perform necessary checks on the votes, update "vote" and "candidature"
   accordingly, and then clean itself. */
CREATE TABLE pending_vote (
    election_id int NOT NULL,
    candidate_id int NOT NULL,
    voter_id int NOT NULL,
    FOREIGN KEY (election_id, candidate_id) REFERENCES candidature (election_id, candidate_id),
    FOREIGN KEY (voter_id) REFERENCES account (id),
    PRIMARY KEY (candidate_id, election_id, voter_id)
);

/* Validate the votes and increment vote counts. */
CREATE OR REPLACE FUNCTION process_pending_vote() RETURNS TRIGGER AS $$
BEGIN
    IF EXISTS (SELECT voter_id, election_id, seats
    FROM pending_vote JOIN election ON election.id = election_id
    GROUP BY voter_id, election_id, seats
    HAVING count(*) > seats) THEN
        RAISE EXCEPTION 'Too many votes in one election by a single voter.';
    END IF;
 
    INSERT INTO vote SELECT DISTINCT election_id, voter_id FROM pending_vote;
 
    UPDATE candidature c
    SET vote_count = 
        vote_count +
        (SELECT count(*)
        FROM pending_vote pv
        WHERE pv.election_id = c.election_id AND pv.candidate_id = c.candidate_id);
 
    DELETE FROM pending_vote;
    RETURN NULL;
END
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS pending_vote_processor ON pending_vote;
CREATE TRIGGER pending_vote_processor
AFTER INSERT
ON pending_vote
FOR EACH STATEMENT
EXECUTE PROCEDURE process_pending_vote();

/* Disallow adding votes outside of voting time. */
CREATE OR REPLACE FUNCTION check_vote_time() RETURNS TRIGGER AS $$
BEGIN
    IF (SELECT CURRENT_TIMESTAMP < voting_start OR voting_end < CURRENT_TIMESTAMP
    FROM election WHERE election.id = NEW.election_id) THEN
        RAISE EXCEPTION 'Voting closed.';
    END IF;
    RETURN NULL;
END
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS vote_time_checker ON pending_vote;
CREATE TRIGGER vote_time_checker
AFTER INSERT
ON vote 
FOR EACH ROW
EXECUTE PROCEDURE check_vote_time();

/* Disallow candidate registation after the deadline. */
CREATE OR REPLACE FUNCTION check_registration_time() RETURNS TRIGGER AS $$
BEGIN
    IF (SELECT CURRENT_TIMESTAMP < filing_deadline
    FROM election WHERE election.id = NEW.election_id) THEN
        RAISE EXCEPTION 'Registration closed.';
    END IF;
    RETURN NULL;
END
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS registration_time_checker ON vote;
CREATE TRIGGER registration_time_checker
AFTER INSERT
ON vote 
FOR EACH ROW
EXECUTE PROCEDURE check_registration_time();

/* Check that every election candidate is a voter account. */
CREATE OR REPLACE FUNCTION check_candidate_category() RETURNS TRIGGER AS $$
BEGIN
    IF (SELECT category != 'voter'
    FROM account WHERE account.id = NEW.candidate_id) THEN
        RAISE EXCEPTION 'Only a voter can become a candidate.';
    END IF;
    RETURN NULL;
END
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS candidate_category_checker ON candidature;
CREATE TRIGGER candidate_category_checker
AFTER INSERT
ON candidature 
FOR EACH ROW
EXECUTE PROCEDURE check_candidate_category();

/* This application MUST include at least one stored function besides triggers. */
CREATE OR REPLACE FUNCTION publish(int) RETURNS void AS $$
    UPDATE election SET published = 't' WHERE id = $1;
$$ LANGUAGE SQL;

COMMIT;
