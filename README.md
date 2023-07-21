# TempRent public project repository

_This repository is based on the reseach grant nr 22/16.06.2017 within Priority Axis 2.2.1 "Tehnologia Informației și Comunicațiilor (TIC) pentru o economie digitală competitivă - Creșterea contribuției sectorului TIC pentru competitivitatea economică - Sprijinirea creșterii valorii adăugate generate de sectorul TIC și a inovării în domeniu prin dezvoltarea de clustere”_

# Install

**The following information is presented in Romanian language. The English instructions will soon be added**

## Mediul
- php 7.2
- mysql (sau mariadb)
- composer
- extensii de php
  - bz2
  - intl
  - iconv
  - bcmath
  - opcache
  - calendar
  - mysql
  - zip
  - json
  - xml

## Aplicatia

Dupa ce totul este instalat, se cloneaza acest repo intr-un folder si se ruleaza
`$ composer install`

In urma instalarii tuturor bibliotecilor depente se vor configura cativa parametri de aplicatie. Acesta este un proces interactiv, iar valorile se salveaza in `app/config/parameters.yml`.

## Configurari

Inca aplicatia nu este functionala. Trebuie creata baza de date. Pentru aceasta, vom rula urmatoarea comanda:
`$ php bin/console doctrine:database:create`

Iar pentru crearea schemei bazei de date se va rula:
`$ php bin/console doctrine:schema:create`

Deoarece in acest moment baza de date este goala, vom fi nevoiti sa cream utilizatorul de administrare folosind CLI:
`$ php bin/console fos:user:create`

Si apoi il promovam ca super-admin
`$ php bin/console fos:user:promote`

Urmarim instructiunile de pe ecran si cand ni se cere sa introducem rolul in care este promovat userul, vom introduce **ROLE_SUPER_ADMIN**

## Finalizarea instalarii

Pentru a rula aplicatia, se va rula:
`$ php bin/console server:start 0.0.0.0:9004`

Aceasta comanda va porni serverul aplicatiei si il va expune pe portul 9004.

> ATENTIE! aplicatia nu va rula in production mode in acest fel. Chiar daca este perfect functionala, nu este recomandata rularea cu aceasta metoda in productie.

**!Pentru a rula in productie!!**
1. se defineste un virtual host nou in apache2 folosind urmatoarea configurare:
```xml
<VirtualHost *:80>
    DocumentRoot /home/lummetry/webapp/service/web
    <Directory /home/lummetry/webapp/service/web>
        Require all granted
        AllowOverride All
        Allow from All
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(.*)$ app.php [QSA,L]
    </Directory>
    ErrorLog /var/log/apache2/project_error.log
    CustomLog /var/log/apache2/project_access.log combined
</VirtualHost>

<VirtualHost *:443>
    DocumentRoot /home/lummetry/webapp/service/web
    <Directory /home/lummetry/webapp/service/web>
        Require all granted
        AllowOverride All
        Allow from All
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(.*)$ app.php [QSA,L]
    </Directory>
    ErrorLog /var/log/apache2/project_error.log
    CustomLog /var/log/apache2/project_access.log combined

</VirtualHost>
```

Se editeaza calea din configurare cu calea unde a fost clonata aplicatia, apoi se salveaza, de exemplu sub denumirea de *temprent.conf* in `/etc/apache2/sites-available/`.

Apoi:
1. se dezactiveaza site-ul incarcat default:
`sudo a2dissite 000-default.conf`
2. se activreaza noul site *temprent.conf*
`sudo a2ensite temprent.conf`
3. se reporneste apache2
`sudo service apache2 restart`

## Instalarea ceritficatului SSL

Urmariti instructiunile de aici: https://www.digitalocean.com/community/tutorials/how-to-secure-apache-with-let-s-encrypt-on-ubuntu-18-04

## Dump-ul bazei de date

Se foloseste comanda (atentie ca cere parola, se editeaza numele fisierului dupa nevoie):

`$ mysqldump -umicrorentingadmin -p microrenting > ~/dbdumps/2020-06-25.sql`

## Configurari importante

### Setari legate de e-mail

Pentru a permite trimiterea e-mail-urilor Symfony se foloseste de SwiftMailer, o biblioteca open source care se leaga la SMTP. Configurarea acesteia este facuta prin intermediul fisierului `service/app/config/config.yml`. 

```yml
swiftmailer:
    transport:            '%mailer_transport%'
    username:             '%mailer_user%'
    password:             '%mailer_password%'
    host:                 '%mailer_host%'
    port:                 '%mailer_port%'
    logging:              true
    delivery_addresses:   ['temprent@lummetry.ai']
```

Optiunea de configurare `delivery_address` este cea care forteaza sistemul sa trimita toate e-mail-urile catre adresa `temprent@lummetry.ai`. Pentru a lasa sistemul sa trimita e-mail-urile catre destinatarii originali, trebuie stearsa aceasta optiune.

Toate variabilele mentionate mai sus in `config.yml` sunt definite in `service/app/config/parameters.yml`

## Export

Pentru exportul datelor se va folosi comanda `python3 export/export.py` lansata in directorul radacina al TempRent fie din SIEMMT sau din versiune Cloud.

## Operationalizare

> **ATENTIE**: in VM respectiv `_temprent_deploy` trebuie sa existe modelele/datele preantrenate si sa fie validate
>   - 20200622_w2v_ep25_transfered_20200120 
>   - 20200622_180702_AChT02_004_e058_dev_acc_0.843.h5 
>   - 20200622_180702_dct_label2idx.json

```
cd ~
cp -r _temprent_deploy/ webapp/
cd webapp
conda activate py37_tensorflow
nohup python api/run_server.py -H 0.0.0.0 -P 5001 -b ~/webapp -a _temprent_deploy -s 20 -e 20200622_w2v_ep25_transfered_20200120 -m 20200622_180702_AChT02_004_e058_dev_acc_0.843.h5 -d 20200622_180702_dct_label2idx.json &
tail -f -n 100 nohup.out
```

## SIEMMT

Parti ale codului sursa proprii dispozitivul incorporat SIEMMT nu este continut in acest repository public. Schemele, modelele neurale, instalarea, configurarea si functionarea dispozitivului incorporat SIEMMT sunt proprietate intelectuala exclusiva a 4E Software.
Nota:

> SIEMMT a fost portat 100% in motorul neural ALLAN. Incepand cu 2020-09-01 API-urile SIEMMT au fost modificate si inlocuite cu versiunea avansata ALLAN rezultata din cadrul proiectului TempRent.


