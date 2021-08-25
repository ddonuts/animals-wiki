DOCK=docker-compose
WORKDIR=/var/www
WEB_SERVICE=aw-web
DB_SERVICE=aw-db
DB_CONTAINER_ID=animals-wiki_aw-db_1
EXEC=$(DOCK) exec -w $(WORKDIR) $(WEB_SERVICE)

start: up
	@echo ' => App running on http://localhost:8200/'

stop: kill
	@echo ' => App stopped.'

restart: stop start

dist-files:
	cp .env.dist .env

up:
	$(DOCK) up -d

kill:
	$(DOCK) kill

download-itis:
	test -d itiMySQL && rm -rf itiMySQL
	curl https://www.itis.gov/downloads/itisMySQLBulk.zip --output itisMySQLBulk.zip
	unzip itisMySQLBulk.zip
	rm itisMySQLBulk.zip
	mv itisMySQL* itisMySQL

db-init:
	test -d itisMySQL || make download-itis
	docker cp itisMySQL/ITIS.sql $(DB_CONTAINER_ID):/ITIS.sql
	docker exec -i $(DB_CONTAINER_ID) mysql -uroot -proot mysql < itisMySQL/CreateDB.sql

web-service-setup:
	$(EXEC) rm -rf html
	$(EXEC) ln -s public html
	#$(EXEC) apt-get update --yes
	#$(EXEC) apt-get install --yes git
	#$(EXEC) apt-get install --yes zip unzip
	#$(EXEC) apt-get install --yes libmagickwand-dev --no-install-recommends
	$(EXEC) docker-php-ext-install mysqli pdo pdo_mysql
	##$(EXEC) bash -c "(printf "\n" | pecl install -f imagick) && docker-php-ext-enable imagick"
	$(EXEC) a2enmod rewrite
	$(DOCK) restart $(WEB_SERVICE)

init-services:
	$(DOCK) up -d

init: dist-files init-services web-service-setup db-init stop
	@echo ' => Init done. Run "make start" to launch the containers'

ssh:
	$(EXEC) bash


