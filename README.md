# TempRent public project repository

_this repository is based on the reseach grant nr 22/16.06.2017 within Priority Axis 2.2.1 "Tehnologia Informației și Comunicațiilor (TIC) pentru o economie digitală competitivă - Creșterea contribuției sectorului TIC pentru competitivitatea economică - Sprijinirea creșterii valorii adăugate generate de sectorul TIC și a inovării în domeniu prin dezvoltarea de clustere”_

# Install

Trebuie construite containerele:
`$ docker-compose build`

Apoi se pornesc:
`$ docker-compose up -d`

Apoi ne logam pe containerul de backend:
`$ docker exec -it temprent_temp_rent_1 bash`

Ne asiguram ca sunt instalate toate bibliotecile de care depindem:
`$ composer install`
> Atentie, in procesul de instalare trebuie sa setam niste parametri.

Cream baza de date si schema
`$ php bin/console doctrine:schema:create`

Cream utilizatorul de administrare:
`$ php bin/console fos:user:create`

Si il promovam ca super-administrator:
`php bin/console fos:user:promote`

> Please choose a username:admin
> Please choose a role:ROLE_SUPER_ADMIN

# Comenzi utile

### Vreau controlul unui container
`docker exec -it webapp_temp_rent_1 bash`

### Vreau sa vad statusul containerelor
`docker-compose ps`
