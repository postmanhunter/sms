ALTER TABLE record ADD `send_id` int NULL COMMENT '发送记录id';
ALTER TABLE send_list ADD `success` int NULL COMMENT '成功发送的条数';