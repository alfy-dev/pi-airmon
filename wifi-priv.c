#include <stdio.h>
#include <stdlib.h>
#include <sys/types.h>
#include <unistd.h>

int main(int argc, char *argv[])
{

  FILE *fptr1, *fptr2;
  int ch;
  int ret;

  setuid(0);
  
  if( (fptr1 = fopen(argv[1], "r") ) == NULL ) {
    printf("Error...\nCannot open file: %s\n", argv[1]);
    return -1;
  }

  if( (fptr2 = fopen("/etc/wpa_supplicant/wpa_supplicant.conf", "w") ) == NULL) {
    printf("Error...\nCannot open file: /etc/wpa_supplicant/wpa_supplicant.conf\n");
    return -1;
  }
  
  else {

    ch = fgetc(fptr1);
    while (ch != EOF) {
      fputc(ch, fptr2);
      ch = fgetc(fptr1);
    }

  }


  fclose(fptr1);
  fclose(fptr2);

  ret = system("/sbin/wpa_cli -i wlan0 reconfigure");
  
  if (ret == -1) {
    printf("error\n");
    return -1;
  }
  else {
    printf("ok\n");
    return 0;
  }

}

