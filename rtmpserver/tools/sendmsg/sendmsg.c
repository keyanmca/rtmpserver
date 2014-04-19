/***************************************************************************
 * 
 * Copyright (c) 2014 Baidu.com, Inc. All Rights Reserved
 * 
 **************************************************************************/
 
 
 
/**
 * @file sendmsg.c
 * @author gaowei(gaowei@baidu.com)
 * @date 2014/04/03 11:46:12
 * @brief 
 *  
 **/
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>
#include "rtmp.h"
#include "amf.h"
#include "log.h"
#include <unistd.h>
#include <pthread.h>
#include <netdb.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>

char *vid;
char uid[] = "yangzhe928";
int s = 0; // sina
int n = 1; // player1

RTMP *acfun_rtmp;

struct addrinfo *acfun_hosts, acfun_hint = { 0 };
struct in_addr acfun_ip;

#define CAVC(x) const AVal av_##x = AVC(#x)
#define TRUE 1

char app[50], flashVer[] = "LNX\01410,1,53,64", swfUrl[500], tcUrl[500], pageUrl[500];

void usage() {
  fprintf(stderr,
          "sendmsg -s <video source> -n <acfun player> -p <pageUrl> <vid>\n"
          "video source: 0 for sina\n"
          "acfun player: 0 for player.swf; 1 for player1.swf\n");
}

void setAVal(AVal *v, char *p) {
  v->av_val = p;
  v->av_len = strlen(p);
}

void *acfun_post(void *arg) {
  int info;
  char pbuf[4096], *pend = pbuf + sizeof(pbuf);
  char line[4096], cmd[20], *p;
  char message[4096], color[20], times[256];
  double mode, playTime, fontsize, alpha;
  char *running = (char *)arg;

  RTMPPacket packet = { 0 };
  char *enc;
  CAVC(publicChat);
  CAVC(playTime);
  CAVC(times);
  CAVC(fontsize);
  CAVC(username);
  CAVC(alpha);
  CAVC(message);
  CAVC(color);
  CAVC(mode);
  AVal val;

  puts("please input the messages");
  info = 0;
  while (gets(line)) {
    p = strchr(line, ':');
    if (p != NULL) {
      *p++ = '\0';
      while (*p == ' ')
        ++p;
      sscanf(line, "%s", cmd);
      if (strcmp(cmd, "mode") == 0) {
        if (sscanf(p, "%lf", &mode) == 1) {
          info |= 1;
        }
      }
      if (strcmp(cmd, "message") == 0) {
        strcpy(message, p);
        info |= 2;
      }
      if (strcmp(cmd, "color") == 0) {
        strcpy(color, p);
        info |= 4;
      }
      if (strcmp(cmd, "alpha") == 0) {
        if (sscanf(p, "%lf", &alpha) == 1) {
          info |= 8;
        }
      }
      if (strcmp(cmd, "times") == 0) {
        strcpy(times, p);
        info |= 16;
      }
      if (strcmp(cmd, "fontsize") == 0) {
        if (sscanf(p, "%lf", &fontsize) == 1) {
          info |= 32;
        }
      }
      if (strcmp(cmd, "playTime") == 0) {
        if (sscanf(p, "%lf", &playTime) == 1) {
          info |= 64;
        }
      }
    }
    if (info == 127) {
      packet.m_nChannel = 0x03;
      packet.m_headerType = RTMP_PACKET_SIZE_LARGE;
      packet.m_packetType = 0x14;
      //packet.m_nTimeStamp += 1000;
      packet.m_nTimeStamp = 0;
      packet.m_nInfoField2 = 0;
      packet.m_hasAbsTimestamp = 0;
      packet.m_body = pbuf + RTMP_MAX_HEADER_SIZE;

      enc = packet.m_body;
      enc = AMF_EncodeString(enc, pend, &av_publicChat);
      enc = AMF_EncodeNumber(enc, pend, 0);
      *enc++ = AMF_NULL;
      *enc++ = AMF_OBJECT;

      enc = AMF_EncodeNamedNumber(enc, pend, &av_playTime, playTime);
      setAVal(&val, times);
      enc = AMF_EncodeNamedString(enc, pend, &av_times, &val);
      enc = AMF_EncodeNamedNumber(enc, pend, &av_fontsize, fontsize);
      setAVal(&val, uid);
      uid[8] = 'a' + (uid[8] + 1) % 26;
      enc = AMF_EncodeNamedString(enc, pend, &av_username, &val);
      enc = AMF_EncodeNamedNumber(enc, pend, &av_alpha, alpha);
      setAVal(&val, message);
      enc = AMF_EncodeNamedString(enc, pend, &av_message, &val);
      setAVal(&val, color);
      enc = AMF_EncodeNamedString(enc, pend, &av_color, &val);
      enc = AMF_EncodeNamedNumber(enc, pend, &av_mode, mode);
      *enc++ = 0; *enc++ = 0; *enc++ = AMF_OBJECT_END;

      packet.m_nBodySize = enc - packet.m_body;

      if (RTMP_SendPacket(acfun_rtmp, &packet, TRUE))
        fputs("sent.", stderr);
      else
        break;
      packet.m_body = NULL;

      sleep(3);
      info = 0;
    }
  }
  *running = 0;
  return NULL;
}

int login() {
  char url[1000];

  RTMP_Init(acfun_rtmp);
  sprintf(url, "rtmp://%s/%s app=%s tcUrl=%s swfUrl=%s pageUrl=%s flashVer=%s conn=O:1 conn=NS:playType:sina conn=NS:username:yangzhe928 conn=NS:movieID:%s conn=NS:sortSina:new conn=O:0 swfVfy=1", inet_ntoa(acfun_ip), app, app, tcUrl, swfUrl, pageUrl, flashVer, vid);
  RTMP_SetupURL(acfun_rtmp, url);
  if (RTMP_Connect(acfun_rtmp, NULL) == 0) {
    RTMP_Free(acfun_rtmp);
    perror("RTMP_Connect");
    fprintf(stderr, "Can not connect.\n");
    return -1;
  }
  else {
    fprintf(stderr, "Connected.\n");
  }

  sleep(1);
  return 0;
}

void chat() {
  RTMPPacket packet = { 0 };
  char pbuf[4096], *pend = pbuf + sizeof(pbuf);
  char *enc;
  const AVal av_chat = AVC("chat");
  char running = 1;
  char chatsent = 0;
  //int nType;

  while (RTMP_IsConnected(acfun_rtmp) && RTMP_ReadPacket(acfun_rtmp, &packet)) {
    if (RTMPPacket_IsReady(&packet)) {
      if (!packet.m_nBodySize) {
        RTMPPacket_Free(&packet);
        continue;
      }
      if (packet.m_packetType == 0x14) {
        RTMPPacket_Free(&packet);
        packet.m_nChannel = 0x03;
        packet.m_headerType = RTMP_PACKET_SIZE_LARGE;
        packet.m_packetType = 0x13;
        packet.m_nTimeStamp = 0;
        packet.m_nInfoField2 = 0;
        packet.m_hasAbsTimestamp = 0;
        packet.m_body = pbuf + RTMP_MAX_HEADER_SIZE;

        enc = packet.m_body - 1;
        enc = AMF_EncodeString(enc, pend, &av_chat);
        packet.m_body[0] = 0;
        enc = AMF_EncodeNumber(enc, pend, 0);
        *enc++ = 0;
        *enc++ = 0;
        *enc++ = 0;
        *enc++ = 1;
        *(int *)enc = 0;
        enc += 4;

        packet.m_nBodySize = enc - packet.m_body;

        if (RTMP_SendPacket(acfun_rtmp, &packet, TRUE)) {
          chatsent = 1;
          //sleep(1);
        }
        packet.m_body = NULL;
        break;
      }
      else if (packet.m_packetType == 0x05) {
        RTMP_SendPacket(acfun_rtmp, &packet, TRUE);
        RTMPPacket_Free(&packet);
      }
/*
      else if (packet.m_packetType == 0x04) {
        nType = -1;
        if (packet.m_nBodySize >= 2)
          nType = AMF_DecodeInt16(packet.m_body);
        if (nType == 0x1A) {
          RTMP_SendCtrl(acfun_rtmp, 0x1B, 0, 0);
        }
        else if (nType >= 0) {
          RTMP_ClientPacket(acfun_rtmp, &packet);
          RTMPPacket_Free(&packet);
        }
      }
*/
      else {
        RTMP_ClientPacket(acfun_rtmp, &packet);
        RTMPPacket_Free(&packet);
      }
    }
  }

  pthread_t post;
  if (RTMP_IsConnected(acfun_rtmp))
    pthread_create(&post, NULL, acfun_post, &running);
  else {
    RTMP_Close(acfun_rtmp);
    RTMP_Free(acfun_rtmp);
    return;
  }
  while (running && RTMP_IsConnected(acfun_rtmp)) {
    RTMP_ReadPacket(acfun_rtmp, &packet);
    if (RTMPPacket_IsReady(&packet)) {
      if (!packet.m_nBodySize) {
        RTMPPacket_Free(&packet);
        continue;
      }
      if (packet.m_packetType == 0x13) {
//      RTMP_LogHex(RTMP_LOGERROR, (uint8_t *)packet.m_body, packet.m_nBodySize);
      }
      RTMP_ClientPacket(acfun_rtmp, &packet);
      RTMPPacket_Free(&packet);
    }
  }

  void *ret;
  pthread_join(post, &ret);
  RTMP_Close(acfun_rtmp);
  RTMP_Free(acfun_rtmp);
}

int main(int argc, char **argv) {
  int opt;
  while (-1 != (opt = getopt(argc, argv, "s:n:p:"))) {
    switch(opt) {
    case 's':
      s = atoi(optarg);
      break;
    case 'n':
      n = atoi(optarg);
      break;
    case 'p':
      strcpy(pageUrl, optarg);
      break;
    default:
      usage();
      return -1;
    }
  }

  if (optind < argc)
    vid = argv[optind];
  else {
    usage();
    return -1;
  }

  acfun_rtmp = RTMP_Alloc();
  if (acfun_rtmp == NULL) {
    perror("RTMP_Alloc");
    return -1;
  }

  acfun_hint.ai_family = AF_INET;
  acfun_hint.ai_socktype = SOCK_STREAM;
  acfun_hint.ai_protocol = IPPROTO_TCP;
  acfun_hint.ai_flags = AI_ADDRCONFIG;
  if (0 != getaddrinfo("www.acfun.cn", NULL, &acfun_hint, &acfun_hosts)) {
    perror("getaddrinfo");
    return -1;
  }

  if (NULL == acfun_hosts) {
    freeaddrinfo(acfun_hosts);
    return -1;
  }
  acfun_ip.s_addr = ((struct sockaddr_in *)acfun_hosts->ai_addr)->sin_addr.s_addr;
  freeaddrinfo(acfun_hosts);

  if (s == 0) {
    sprintf(app, "flvplayer%d/%s", n + 2, vid);
    sprintf(swfUrl, "http://%s/newflvplayer/player%s.swf?id=%s&type=video&sort=new", inet_ntoa(acfun_ip), (n==1)?"1":"", vid);
    sprintf(tcUrl, "rtmp::1935/flvplayer%d/%s", n + 2, vid);
    if (strlen(pageUrl) == 0)
      strcpy(pageUrl, swfUrl);

    RTMP_LogSetLevel(RTMP_LOGERROR);

    if (login() == -1) {
      fprintf(stderr, "login failed.\n");
      return -3;
    };
    chat();
  }
  else {
    fprintf(stderr, "Not supported video source.\n");
    return -2;
  }

  return 0;
}























/* vim: set expandtab ts=4 sw=4 sts=4 tw=100 */
