DROP TABLE IF EXISTS guestbook;
CREATE TABLE IF NOT EXISTS guestbook (
  id INTEGER PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  text TEXT NOT NULL,
  datetime DATETIME
);
CREATE INDEX guestbook_datetime ON guestbook (datetime);
