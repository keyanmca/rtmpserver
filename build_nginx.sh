#!/bin/sh
SRC_PATH=$(pwd)
cd	$SRC_PATH 
#NGX_HOME="/home/work/local/nginx"
NGX_HOME="$SRC_PATH/tmp/nginx"
NGX_SBIN_PATH="$NGX_HOME/sbin/nginx"
NGX_CONF_PATH="$NGX_HOME/conf/nginx.conf"  
NGX_ERROR_LOG_PATH="$NGX_HOME/logs/error_log"  
NGX_HTTP_ACCESS_LOG_PATH="$NGX_HOME/logs/access_log"
NGX_PIDFILE_PATH="$NGX_HOME/run/nginx.pid"  
NGX_WORKER_PROC_USER=work  
NGX_WORKER_PROC_GROUP=work    
MOD_SRC_PATH=$SRC_PATH/tmp/
mkdir tmp
cp $SRC_PATH/tools/*.tar.gz ./tmp/
cp  -r $SRC_PATH/nginx/nginx-rtmp-module ./tmp/ 
cd tmp
tar -xvzf ./pcre-8.33.tar.gz 
tar -xvzf ./openssl-1.0.1e.tar.gz
PCRE_SRC=$SRC_PATH/tmp/pcre-8.33/
OPENSSL_SRC=$SRC_PATH/tmp/openssl-1.0.1e/ 
OPTS_WITH="--with-ipv6 \
	--with-http_ssl_module \
	--with-http_realip_module \
	--with-http_addition_module \
	--with-http_sub_module \
	--with-http_gunzip_module \
	--with-http_gzip_static_module \
	--with-http_stub_status_module \
	--with-pcre=$PCRE_SRC \
	--with-pcre-jit \
	--with-openssl=$OPENSSL_SRC \
	--with-cc-opt=-Wno-error \
	--with-ld-opt=-lstdc++"
#for options of --without-* of configuration script
OPTS_WITHOUT="--without-http_userid_module \
    --without-mail_pop3_module \
    --without-mail_imap_module \
    --without-mail_smtp_module"


OPTS_ADD_MODULE="--add-module=$MOD_SRC_PATH/nginx-rtmp-module" 



config_opts="--prefix=$NGX_HOME\
        $OPTS_WITH \
        $OPTS_WITHOUT \
        $OPTS_ADD_MODULE" 

tar -xvzf nginx-1.4.2.tar.gz
cd  nginx-1.4.2/
echo "./configure $config_opts"
./configure $config_opts
make 
#make install
cd	$SRC_PATH
mkdir -p ./output/nginx/bin ./output/nginx/conf ./output/nginx/lib ./output/nginx/logs
cp tmp/nginx-1.4.2/objs/nginx output/nginx/bin/
cp nginx/conf/* ./output/nginx/conf/ 
exit 0
