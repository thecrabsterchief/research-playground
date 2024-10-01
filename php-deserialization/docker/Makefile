build:
	docker build -t debug-swiftmailer .
up:
	docker run -d --rm -p 1338:80 --name debug-swiftmailer debug-swiftmailer
down:
	docker stop debug-swiftmailer
