create table if not exists `user` (
    `id` integer unsigned not null auto_increment,
    `username` varchar(16) not null,
    `password` varchar(128) not null,
    `plain_password` varchar(128) null default null,
    `new_password` varchar(128) null default null,
    `roles` varchar(128) null default null,
    `active` tinyint(1) not null default 1,
    primary key (`id`),
    unique key (`username`)
) engine = innodb;
