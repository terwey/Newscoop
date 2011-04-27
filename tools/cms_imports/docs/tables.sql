CREATE TABLE IF NOT EXISTS ConvRequests (
    id int(10) unsigned NOT NULL AUTO_INCREMENT,
    worker smallint NOT NULL DEFAULT 0,
    state VARCHAR(255) NOT NULL DEFAULT "init",
    email VARCHAR(1024) NOT NULL DEFAULT "",
    format VARCHAR(255) NOT NULL DEFAULT "",
    file VARCHAR(1024) NOT NULL DEFAULT "",
    orig VARCHAR(1024) NOT NULL DEFAULT "",
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY conv_requests_worker (worker)
);


