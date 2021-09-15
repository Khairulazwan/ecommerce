@echo off
"C:\Bitnami\wordpress-5.6-0/mysql\bin\mysql.exe" --defaults-file="C:\Bitnami\wordpress-5.6-0/mysql\my.ini" -u root -e "DELETE FROM mysql.user WHERE User='';"
"C:\Bitnami\wordpress-5.6-0/mysql\bin\mysql.exe" --defaults-file="C:\Bitnami\wordpress-5.6-0/mysql\my.ini" -u root -e "CREATE USER 'root'@'127.0.0.1' IDENTIFIED BY '%1';"
"C:\Bitnami\wordpress-5.6-0/mysql\bin\mysql.exe" --defaults-file="C:\Bitnami\wordpress-5.6-0/mysql\my.ini" -u root -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;"
"C:\Bitnami\wordpress-5.6-0/mysql\bin\mysql.exe" --defaults-file="C:\Bitnami\wordpress-5.6-0/mysql\my.ini" -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '%1';"
