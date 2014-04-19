
default:	build

clean:
	rm -rf Makefile objs

build:
	$(MAKE) -f objs/Makefile
	$(MAKE) -f objs/Makefile manpage

install:
	$(MAKE) -f objs/Makefile install

upgrade:
	/home/gaowei/gitcode/rtmpserver/tmp/nginx/sbin/nginx -t

	kill -USR2 `cat /home/gaowei/gitcode/rtmpserver/tmp/nginx/logs/nginx.pid`
	sleep 1
	test -f /home/gaowei/gitcode/rtmpserver/tmp/nginx/logs/nginx.pid.oldbin

	kill -QUIT `cat /home/gaowei/gitcode/rtmpserver/tmp/nginx/logs/nginx.pid.oldbin`
