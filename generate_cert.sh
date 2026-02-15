#!/bin/bash
# Generate self-signed SSL certificate untuk development
# Untuk production, ganti CN ke data-lib.ums.ac.id
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout .docker/nginx/selfsigned.key \
  -out .docker/nginx/selfsigned.crt \
  -subj "/C=ID/ST=JawaTengah/L=Surakarta/O=UMS/OU=IT/CN=mam.ums.ac.id"
