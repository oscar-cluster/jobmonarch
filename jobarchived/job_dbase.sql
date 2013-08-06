SET client_min_messages TO PANIC;

CREATE TABLE jobs (
	job_id			VARCHAR(20) NOT NULL UNIQUE PRIMARY KEY,
	job_name		VARCHAR(255),
	job_queue		VARCHAR(50),
	job_owner		VARCHAR(50),
	job_requested_time	VARCHAR(10),
	job_requested_memory	VARCHAR(10),
	job_ppn			INT,
	job_status		VARCHAR(2),
	job_start_timestamp	VARCHAR(10),
	job_stop_timestamp	VARCHAR(10)
);

CREATE TABLE nodes (
	node_id			SERIAL NOT NULL UNIQUE PRIMARY KEY,
	node_hostname		VARCHAR(100),
	node_location		VARCHAR(10)
);

CREATE TABLE job_nodes (
	job_id			VARCHAR(20) NOT NULL REFERENCES jobs ON DELETE CASCADE,
	node_id			INT NOT NULL REFERENCES nodes ON DELETE RESTRICT,
	PRIMARY KEY ( job_id, node_id )
);

CREATE USER jobarchive;

-- modify me: set a password
-- ALTER USER jobarchive WITH PASSWORD '';

GRANT ALL ON jobs,nodes,job_nodes TO "jobarchive";
GRANT ALL ON nodes_node_id_seq TO "jobarchive";
