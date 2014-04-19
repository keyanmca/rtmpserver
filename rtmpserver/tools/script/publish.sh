./ffmpeg -i ./gaowei.mp4 -strict -2  -c:v libx264 -c:a aac -f flv  rtmp://10.26.74.17:8081/live/test?access_token=1
