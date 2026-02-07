openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout .docker/nginx/selfsigned.key -out .docker/nginx/selfsigned.crt -subj "/C=ID/ST=JawaTengah/L=Surakarta/O=UMS/OU=IT/CN=data-lib.ums.ac.id"
