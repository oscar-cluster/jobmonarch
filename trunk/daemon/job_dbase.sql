DROP TABLE jobs CASCADE;
DROP TABLE nodes CASCADE;
DROP TABLE job_nodes CASCADE;

CREATE TABLE jobs (
	job_id			INT NOT NULL UNIQUE PRIMARY KEY,
	job_name		VARCHAR(100),
	job_queue		VARCHAR(50),
	job_owner		VARCHAR(30),
	job_requested_time	VARCHAR(10),
	job_requested_memory	VARCHAR(10),
	job_ppn			INT,
	job_status		VARCHAR(2),
	job_start_timestamp	VARCHAR(10),
	job_stop_timestamp	VARCHAR(10)
);

CREATE TABLE nodes (
	node_id			SERIAL NOT NULL UNIQUE PRIMARY KEY,
	node_hostname		VARCHAR(100)
);

CREATE TABLE job_nodes (
	job_id			INT NOT NULL REFERENCES jobs ON DELETE CASCADE,
	node_id			INT NOT NULL REFERENCES nodes ON DELETE CASCADE,
	PRIMARY KEY ( job_id, node_id )
);

CREATE USER root;
CREATE USER ramon;

GRANT ALL ON jobs,nodes,job_nodes TO root,ramon;
GRANT ALL ON nodes_node_id_seq TO root,ramon;
