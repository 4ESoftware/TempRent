# TempRent public project repository

_this repository is based on the reseach grant nr 22/16.06.2017 within Priority Axis 2.2.1 "Tehnologia Informației și Comunicațiilor (TIC) pentru o economie digitală competitivă - Creșterea contribuției sectorului TIC pentru competitivitatea economică - Sprijinirea creșterii valorii adăugate generate de sectorul TIC și a inovării în domeniu prin dezvoltarea de clustere”_

# Install

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
