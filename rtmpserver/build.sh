#!/bin/sh

# 1. 初始化路径
SRC_PATH=$(pwd)

NGX_HOME=$SRC_PATH/tmp
NGX_OUTPUT=$SRC_PATH/output

NGINX_SERVER_SRC=$SRC_PATH/nginx-server
NGINX_MODULE_RTMP_SRC=$SRC_PATH/nginx-rtmp-module

CONF_SRC=$SRC_PATH/conf
PCRE_SRC=$SRC_PATH/lib/pcre
OPENSSL_SRC=$SRC_PATH/lib/openssl
MOD_SRC_PATH=$SRC_PATH


# 2. 初始化参数
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
	--with-debug \
	--with-ld-opt=-lstdc++"

#for options of --without-* of configuration script
OPTS_WITHOUT="--without-http_userid_module \
    --without-mail_pop3_module \
    --without-mail_imap_module \
    --without-mail_smtp_module"

OPTS_ADD_MODULE="--add-module=$NGINX_MODULE_RTMP_SRC" 

config_opts="--prefix=$NGX_HOME\
    $OPTS_WITH \
    $OPTS_WITHOUT \
    $OPTS_ADD_MODULE" 


# 3. 准备安装
rm -rf $NGX_HOME
mkdir -p $NGX_HOME
cd $NGINX_SERVER_SRC
make clean

echo "./configure $config_opts"

make clean
./configure $config_opts
make 
make install


# 4. 输出output目录
rm -fr $NGX_OUTPUT
mkdir -p $NGX_OUTPUT $NGX_OUTPUT/bin $NGX_OUTPUT/conf $NGX_OUTPUT/logs
cp $NGX_HOME/sbin/nginx $NGX_OUTPUT/bin/
cp $CONF_SRC/* $NGX_OUTPUT/conf/

rm -fr $NGX_HOME

make clean

cd $PCRE_SRC
make clean

cd $OPENSSL_SRC
make clean

exit 0
