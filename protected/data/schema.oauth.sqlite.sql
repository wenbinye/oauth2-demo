CREATE TABLE oauth_access_tokens (access_token TEXT, client_id TEXT, user_id TEXT, expires TIMESTAMP, scope TEXT);
CREATE TABLE oauth_authorization_codes (authorization_code TEXT, client_id TEXT, user_id TEXT, redirect_uri TEXT, expires TIMESTAMP, scope TEXT);
CREATE TABLE oauth_clients (client_id TEXT, client_secret TEXT, redirect_uri TEXT);
CREATE TABLE oauth_refresh_tokens (refresh_token TEXT, client_id TEXT, user_id TEXT, expires TIMESTAMP, scope TEXT);
INSERT INTO oauth_clients (client_id, client_secret, redirect_uri)
VALUES ('1362053493', '900150983cd24fb0d6963f7d28e17f72', 'http://ruyi.taobao.com');
