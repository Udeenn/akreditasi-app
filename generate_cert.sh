#!/bin/bash
# Generate self-signed SSL certificate untuk development
# MSYS_NO_PATHCONV=1 mencegah Git Bash mengkonversi path di Windows
MSYS_NO_PATHCONV=1 openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout .docker/nginx/selfsigned.key \
  -out .docker/nginx/selfsigned.crt \
  -subj "/C=ID/ST=JawaTengah/L=Surakarta/O=UMS/OU=IT/CN=mam.ums.ac.id"
