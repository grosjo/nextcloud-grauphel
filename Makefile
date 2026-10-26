# Release and development tasks for grauphel.
#
#   make appstore   build build/artifacts/grauphel-VERSION.tar.gz
#   make sign       print the App Store signature of that archive
#   make lint psalm test

app_name    := grauphel
version     := $(shell sed -n 's:.*<version>\(.*\)</version>.*:\1:p' appinfo/info.xml)
build_dir   := build
release_dir := $(build_dir)/release/$(app_name)
archive     := $(build_dir)/artifacts/$(app_name)-$(version).tar.gz
cert_dir    ?= $(HOME)/.nextcloud/certificates

.PHONY: all appstore sign clean lint psalm test

all: appstore

appstore: clean
	mkdir -p $(release_dir) $(dir $(archive))
	rsync -a \
		--exclude=/.git \
		--exclude=/.github \
		--exclude=/.claude \
		--exclude=/build \
		--exclude=/vendor \
		--exclude=/tests \
		--exclude=/tools \
		--exclude=/composer.json \
		--exclude=/composer.lock \
		--exclude=/psalm.xml \
		--exclude=/Makefile \
		--exclude=.gitignore \
		./ $(release_dir)/
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing app code with $(cert_dir)/$(app_name).key"; \
		php ../../occ integrity:sign-app \
			--privateKey=$(cert_dir)/$(app_name).key \
			--certificate=$(cert_dir)/$(app_name).crt \
			--path=$(CURDIR)/$(release_dir); \
	fi
	tar -czf $(archive) -C $(build_dir)/release $(app_name)
	@echo "Created $(archive)"

# Signature to paste into the App Store release form
sign: $(archive)
	openssl dgst -sha512 -sign $(cert_dir)/$(app_name).key $(archive) | openssl base64

clean:
	rm -rf $(build_dir)

lint:
	find lib appinfo templates tests -name '*.php' -print0 | xargs -0 -n1 php -l
	node --check js/grauphel.js

psalm:
	composer psalm

test:
	composer test:unit
