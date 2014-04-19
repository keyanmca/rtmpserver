
/*
 * Copyright (C) Roman Arutyunyan
 */


#ifndef _NGX_RTMP_LIVE_H_INCLUDED_
#define _NGX_RTMP_LIVE_H_INCLUDED_


#include <ngx_config.h>
#include <ngx_core.h>
#include "ngx_rtmp.h"
#include "ngx_rtmp_cmd_module.h"
#include "ngx_rtmp_bandwidth.h"
#include "ngx_rtmp_streams.h"


typedef struct ngx_rtmp_live_ctx_s ngx_rtmp_live_ctx_t;
typedef struct ngx_rtmp_live_stream_s ngx_rtmp_live_stream_t;


typedef struct {
    unsigned                            active:1;
    uint32_t                            timestamp;
    uint32_t                            csid;
    uint32_t                            dropped;
} ngx_rtmp_live_chunk_stream_t;

/*
 * live module对应的上下文结构，本质上是个链表的节点，其存储于session的ctx成员中
 * 当收到音视频包时，各个数据结构的配合如下:
 * 1. 首先拿到的与连接相关的唯一的ngx_rtmp_session_t，从其内读取ctx，对应live module数据结构为ngx_rtmp_live_ctx_s
 * 2. 从ngx_rtmp_live_ctx_s中读取stream成员，这个是该publisher创建的stream，数据结构为ngx_rtmp_live_stream_t
 * 3. 从ngx_rtmp_live_stream_t中读取ctx成员，其记录的是subscriber的链表的成员头节点
 * 4. 顺着链表，把所有的音视频数据，推送到各个subscriber即可
 * 
 */
struct ngx_rtmp_live_ctx_s {
    ngx_rtmp_session_t                 *session; // 对应的session
    ngx_rtmp_live_stream_t             *stream; // 对应的stream
    ngx_rtmp_live_ctx_t                *next; // 链表节点域，链表保存着所有订阅了stream的节点信息
    ngx_uint_t                          ndropped;
    // add by gw
	ngx_rtmp_live_chunk_stream_t        cs[3];
    ngx_uint_t                          meta_version;
    ngx_event_t                         idle_evt;
    unsigned                            active:1;
    unsigned                            publishing:1;
    unsigned                            silent:1;
    unsigned                            paused:1;
};


struct ngx_rtmp_live_stream_s {
    u_char                              name[NGX_RTMP_MAX_NAME];
    ngx_rtmp_live_stream_t             *next;
    ngx_rtmp_live_ctx_t                *ctx; // 订阅了该stream的链表头节点
    ngx_rtmp_bandwidth_t                bw_in;
    ngx_rtmp_bandwidth_t                bw_in_audio;
    ngx_rtmp_bandwidth_t                bw_in_video;
    ngx_rtmp_bandwidth_t                bw_out;
    ngx_msec_t                          epoch;
    unsigned                            active:1;
    unsigned                            publishing:1;
};


typedef struct {
    ngx_int_t                           nbuckets;
    ngx_rtmp_live_stream_t            **streams; // 存储所有的stream
    ngx_flag_t                          live;
    ngx_flag_t                          meta;
    ngx_msec_t                          sync;
    ngx_msec_t                          idle_timeout;
    ngx_flag_t                          atc;
    ngx_flag_t                          interleave;
    ngx_flag_t                          wait_key;
    ngx_flag_t                          wait_video;
    ngx_flag_t                          publish_notify;
    ngx_flag_t                          play_restart;
    ngx_flag_t                          idle_streams;
    ngx_msec_t                          buflen;
    ngx_pool_t                         *pool;
    ngx_rtmp_live_stream_t             *free_streams;
} ngx_rtmp_live_app_conf_t;


extern ngx_module_t  ngx_rtmp_live_module;


#endif /* _NGX_RTMP_LIVE_H_INCLUDED_ */
