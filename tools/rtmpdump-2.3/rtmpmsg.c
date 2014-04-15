/***************************************************************************
 * 
 * Copyright (c) 2014 Baidu.com, Inc. All Rights Reserved
 * 
 **************************************************************************/
 
 
 
/**
 * @file rtmpmsg.c
 * @author gaowei(gaowei@baidu.com)
 * @date 2014/04/15 12:02:33
 * @brief 
 *  
 **/
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include "librtmp/rtmp_sys.h"
#include "librtmp/log.h"
#include <getopt.h>
#include <signal.h>

#define STR2AVAL(av,str)	av.av_val = str; av.av_len = strlen(av.av_val)

void
sigIntHandler(int sig)
{
  RTMP_ctrlC = TRUE;
  RTMP_LogPrintf("Caught signal: %d, cleaning up, just a second...\n", sig);
  // ignore all these signals now and let the connection close
  signal(SIGINT, SIG_IGN);
  signal(SIGTERM, SIG_IGN);

}

RTMP*rtmp=NULL;//rtmp应用指针
RTMPPacket*packet=NULL;//rtmp包结构
void ZCLEAR();//清除相关

int GetMsg()	
{
	RTMPPacket_Reset(packet);//重置packet状态
	int32_t nRead;  	
  	nRead = RTMP_ReadPacket(rtmp, packet) ;
  	RTMPPacket_Dump(packet);
  	printf("i receive a msg : %s\n" ,packet->m_body);
  	return nRead;
}

int SendMsg(char * msg, int size)	
{
	RTMP_EnableWrite(rtmp);
	RTMPPacket_Reset(packet);//重置packet状态	
	packet->m_headerType = RTMP_PACKET_SIZE_MEDIUM; 
	time_t timep;
    time (&timep);
	packet->m_nTimeStamp = timep; 
	packet->m_packetType=0x0A;
	packet->m_nBodySize=size;
	strncpy(packet->m_body,msg,size);	
	packet->m_nChannel = 0x08; //通道
	packet->m_nInfoField2 = rtmp->m_stream_id;
	RTMP_SendPacket(rtmp,packet,0);
	return 0;

}
void usage(char *prog)
{
	RTMP_LogPrintf("-h               Prints this help screen.\n");
	RTMP_LogPrintf("-r url           URL (e.g. rtmp://host[:port]/path)\n");
	RTMP_LogPrintf("-s                I am a sender.\n");
	


}
int main(int argc, char ** argv)
{
	extern char *optarg;
	signal(SIGINT, sigIntHandler);
  	signal(SIGTERM, sigIntHandler);
	int opt;
  	struct option longopts[] = {
    	{"help", 0, NULL, 'h'},       	
    	{"msg", 0, NULL, 's'},   
    	{"rtmp", 1, NULL, 'r'},   
    	{0, 0, 0, 0}
  	};
  	
  	AVal msg = { 0, 0 };
  	AVal swfUrl = { 0, 0 };
  	AVal tcUrl = { 0, 0 };
  	AVal pageUrl = { 0, 0 };
  	AVal app = { 0, 0 };
  	AVal auth = { 0, 0 };
  	AVal swfHash = { 0, 0 };
  	AVal hostname = { 0, 0 };
  	AVal playpath = { 0, 0 };
  	AVal sockshost = { 0, 0 };
  	AVal subscribepath = { 0, 0 };
  	uint32_t swfSize = 0;
  	AVal flashVer = { 0, 0 };
  	int protocol = RTMP_PROTOCOL_UNDEFINED;
	int port = -1;
	int sender = FALSE;
	while ((opt = getopt_long(argc, argv,"hms:r:#",longopts, NULL)) != -1)
    {
    	switch(opt){
    		case 'r':
    		{	
    			AVal parsedHost, parsedApp, parsedPlaypath;
	    		unsigned int parsedPort = 0;
	    		int parsedProtocol = RTMP_PROTOCOL_UNDEFINED;

			    if (!RTMP_ParseURL(optarg, &parsedProtocol, &parsedHost, &parsedPort,
				 	&parsedPlaypath, &parsedApp))
			    {
					RTMP_Log(RTMP_LOGWARNING, "Couldn't parse the specified url (%s)!",
				    	optarg);
			    }
			    else
			    {
					if (!hostname.av_len)
					  	hostname = parsedHost;
					if (port == -1)
					  	port = parsedPort;
					if (playpath.av_len == 0 && parsedPlaypath.av_len)
					{
					    playpath = parsedPlaypath;
					}
					if (protocol == RTMP_PROTOCOL_UNDEFINED)
					  	protocol = parsedProtocol;
					if (app.av_len == 0 && parsedApp.av_len)
					{
					    app = parsedApp;
					}
			    }
			    
    			break;
    		}	
    		case 'm':
    		{
    			sender = TRUE;
    			break;
    		}
    		case 'h':
    		{
    			usage(argv[0]);
    			exit(0);
    			break;
    		}
    		default:
    		{	
    			usage(argv[0]);
    			exit(0);
    		}
    	}		
   	} 	
   	uint32_t dSeek = 0;
   	int bLiveStream = TRUE;
   	long int timeout = 1200;
   	
   	uint32_t dStopOffset = 0;  
/////////////////////////////////初始化//////////////////////	
	RTMP_LogLevel lvl=RTMP_LOGALL;
	RTMP_LogSetLevel(lvl);//设置信息等级
	//RTMP_LogSetOutput(FILE*fp);//设置信息输出文件
	rtmp=RTMP_Alloc();//申请rtmp空间
	RTMP_Init(rtmp);//初始化rtmp设置
	rtmp->Link.timeout=5;//设置连接超时，单位秒，默认30秒
	packet=calloc(1, sizeof(RTMPPacket));//创建包
	RTMPPacket_Alloc(packet,1024*64);//给packet分配数据空间，要满足最长的帧，不知道可设大些
	
////////////////////////////////连接//////////////////
	RTMP_SetupStream(rtmp, protocol, &hostname, port, &sockshost, &playpath,
		&tcUrl, &swfUrl, &pageUrl, &app, &auth, &swfHash, swfSize,
		&flashVer, &subscribepath, dSeek, dStopOffset, bLiveStream, timeout);
	//RTMP_EnableWrite(rtmp);//设置可写状态
	if (!RTMP_Connect(rtmp, NULL))
	{
	    printf("connect err\n");
		ZCLEAR();
		return -1;
	}
	if (!RTMP_ConnectStream(rtmp,dSeek))//创建流(取决于rtmp->Link.lFlags)
	{
		printf("ConnectStreamerr\n");
		ZCLEAR();
		return -1;
	}
	while (!RTMP_ctrlC){
			
		if(sender){
			char tmp[100] = {0};
			printf("waiting for your msg :\n");
			scanf("%s",tmp);
			SendMsg(tmp, strlen(tmp));
		}else{
			GetMsg();	

		}		


	}
	
	ZCLEAR();
	return 0;
}

void ZCLEAR()
{
	//////////////////////////////////////////释放/////////////////////
	if (rtmp!=NULL)
	{
		RTMP_Close(rtmp);//断开连接
		RTMP_Free(rtmp);//释放内存
		rtmp=NULL;
	}
	if (packet!=NULL)
	{
		RTMPPacket_Free(packet);//释放内存
		free(packet);
		packet=NULL;
	}
}























/* vim: set expandtab ts=4 sw=4 sts=4 tw=100 */
