# TSiSIP Asset Build Pipeline
# Run: make -C build all

.PHONY: all clean variables css svg manifest po

BUILD_DIR := .
WEB_DIR := ../web/tsisip
ASSETS_DIR := $(WEB_DIR)/assets
CSS_DIR := $(WEB_DIR)/css
JS_DIR := $(WEB_DIR)/js
LOCALE_DIR := $(WEB_DIR)/locale

# Tools
SVGO := npx svgo
POSTCSS := npx postcss
MSGFMT := msgfmt

all: variables css svg manifest po
	@echo "TSiSIP build complete"

variables: theme.json
	node generate-css-variables.js

css: tsisip-theme.src.css variables
	$(POSTCSS) tsisip-theme.src.css -o $(CSS_DIR)/tsisip-theme.css

svg:
	@echo "Optimizing SVGs..."
	$(SVGO) -f ../design/logo -o $(ASSETS_DIR)/

manifest:
	node generate-manifest.js

po:
	@echo "Compiling .po to .mo..."
	$(MSGFMT) $(LOCALE_DIR)/tsisip-en.po -o $(LOCALE_DIR)/en_US/LC_MESSAGES/tsisip.mo || true
	$(MSGFMT) $(LOCALE_DIR)/tsisip-es.po -o $(LOCALE_DIR)/es_ES/LC_MESSAGES/tsisip.mo || true
	$(MSGFMT) $(LOCALE_DIR)/tsisip-pt.po -o $(LOCALE_DIR)/pt_BR/LC_MESSAGES/tsisip.mo || true

clean:
	rm -f $(CSS_DIR)/*.css $(ASSETS_DIR)/*.svg $(JS_DIR)/*.js $(WEB_DIR)/asset-manifest.json
