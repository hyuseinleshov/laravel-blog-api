CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "tags"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "tags_name_unique" on "tags"("name");
CREATE TABLE IF NOT EXISTS "post_tag"(
  "post_id" integer not null,
  "tag_id" integer not null,
  foreign key("post_id") references "posts"("id") on delete cascade,
  foreign key("tag_id") references "tags"("id") on delete cascade,
  primary key("post_id", "tag_id")
);
CREATE TABLE IF NOT EXISTS "authors"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "password" varchar not null,
  "email_verified_at" datetime,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "authors_email_unique" on "authors"("email");
CREATE TABLE IF NOT EXISTS "personal_access_tokens"(
  "id" integer primary key autoincrement not null,
  "tokenable_type" varchar not null,
  "tokenable_id" integer not null,
  "name" text not null,
  "token" varchar not null,
  "abilities" text,
  "last_used_at" datetime,
  "expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" on "personal_access_tokens"(
  "tokenable_type",
  "tokenable_id"
);
CREATE UNIQUE INDEX "personal_access_tokens_token_unique" on "personal_access_tokens"(
  "token"
);
CREATE INDEX "personal_access_tokens_expires_at_index" on "personal_access_tokens"(
  "expires_at"
);
CREATE TABLE IF NOT EXISTS "posts"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "content" text not null,
  "status" varchar not null default('draft'),
  "author_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("author_id") references "authors"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "subscriptions"(
  "id" integer primary key autoincrement not null,
  "author_id" integer not null,
  "plan" varchar not null,
  "status" varchar not null,
  "valid_from" datetime not null,
  "valid_to" datetime,
  "stripe_payment_intent_id" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("author_id") references "authors"("id") on delete cascade
);
CREATE INDEX "subscriptions_author_id_index" on "subscriptions"("author_id");
CREATE INDEX "subscriptions_author_id_status_index" on "subscriptions"(
  "author_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "transactions"(
  "id" integer primary key autoincrement not null,
  "author_id" integer not null,
  "subscription_id" integer,
  "stripe_payment_id" varchar not null,
  "amount" integer not null,
  "currency" varchar not null default 'eur',
  "plan" varchar not null,
  "status" varchar not null,
  "metadata" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("author_id") references "authors"("id") on delete cascade,
  foreign key("subscription_id") references "subscriptions"("id") on delete cascade
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_12_09_074205_create_posts_table',1);
INSERT INTO migrations VALUES(5,'2025_12_09_074324_create_tags_table',1);
INSERT INTO migrations VALUES(6,'2025_12_09_074807_create_post_tag_table',1);
INSERT INTO migrations VALUES(7,'2026_01_13_094307_create_authors_table',1);
INSERT INTO migrations VALUES(8,'2026_01_13_094702_create_personal_access_tokens_table',1);
INSERT INTO migrations VALUES(9,'2026_01_13_095259_change_posts_user_id_to_author_id',1);
INSERT INTO migrations VALUES(10,'2026_01_14_120257_create_subscriptions_table',2);
INSERT INTO migrations VALUES(11,'2026_01_14_120357_create_transactions_table',2);
