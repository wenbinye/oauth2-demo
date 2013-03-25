CREATE TABLE oauth_clients (
client_id bigint primary key,
client_secret varchar(40),
redirect_uri text
) DEFAULT CHARSET=UTF8;

CREATE TABLE oauth_access_tokens (
access_token varchar(40) primary key,
client_id bigint,
user_id varchar(255),
expires datetime,
scope text
) DEFAULT CHARSET=UTF8;

CREATE TABLE oauth_authorization_codes (
authorization_code varchar(40) primary key,
client_id bigint,
user_id varchar(255),
redirect_uri text,
expires datetime,
scope text
) DEFAULT CHARSET=UTF8;

CREATE TABLE oauth_refresh_tokens (
refresh_token varchar(40) primary key,
client_id bigint,
user_id varchar(255),
expires datetime,
scope text
) DEFAULT CHARSET=UTF8;
