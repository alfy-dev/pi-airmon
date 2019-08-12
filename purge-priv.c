#include <stdio.h>
#include <stdlib.h>
#include <sys/types.h>
#include <unistd.h>

int main() {

	int ret;
	
	// become root
	ret = setuid(0);
	if (ret == -1) {
		printf("setuid root error\n");
		return -1;
	} 
	
	// kill planelogger.py
	printf("Killing planelogger.py\n");
	ret = system("/usr/bin/pkill -f planelogger.py");

	if (ret == -1) {
		printf("pkill error\n");
		return -1;
	} 
	
	// drop the database
	printf("Dropping the database\n");
	ret = system("export PGPASSWORD=SDIOSio23mids ; /usr/bin/dropdb -h localhost -U postgres PlaneReports");
	if (ret == -1) {
		printf("dropdb error\n");
		return -1;
	} 
	
	// re-create the database
	printf("Re-creating the database\n");
	ret = system("export PGPASSWORD=SDIOSio23mids ; createdb -h localhost -U postgres PlaneReports");
	if (ret == -1) {
		printf("createdb error\n");
		return -1;
	} 

	// restore the schema only: it was obtained by running once: pg_dump -U postgres -v -Fc -s -f /root/ads-b-logger/schema.psql PlaneReports
	printf("Re-creating the database (schema only)\n");
	ret = system("export PGPASSWORD=SDIOSio23mids ; /usr/bin/pg_restore -h localhost -U postgres -v -d PlaneReports /root/ads-b-logger/schema.psql");
	if (ret == -1) {
		printf("pg_restore error\n");
		return -1;
	} 
	
	// relaunch planelogger.py
	printf("Re-launching /etc/rc.local (planelogger.py)\n");
	system("/etc/rc.local");

}

