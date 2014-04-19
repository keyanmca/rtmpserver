PROGRAM := sendmsg 
SRCDIRS := .
INCLUDES := -I .  -I ./librtmp/include
SRCEXTS := .c .cpp

CFLAGS := -Wall -g
CFLAGS += $(INCLUDES)

#LDFLAGS=-L/home/zhanglei18/lib/lib/
LIBS=-lpthread -lm -lssl -lcrypto -ldl -lz
CC = g++ 
RM = rm -f

SHELL = /bin/sh
SOURCES = $(foreach d,$(SRCDIRS),$(wildcard $(addprefix $(d)/*,$(SRCEXTS))))
OBJS = $(foreach x,$(SRCEXTS), $(patsubst %$(x),%.o,$(filter %$(x),$(SOURCES))))
DEPS = $(patsubst %.o,%.d,$(OBJS))
OBJS2 = ./librtmp/lib/librtmp.a 

.PHONY : all objs clean

all : $(PROGRAM)

# Rules for creating the dependency files (.d).
#---------------------------------------------------
%.d : %.c
	@$(CC) -MM -MD $(CFLAGS) $<


objs : $(OBJS)
%.o : %.c
	$(CC) -c $(CPPFLAGS) $(CFLAGS) $<

$(PROGRAM) : $(OBJS)
	$(CC) -o $(PROGRAM) $(OBJS) $(OBJS2) $(LDFLAGS) $(LIBS)
	
clean :
	@$(RM) *.o *.d sendmsg
