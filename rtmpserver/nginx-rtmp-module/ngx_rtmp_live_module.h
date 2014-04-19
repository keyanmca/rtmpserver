
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
 * live module��Ӧ�������Ľṹ���������Ǹ�����Ľڵ㣬��洢��session��ctx��Ա��
 * ���յ�����Ƶ��ʱ���������ݽṹ���������:
 * 1. �����õ�����������ص�Ψһ��ngx_rtmp_session_t�������ڶ�ȡctx����Ӧlive module���ݽṹΪngx_rtmp_live_ctx_s
 * 2. ��ngx_rtmp_live_ctx_s�ж�ȡstream��Ա������Ǹ�publisher������stream�����ݽṹΪngx_rtmp_live_stream_t
 * 3. ��ngx_rtmp_live_stream_t�ж�ȡctx��Ա�����¼����subscriber������ĳ�Աͷ�ڵ�
 * 4. ˳�����������е�����Ƶ���ݣ����͵�����subscriber����
 * 
 */
struct ngx_rtmp_live_ctx_s {
    ngx_rtmp_session_t                 *session; // ��Ӧ��session
    ngx_rtmp_live_stream_t             *stream; // ��Ӧ��stream
    ngx_rtmp_live_ctx_t                *next; // ����ڵ��������������ж�����stream�Ľڵ���Ϣ
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
    ngx_rtmp_live_ctx_t                *ctx; // �����˸�stream������ͷ�ڵ�
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
    ngx_rtmp_live_stream_t            **streams; // �洢���е�stream
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
